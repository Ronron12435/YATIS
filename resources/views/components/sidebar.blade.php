<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-container">
            <div class="sidebar-logo-text">YATIS</div>
        </div>
    </div>
    
    <div class="sidebar-content">
        @php
            $userRole = auth()->user()->role;
            $userBusiness = auth()->user()->businesses()->first();
            $isBusiness = $userRole === 'business';
        @endphp

        <!-- BUSINESS ACCOUNT MENU -->
        @if($isBusiness)
            <!-- Dashboard -->
            <div class="sidebar-item active" onclick="showSection('dashboard')">
                <span class="sidebar-icon"><i class="fas fa-home"></i></span>
                <div>Dashboard</div>
            </div>
            
            <!-- Businesses -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('businesses'); resetBusinessFilter();">
                    <span class="sidebar-icon"><i class="fas fa-store"></i></span>
                    <div>Businesses</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item business-filter-item" data-filter="food" onclick="showSection('businesses'); filterBusinessesByType('food'); updateFilterUI(this); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-utensils"></i></span>
                        <div>Food</div>
                    </div>
                    <div class="sidebar-item business-filter-item" data-filter="goods" onclick="showSection('businesses'); filterBusinessesByType('goods'); updateFilterUI(this); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <div class="sidebar-item business-filter-item" data-filter="services" onclick="showSection('businesses'); filterBusinessesByType('services'); updateFilterUI(this); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-tools"></i></span>
                        <div>Services</div>
                    </div>
                </div>
            </div>
            
            <!-- My Business -->
            <div class="sidebar-item" onclick="showSection('my-business')">
                <span class="sidebar-icon"><i class="fas fa-building"></i></span>
                <div>My Business</div>
            </div>
            
            <!-- Post a Job -->
            <div class="sidebar-item" onclick="showSection('jobs')">
                <span class="sidebar-icon"><i class="fas fa-plus-circle"></i></span>
                <div>Post a Job</div>
            </div>
            
            <!-- My Profile -->
            <div class="sidebar-item" onclick="showSection('profile')">
                <span class="sidebar-icon"><i class="fas fa-user-circle"></i></span>
                <div>My Profile</div>
            </div>

            <!-- Go Premium (if not premium) -->
            @if(!auth()->user()->is_premium)
            <div class="sidebar-item" onclick="showSection('premium')">
                <span class="sidebar-icon"><i class="fas fa-crown"></i></span>
                <div>Go Premium</div>
            </div>
            @endif

        <!-- REGULAR USER MENU -->
        @else
            <!-- Dashboard -->
            <div class="sidebar-item active" onclick="showSection('dashboard')">
                <span class="sidebar-icon"><i class="fas fa-home"></i></span>
                <div>Dashboard</div>
            </div>
            
            <!-- People Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('people')">
                    <span class="sidebar-icon"><i class="fas fa-users"></i></span>
                    <div>People</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item" onclick="showSection('my-friends'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-user-friends"></i></span>
                        <div>My Friends</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('friend-requests'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-user-plus"></i></span>
                        <div>Friend Requests</div>
                    </div>
                </div>
            </div>
            
            <!-- Businesses Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('businesses'); resetBusinessFilter();">
                    <span class="sidebar-icon"><i class="fas fa-store"></i></span>
                    <div>Businesses</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item business-filter-item" data-filter="food" onclick="showSection('businesses'); filterBusinessesByType('food'); updateFilterUI(this); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-utensils"></i></span>
                        <div>Food</div>
                    </div>
                    <div class="sidebar-item business-filter-item" data-filter="goods" onclick="showSection('businesses'); filterBusinessesByType('goods'); updateFilterUI(this); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-shopping-bag"></i></span>
                        <div>Goods</div>
                    </div>
                    <div class="sidebar-item business-filter-item" data-filter="services" onclick="showSection('businesses'); filterBusinessesByType('services'); updateFilterUI(this); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-tools"></i></span>
                        <div>Services</div>
                    </div>
                </div>
            </div>
            
            <!-- Employers Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('employers')">
                    <span class="sidebar-icon"><i class="fas fa-briefcase"></i></span>
                    <div>Employers</div>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item" onclick="showSection('job-listings'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-list-alt"></i></span>
                        <div>Job Listings</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('my-applications'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-file-alt"></i></span>
                        <div>My Applications</div>
                    </div>
                </div>
            </div>
            
            <!-- Tourist Destinations -->
            <div class="sidebar-item" onclick="showSection('destinations')">
                <span class="sidebar-icon"><i class="fas fa-map-marked-alt"></i></span>
                <div>Tourist Destinations</div>
            </div>
            
            <!-- Events & Challenges -->
            <div class="sidebar-item" onclick="showSection('events')">
                <span class="sidebar-icon"><i class="fas fa-calendar-check"></i></span>
                <div>Events & Challenges</div>
            </div>
            
            <!-- My Profile -->
            <div class="sidebar-item" onclick="showSection('profile')">
                <span class="sidebar-icon"><i class="fas fa-user-circle"></i></span>
                <div>My Profile</div>
            </div>
            
            <!-- My Groups -->
            <div class="sidebar-item" onclick="showSection('groups')">
                <span class="sidebar-icon"><i class="fas fa-users-cog"></i></span>
                <div>My Groups</div>
            </div>
            
            <!-- Admin Panel (if admin) -->
            @if($userRole === 'admin')
            <div class="sidebar-item" onclick="showSection('admin-panel')" style="background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); color: #c62828; font-weight: 600;">
                <span class="sidebar-icon"><i class="fas fa-cog"></i></span>
                <div>Admin Panel</div>
            </div>
            @endif
            
            <!-- Go Premium (if not premium) -->
            @if(!auth()->user()->is_premium)
            <div class="sidebar-item" onclick="showSection('premium')" style="background: linear-gradient(135deg, #fff9e6 0%, #ffe082 100%); color: #f57f17; font-weight: 600;">
                <span class="sidebar-icon"><i class="fas fa-crown"></i></span>
                <div>Go Premium</div>
            </div>
            @endif
        @endif
    </div>
    <div class="sidebar-footer">
        <form method="POST" action="/logout" style="width: 100%;">
            @csrf
            <button type="submit" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i> Logout
            </button>
        </form>
    </div>
</div>
