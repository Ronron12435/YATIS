/**
 * Sidebar Location Icon Manager
 * Updates the People sidebar icon based on current section/location
 */

const SidebarLocationIcon = {
    iconMap: {
        'people': { icon: 'fas fa-map-pin', title: 'People Near You' },
        'my-friends': { icon: 'fas fa-user-friends', title: 'My Friends' },
        'friend-requests': { icon: 'fas fa-user-plus', title: 'Friend Requests' },
        'dashboard': { icon: 'fas fa-home', title: 'Dashboard' },
        'businesses': { icon: 'fas fa-store', title: 'Businesses' },
        'employers': { icon: 'fas fa-briefcase', title: 'Employers' },
        'destinations': { icon: 'fas fa-map-marked-alt', title: 'Destinations' },
        'events': { icon: 'fas fa-calendar-check', title: 'Events' },
        'profile': { icon: 'fas fa-user', title: 'Profile' },
        'groups': { icon: 'fas fa-users-cog', title: 'Groups' },
        'admin-panel': { icon: 'fas fa-cog', title: 'Admin' },
    },

    init() {
        // Listen for section changes
        const originalShowSection = window.showSection;
        if (originalShowSection) {
            window.showSection = (sectionId) => {
                this.updatePeopleIcon(sectionId);
                return originalShowSection(sectionId);
            };
        }
    },

    updatePeopleIcon(currentSection) {
        const peopleIcon = document.getElementById('people-icon');
        if (!peopleIcon) return;

        // Check if we're in a people-related section
        if (currentSection === 'people' || currentSection === 'my-friends' || currentSection === 'friend-requests') {
            const iconData = this.iconMap[currentSection] || this.iconMap['people'];
            const iconElement = peopleIcon.querySelector('i');
            
            if (iconElement) {
                // Remove all icon classes
                iconElement.className = '';
                // Add new icon classes
                iconData.icon.split(' ').forEach(cls => iconElement.classList.add(cls));
            }
            
            // Update title on hover
            peopleIcon.parentElement.title = iconData.title;
        }
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    SidebarLocationIcon.init();
});
