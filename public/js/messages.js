const MessagesModule = (() => {
    let currentChatUserId = null;
    let currentChatUserName = null;
    let chatPollInterval = null;
    const CSRF_TOKEN = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const init = () => {
        loadConversations();
        setupEventListeners();
    };

    const setupEventListeners = () => {
        const backBtn = document.getElementById('back-to-list-btn');
        const sendBtn = document.getElementById('send-message-btn');
        const chatInput = document.getElementById('chat-input');

        if (backBtn) backBtn.addEventListener('click', closeChatWindow);
        if (sendBtn) sendBtn.addEventListener('click', sendMessage);
        if (chatInput) {
            chatInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') sendMessage();
            });
        }
    };

    const loadConversations = () => {
        const list = document.getElementById('conversations-list');
        if (!list) return;

        fetch('/api/friends-list', { credentials: 'include' })
            .then(r => r.json())
            .then(friends => {
                const list = document.getElementById('conversations-list');
                if (!list) return;
                if (!friends || !friends.length) {
                    list.innerHTML = '<p style="color:#999; text-align:center; padding:20px;">No conversations yet. Add friends to start chatting!</p>';
                    return;
                }

                list.innerHTML = friends.map(friend => {
                    const initials = ((friend.first_name || '')[0] || '').toUpperCase() + 
                                   ((friend.last_name || '')[0] || '').toUpperCase();
                    const fullName = `${friend.first_name || ''} ${friend.last_name || ''}`.trim();
                    
                    return `
                        <div style="display:flex; align-items:center; gap:14px; padding:12px; border-radius:6px; cursor:pointer; transition:background 0.2s;"
                             onmouseover="this.style.background='#f0f4f8'"
                             onmouseout="this.style.background='transparent'"
                             onclick="MessagesModule.openChat(${friend.id}, '${fullName}', '${initials}')">
                            <div style="width:40px; height:40px; border-radius:50%; background:linear-gradient(135deg,#3498db,#2980b9); color:white; display:flex; align-items:center; justify-content:center; font-weight:bold; font-size:14px;">
                                ${initials}
                            </div>
                            <div style="flex:1;">
                                <div style="font-weight:600; color:#333;">${fullName}</div>
                                <div style="font-size:12px; color:#999;">Click to message</div>
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(err => {
                console.error('Error loading conversations:', err);
                const list = document.getElementById('conversations-list');
                if (list) list.innerHTML = '<p style="color:#e74c3c; text-align:center; padding:20px;">Error loading conversations</p>';
            });
    };

    const openChat = (userId, fullName, initials) => {
        currentChatUserId = userId;
        currentChatUserName = fullName;
        
        document.getElementById('chat-friend-name').textContent = fullName;
        document.getElementById('chat-friend-avatar').textContent = initials;
        
        document.getElementById('chat-list-view').style.display = 'none';
        document.getElementById('chat-window-view').style.display = 'flex';
        document.getElementById('chat-window-view').style.flexDirection = 'column';
        
        document.getElementById('chat-input').value = '';
        document.getElementById('chat-input').focus();
        
        loadMessages();
        
        if (chatPollInterval) clearInterval(chatPollInterval);
        chatPollInterval = setInterval(loadMessages, 3000);
    };

    const closeChatWindow = () => {
        if (chatPollInterval) clearInterval(chatPollInterval);
        
        currentChatUserId = null;
        currentChatUserName = null;
        
        document.getElementById('chat-window-view').style.display = 'none';
        document.getElementById('chat-list-view').style.display = 'block';
        
        loadConversations();
    };

    const loadMessages = () => {
        if (!currentChatUserId) return;

        fetch(`/api/messages/${currentChatUserId}`, { credentials: 'include' })
            .then(r => r.json())
            .then(response => {
                const messagesBox = document.getElementById('chat-messages');
                const messages = response.data || [];
                const myId = parseInt(document.querySelector('meta[name="user-id"]')?.content || '0');

                if (!Array.isArray(messages) || messages.length === 0) {
                    messagesBox.innerHTML = '<p style="color:#999; text-align:center; margin-top:40px;">No messages yet. Say hello!</p>';
                    return;
                }

                messagesBox.innerHTML = messages.map(msg => {
                    const isOwn = msg.sender_id === myId;
                    const senderName = isOwn ? 'You' : currentChatUserName;
                    const timestamp = new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                    
                    return `
                        <div style="display:flex; margin-bottom:12px; ${isOwn ? 'justify-content:flex-end' : 'justify-content:flex-start'}">
                            <div style="max-width:65%; background:${isOwn ? '#3498db' : '#ecf0f1'}; color:${isOwn ? 'white' : '#333'}; padding:10px 14px; border-radius:12px; word-wrap:break-word;">
                                <div style="font-size:12px; opacity:0.8; margin-bottom:4px;">${senderName} • ${timestamp}</div>
                                <div>${escapeHtml(msg.content)}</div>
                            </div>
                        </div>
                    `;
                }).join('');

                messagesBox.scrollTop = messagesBox.scrollHeight;
            })
            .catch(err => {
                console.error('Error loading messages:', err);
                document.getElementById('chat-messages').innerHTML = '<p style="color:#e74c3c; text-align:center;">Error loading messages</p>';
            });
    };

    const sendMessage = () => {
        const input = document.getElementById('chat-input');
        const message = input.value.trim();

        if (!message || !currentChatUserId) return;

        input.value = '';
        input.disabled = true;

        fetch('/api/messages', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'X-CSRF-Token': CSRF_TOKEN,
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                recipient_id: currentChatUserId,
                message: message
            })
        })
        .then(r => r.json())
        .then(response => {
            input.disabled = false;
            if (response.success) {
                loadMessages();
            } else {
                alert('Error sending message: ' + (response.message || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Error sending message:', err);
            input.disabled = false;
            alert('Error sending message');
        });
    };

    const escapeHtml = (text) => {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    };

    return {
        init,
        openChat,
        closeChatWindow,
        loadMessages,
        sendMessage
    };
})();

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', MessagesModule.init);
} else {
    MessagesModule.init();
}
