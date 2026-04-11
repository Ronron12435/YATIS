<div class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-logo-container" style="display: flex; align-items: center; justify-content: center; gap: 3px; width: 100%;">
            <img src="{{ asset('images/yatis-logo.png') }}" alt="YATIS Logo" style="height: 70px; width: 100px; object-fit: contain; flex-shrink: 0;">
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
            <div class="sidebar-item" onclick="showSection('dashboard')">
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
                @if(isset($stats['pending_applications']) && $stats['pending_applications'] > 0)
                <span class="notification-badge">{{ $stats['pending_applications'] }}</span>
                @endif
            </div>
            
            <!-- My Profile -->
            <div class="sidebar-item" onclick="showSection('profile')">
                <span class="sidebar-icon">
                    @if(auth()->user()->profile_picture)
                        <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}?t={{ time() }}" alt="Profile" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
                    @else
                        <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
                    @endif
                </span>
                <div>My Profile</div>
            </div>

        <!-- REGULAR USER MENU -->
        @else
            <!-- Dashboard -->
            <div class="sidebar-item" onclick="showSection('dashboard')">
                <span class="sidebar-icon"><i class="fas fa-home"></i></span>
                <div>Dashboard</div>
            </div>
            
            <!-- People Dropdown -->
            <div class="sidebar-item-parent">
                <div class="sidebar-item" onclick="showSection('people')">
                    <span class="sidebar-icon" id="people-icon"><i class="fas fa-users"></i></span>
                    <div>People</div>
                    <span class="notification-badge" id="people-badge" style="display: none;">0</span>
                </div>
                <div class="sidebar-dropdown">
                    <div class="sidebar-item" onclick="showSection('my-friends'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-user-friends"></i></span>
                        <div>My Friends</div>
                    </div>
                    <div class="sidebar-item" onclick="showSection('friend-requests'); event.stopPropagation();">
                        <span class="sidebar-icon"><i class="fas fa-user-plus"></i></span>
                        <div>Friend Requests</div>
                        <span class="notification-badge" id="friend-req-badge" style="display: none;">0</span>
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
                <span class="sidebar-icon">
                    @if(auth()->user()->profile_picture)
                        <img src="{{ asset('storage/' . auth()->user()->profile_picture) }}?t={{ time() }}" alt="Profile" style="width: 28px; height: 28px; border-radius: 50%; object-fit: cover;">
                    @else
                        <div style="width: 28px; height: 28px; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 14px;">{{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}</div>
                    @endif
                </span>
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
            
            <!-- Go Premium (if not premium and not admin) -->
            @if(!auth()->user()->is_premium && $userRole !== 'admin')
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
