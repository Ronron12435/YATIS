<?php

namespace App\Http\Controllers;

use App\DTOs\Message\SendGroupMessageDTO;
use App\DTOs\Message\SendMessageDTO;
use App\Services\MessageService;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private MessageService $messageService) {}

    public function index()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function show($userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->getConversation(auth()->id(), (int) $userId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $validated = $request->validate([
            'recipient_id' => 'required|exists:users,id',
            'message'      => 'required|string|max:5000',
        ]);

        $dto = new SendMessageDTO(
            senderId: auth()->id(),
            recipientId: $validated['recipient_id'],
            content: $validated['message'],
        );

        $response = $this->messageService->send($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy($id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->delete((int) $id, auth()->id());
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function unreadCount()
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->unreadCount(auth()->id());
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function markAsRead($id)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->markAsRead((int) $id, auth()->id());
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function sendGroupMessage(Request $request, $groupId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
        ]);

        $dto = new SendGroupMessageDTO(
            groupId: (int) $groupId,
            senderId: auth()->id(),
            content: $validated['content'],
        );

        $response = $this->messageService->sendGroupMessage($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getGroupMessages($groupId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->getGroupMessages((int) $groupId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function markGroupAsRead($messageId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->markGroupAsRead((int) $messageId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function getGroupUnreadCounts()
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->messageService->getGroupUnreadCounts(auth()->id());
        return response()->json($response->toArray(), $response->statusCode);
    }
}
