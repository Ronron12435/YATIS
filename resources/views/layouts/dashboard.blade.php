<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="user-id" content="{{ auth()->id() }}">
    <meta name="user-role" content="{{ auth()->user()->role }}">
    <meta name="user-latitude" content="{{ auth()->user()->latitude ?? '' }}">
    <meta name="user-longitude" content="{{ auth()->user()->longitude ?? '' }}">
    <meta name="user-profile-picture" content="{{ auth()->user()->profile_picture ?? '' }}">
    <meta name="user-first-name" content="{{ auth()->user()->first_name ?? '' }}">
    <meta name="user-last-name" content="{{ auth()->user()->last_name ?? '' }}">
    <title>@yield('title', 'Dashboard - YATIS')</title>
    <meta name="cache-version" content="{{ time() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.css" />
    <script src="https://unpkg.com/leaflet-routing-machine@3.2.12/dist/leaflet-routing-machine.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    @stack('styles')
    <script src="{{ asset('js/theme-toggle.js') }}"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #0f1419; display: flex; height: 100vh; overflow: hidden; }
        .menu-toggle { display: none; position: fixed; top: 15px; left: 15px; z-index: 998; background: #2d2d2d; color: white; border: none; padding: 12px 16px; border-radius: 8px; font-size: 24px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.3); }
        .navbar { background: #f3f4f6; color: #1f2937; padding: 15px 20px; display: flex; justify-content: flex-end; align-items: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); z-index: 98; position: relative; flex-shrink: 0; border-bottom: 1px solid #e5e7eb; }
        .navbar .user-info { display: flex; align-items: center; gap: 15px; flex-wrap: wrap; color: #1f2937; }
        .navbar .user-info span { color: #6b7280; }
        .navbar .user-info strong { color: #1f2937; }
        .badge { background: #00bcd4; color: white; padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: bold; box-shadow: 0 2px 6px rgba(0,188,212,0.3); white-space: nowrap; }
        .right-section { display: flex; flex-direction: column; margin-left: 280px; width: calc(100% - 280px); height: 100vh; overflow: hidden; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .right-section.sidebar-collapsed { margin-left: 80px; width: calc(100% - 80px); }
        .sidebar { width: 280px; background: #1f2937; box-shadow: 2px 0 20px rgba(0,0,0,0.3); display: flex; flex-direction: column; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); z-index: 99; border-right: 1px solid #374151; height: 100vh; position: fixed; left: 0; top: 0; }
        .sidebar.collapsed { width: 80px; }
        .sidebar-toggle { position: absolute; top: 50%; right: 16px; transform: translateY(-50%); background: none; border: none; color: #9ca3af; cursor: pointer; font-size: 20px; z-index: 100; transition: all 0.3s; padding: 8px; }
        .sidebar-toggle:hover { color: #00bcd4; }
        .sidebar.collapsed .sidebar-toggle i { transform: rotate(180deg); }
        @media (max-width: 768px) { .sidebar-toggle { display: none !important; } }
        .sidebar-header { padding: 20px 16px; border-bottom: 1px solid #374151; background: linear-gradient(135deg, #1f2937 0%, #111827 100%); display: flex; justify-content: center; align-items: center; position: relative; }
        .sidebar-logo-text { font-size: 28px; font-weight: 700; color: #ffffff; letter-spacing: 2px; text-transform: uppercase; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3); transition: all 0.3s; }
        .sidebar.collapsed .sidebar-logo-text { font-size: 0; width: 0; overflow: hidden; }
        .sidebar-content { flex: 1; overflow-y: auto; overflow-x: hidden; padding: 8px 0; }
        .sidebar-footer { padding: 16px; border-top: 1px solid #3a3a3a; background: linear-gradient(135deg, #242424 0%, #1a1a1a 100%); }
        .logout-btn { width: 100%; background: linear-gradient(135deg, #3a3a3a 0%, #2a2a2a 100%); color: #ff5252; border: 1px solid #4a4a4a; padding: 12px 16px; border-radius: 8px; cursor: pointer; font-weight: 600; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); font-size: 14px; display: flex; align-items: center; justify-content: center; gap: 8px; }
        .logout-btn:hover { background: linear-gradient(135deg, #ff5252 0%, #ff1744 100%); color: white; border-color: #ff5252; transform: translateY(-2px); box-shadow: 0 6px 16px rgba(255,82,82,0.4); }

        .premium-btn { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52 !important; font-weight: 600; margin: 10px 8px; box-shadow: 0 4px 12px rgba(255, 215, 0, 0.4); transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border-left-color: #ffd700 !important; }
        .premium-btn:hover { background: linear-gradient(135deg, #ffed4e 0%, #ffd700 100%); transform: translateY(-3px); box-shadow: 0 6px 16px rgba(255, 215, 0, 0.5); }
        .sidebar-item { padding: 14px 18px; margin: 4px 8px; cursor: pointer; transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1); display: flex; align-items: center; gap: 14px; color: #d1d5db; font-weight: 500; font-size: 15px; border-radius: 10px; position: relative; border-left: 3px solid transparent; }
        .sidebar-item:hover { background: rgba(0, 188, 212, 0.12); color: #ffffff; border-left-color: #00bcd4; transform: translateX(4px); }
        .sidebar-item.active { background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); font-weight: 600; color: #ffffff; box-shadow: 0 4px 12px rgba(0, 188, 212, 0.4); border-left-color: #ffffff; }
        .sidebar-item > div { transition: all 0.3s; white-space: nowrap; }
        .sidebar.collapsed .sidebar-item > div:not(.sidebar-icon) { width: 0; overflow: hidden; opacity: 0; }
        .sidebar.collapsed .sidebar-item { padding: 14px; justify-content: center; margin: 4px 4px; gap: 0; }
        .sidebar-item-parent > .sidebar-item::after { content: '›'; font-size: 16px; margin-left: auto; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: #606060; font-weight: bold; }
        .sidebar.collapsed .sidebar-item-parent > .sidebar-item::after { display: none; }
        .sidebar.collapsed .sidebar-dropdown { display: none !important; }
        .sidebar.collapsed .logout-btn { padding: 12px; font-size: 0; }
        .sidebar.collapsed .logout-btn i { font-size: 18px; }
        .sidebar-item.active::before { content: ''; position: absolute; left: 0; top: 50%; transform: translateY(-50%); width: 3px; height: 60%; background: #42a5f5; border-radius: 0 3px 3px 0; display: none; }
        .sidebar-item .sidebar-icon { font-size: 20px; width: 24px; text-align: center; display: flex; align-items: center; justify-content: center; transition: all 0.25s; }
        .sidebar-item:hover .sidebar-icon { transform: scale(1.1); }
        .sidebar-item.active .sidebar-icon { transform: scale(1.15); }
        .notification-badge { background: linear-gradient(135deg, #ff5252 0%, #ff1744 100%); color: white; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: bold; margin-left: auto; box-shadow: 0 2px 8px rgba(255,82,82,0.5); animation: pulse 2s infinite; }
        .sidebar.collapsed .notification-badge { display: none; }
        @keyframes pulse { 0%, 100% { box-shadow: 0 2px 8px rgba(255,82,82,0.5); } 50% { box-shadow: 0 2px 12px rgba(255,82,82,0.8); } }
        .sidebar-item-parent { position: relative; }
        .sidebar-dropdown { display: none; background: rgba(36, 36, 36, 0.8); border-radius: 8px; margin: 4px 8px 8px 8px; padding: 4px 0; border: 1px solid #3a3a3a; backdrop-filter: blur(10px); animation: slideDown 0.2s ease; }
        .sidebar.collapsed .sidebar-dropdown { display: none !important; }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-8px); } to { opacity: 1; transform: translateY(0); } }
        .sidebar-item-parent:hover .sidebar-dropdown { display: block; }
        .sidebar-dropdown .sidebar-item { padding: 10px 16px 10px 44px; font-size: 13px; margin: 2px 4px; color: #909090; border-left: none; }
        .sidebar-dropdown .sidebar-item:hover { background: rgba(25, 118, 210, 0.15); color: #ffffff; border-left-color: #42a5f5; }
        .sidebar-dropdown .sidebar-item.active { background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%); color: #ffffff; }
        .sidebar-item-parent > .sidebar-item::after { content: '›'; font-size: 16px; margin-left: auto; transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1); color: #606060; font-weight: bold; }
        .sidebar-item-parent:hover > .sidebar-item::after { transform: rotate(90deg); color: #b0b0b0; }
        .content { flex: 1; overflow-y: auto; padding: 30px; background: #f9fafb; width: 100%; min-height: 100vh; }
        .content-section { display: none; }
        .content-section.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .page-title { font-size: 32px; color: #1a3a52; margin-bottom: 28px; font-weight: 700; display: flex; align-items: center; gap: 12px; letter-spacing: -0.5px; }
        .page-title i { color: #00bcd4; font-size: 36px; }
        .card { background: white; padding: 28px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 28px; border: 1px solid #f0f0f0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); position: relative; overflow: hidden; }
        .card::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 3px; background: linear-gradient(90deg, #00bcd4 0%, #00acc1 100%); }
        .card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .card h3 { color: #1a3a52; margin-bottom: 16px; font-weight: 700; font-size: 18px; display: flex; align-items: center; gap: 10px; }
        .card h3 i { color: #00bcd4; font-size: 20px; }
        .card p { color: #666; line-height: 1.8; font-size: 14px; }
        .profile-header { background: linear-gradient(135deg, #1a3a52 0%, #2c5f8d 50%, #00bcd4 100%); padding: 48px 36px; border-radius: 16px; margin-bottom: 32px; box-shadow: 0 8px 32px rgba(26, 58, 82, 0.15); display: flex; align-items: center; gap: 32px; position: relative; overflow: hidden; }
        .profile-header::before { content: ''; position: absolute; top: -50%; right: -10%; width: 400px; height: 400px; background: rgba(0, 188, 212, 0.1); border-radius: 50%; }
        .profile-header::after { content: ''; position: absolute; bottom: -30%; left: -5%; width: 300px; height: 300px; background: rgba(255, 255, 255, 0.05); border-radius: 50%; }
        .profile-avatar { position: relative; z-index: 1; }
        .avatar-circle { width: 120px; height: 120px; border-radius: 50%; background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white; display: flex; align-items: center; justify-content: center; font-size: 48px; font-weight: 700; box-shadow: 0 8px 24px rgba(0, 188, 212, 0.4); border: 5px solid rgba(255, 255, 255, 0.3); }
        .profile-info { flex: 1; position: relative; z-index: 1; }
        .profile-name { color: white; font-size: 36px; font-weight: 700; margin: 0 0 12px 0; text-shadow: 0 2px 8px rgba(0, 0, 0, 0.2); letter-spacing: -0.5px; }
        .profile-badges { display: flex; gap: 12px; flex-wrap: wrap; }
        .badge-role { background: rgba(255, 255, 255, 0.25); color: white; backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-premium { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #1a3a52; box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .badge-free { background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); padding: 6px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 5px; }
        .job-card { background: white; padding: 24px; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 16px; border: 1px solid #f0f0f0; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); }
        .job-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 8px 24px rgba(0,0,0,0.08); transform: translateY(-2px); }
        .job-header { display: flex; justify-content: space-between; align-items: start; margin-bottom: 16px; }
        .job-header h4 { color: #00bcd4; margin: 0; font-size: 16px; font-weight: 700; }
        .job-meta { display: flex; gap: 16px; flex-wrap: wrap; margin: 12px 0; font-size: 13px; color: #666; }
        .job-meta span { display: flex; align-items: center; gap: 6px; }
        .badge-status { display: inline-block; padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .badge-pending { background: #f39c12; color: white; }
        .badge-reviewed { background: #3498db; color: white; }
        .badge-accepted { background: #2ecc71; color: white; }
        .badge-rejected { background: #e74c3c; color: white; }
        .badge-job-open { background: #2ecc71; color: white; }
        .badge-job-closed { background: #95a5a6; color: white; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 28px; border-radius: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.08), 0 4px 12px rgba(0,0,0,0.05); text-align: center; color: #333; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); border: 1px solid #f0f0f0; position: relative; overflow: hidden; }
        .stat-card::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200px; height: 200px; background: radial-gradient(circle, rgba(0, 188, 212, 0.1) 0%, transparent 70%); border-radius: 50%; }
        .stat-card:hover { transform: translateY(-6px); box-shadow: 0 2px 8px rgba(0,0,0,0.1), 0 12px 32px rgba(0,0,0,0.08); }
        .stat-card h3 { color: #00bcd4; font-size: 48px; margin-bottom: 12px; font-weight: 700; position: relative; z-index: 1; }
        .stat-card p { color: #666; font-weight: 500; font-size: 14px; position: relative; z-index: 1; }
        .btn { padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; }
        .btn-primary { background: linear-gradient(135deg, #00bcd4 0%, #00acc1 100%); color: white; box-shadow: 0 4px 12px rgba(0, 188, 212, 0.3); }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 188, 212, 0.4); }
        @media (max-width: 768px) { .menu-toggle { display: block !important; } .right-section { margin-left: 0; width: 100%; } .sidebar { position: fixed; left: 0; top: 0; height: 100vh; transform: translateX(-100%); z-index: 1000; } .sidebar.active { transform: translateX(0); } .content { padding: 60px 15px 15px 15px !important; width: 100%; } .page-title { font-size: 24px !important; } .stats { grid-template-columns: 1fr !important; } }
        
        /* Dark Mode Styles - Eye-Friendly */
        body.dark-mode { background: #2d2d2d; }
        body.dark-mode .navbar { background: #2d2d2d; color: #e8eaed; }
        body.dark-mode .navbar .user-info { color: #e8eaed; }
        body.dark-mode .navbar .user-info span { color: #9aa0a6; }
        body.dark-mode .navbar .user-info strong { color: #e8eaed; }
        body.dark-mode .content { background: #2d2d2d; }
        body.dark-mode #dashboard h1 { color: #ffffff !important; }
        body.dark-mode .card { background: #1a1f2e; color: #e8eaed; box-shadow: 0 2px 12px rgba(0,0,0,0.5); border-top-color: #4db8d4; }
        body.dark-mode .card h3 { color: #4db8d4; }
        body.dark-mode .card p { color: #c5cad1; }
        body.dark-mode .card h2 { color: #e8eaed; }
        body.dark-mode .card h4 { color: #e8eaed; }
        body.dark-mode .card label { color: #c5cad1; }
        body.dark-mode .page-title { color: #4db8d4; }
        body.dark-mode .profile-header { background: linear-gradient(135deg, #1a3a52 0%, #0d1f2d 50%, #4db8d4 100%); }
        body.dark-mode .profile-name { color: #e8eaed; }
        body.dark-mode .stat-card { background: linear-gradient(135deg, #1a2a3a 0%, #1a3a52 100%); }
        body.dark-mode .stat-card h3 { color: #4db8d4; }
        body.dark-mode .stat-card p { color: #c5cad1; }
        body.dark-mode .job-card { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode .job-header h4 { color: #4db8d4; }
        body.dark-mode .job-meta { color: #c5cad1; }
        body.dark-mode input, body.dark-mode textarea, body.dark-mode select { background: #0f1419; color: #e8eaed; border-color: #3a4150; }
        body.dark-mode input::placeholder, body.dark-mode textarea::placeholder { color: #7a8089; }
        body.dark-mode input:focus, body.dark-mode textarea:focus, body.dark-mode select:focus { background: #1a1f2e; border-color: #4db8d4; outline: none; }
        body.dark-mode button { color: #e8eaed; }
        body.dark-mode .btn { background: #1a3a52; color: #e8eaed; border-color: #3a4150; }
        body.dark-mode .btn:hover { background: #2c5f8d; }
        body.dark-mode table { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode table th { background: #0f1419; color: #4db8d4; border-color: #3a4150; }
        body.dark-mode table td { border-color: #3a4150; }
        body.dark-mode table tr:hover { background: #262d3a; }
        body.dark-mode .modal { background: rgba(0, 0, 0, 0.8); }
        body.dark-mode .modal-content { background: #1a1f2e; color: #e8eaed; }
        body.dark-mode .modal-header { background: #0f1419; border-color: #3a4150; }
        body.dark-mode .modal-footer { background: #0f1419; border-color: #3a4150; }
        body.dark-mode .alert { background: #262d3a; color: #e8eaed; border-color: #3a4150; }
        body.dark-mode .alert-info { background: #1a3a52; border-color: #4db8d4; }
        body.dark-mode .alert-success { background: #1a3a2a; border-color: #2ecc71; }
        body.dark-mode .alert-warning { background: #3a3a1a; border-color: #f39c12; }
        body.dark-mode .alert-danger { background: #3a1a1a; border-color: #e74c3c; }
        
        /* Dark mode for white boxes and info containers */
        body.dark-mode [style*="background: white"] { background-color: #1a1f2e !important; color: #e8eaed !important; }
        body.dark-mode [style*="background:white"] { background-color: #1a1f2e !important; color: #e8eaed !important; }
        body.dark-mode [style*="background: #fff"] { background-color: #1a1f2e !important; color: #e8eaed !important; }
        body.dark-mode [style*="background:#fff"] { background-color: #1a1f2e !important; color: #e8eaed !important; }
        body.dark-mode [style*="background: linear-gradient(135deg, #e8f5e9"] { background: linear-gradient(135deg, #1a3a2a 0%, #1a2a2e 100%) !important; }
        body.dark-mode [style*="background: #fff3cd"] { background-color: #3a3a1a !important; color: #f39c12 !important; border-left-color: #f39c12 !important; }
        body.dark-mode [style*="background:#fff3cd"] { background-color: #3a3a1a !important; color: #f39c12 !important; border-left-color: #f39c12 !important; }
        body.dark-mode [style*="background: #f8f9fa"] { background-color: #262d3a !important; color: #c5cad1 !important; }
        body.dark-mode [style*="background:#f8f9fa"] { background-color: #262d3a !important; color: #c5cad1 !important; }
        body.dark-mode [style*="background: #f0f4ff"] { background-color: rgba(52, 152, 219, 0.15) !important; }
        body.dark-mode [style*="color: #555"] { color: #c5cad1 !important; }
        body.dark-mode [style*="color:#555"] { color: #c5cad1 !important; }
        body.dark-mode [style*="color: #666"] { color: #9aa0a6 !important; }
        body.dark-mode [style*="color:#666"] { color: #9aa0a6 !important; }
        body.dark-mode [style*="color: #888"] { color: #7a8089 !important; }
        body.dark-mode [style*="color:#888"] { color: #7a8089 !important; }
        body.dark-mode [style*="color: #999"] { color: #7a8089 !important; }
        body.dark-mode [style*="color:#999"] { color: #7a8089 !important; }
        body.dark-mode [style*="color: #1a3a52"] { color: #4db8d4 !important; }
        body.dark-mode [style*="color:#1a3a52"] { color: #4db8d4 !important; }
        body.dark-mode [style*="color: #e74c3c"] { color: #ff6b5b !important; }
        body.dark-mode [style*="color:#e74c3c"] { color: #ff6b5b !important; }
        body.dark-mode [style*="border: 2px solid #00bcd4"] { border-color: #00bcd4 !important; }
        body.dark-mode [style*="border:2px solid #00bcd4"] { border-color: #00bcd4 !important; }
        body.dark-mode div[style*="background: white; padding"] { background-color: #1a1f2e !important; }
        body.dark-mode div[style*="background:white; padding"] { background-color: #1a1f2e !important; }
        
        /* Dark mode for business filter items when selected */
        body.dark-mode .business-filter-item[style*="background: #e3f2fd"] { background-color: #1a3a52 !important; }
    </style>
</head>
<body>
    <button class="menu-toggle" onclick="toggleMobileMenu()"><i class="fas fa-bars"></i></button>
    @include('components.sidebar')
    
    <div class="right-section">
        <div class="content">
            @yield('content')
        </div>
    </div>



    <script>
        window.showSection = function(sectionId) {
            document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
            document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
            const target = document.getElementById(sectionId);
            if(target) target.classList.add('active');
            event.target.closest('.sidebar-item')?.classList.add('active');
            
            // Save active section to sessionStorage (cleared on browser close)
            sessionStorage.setItem('activeSection', sectionId);
            
            // If closing chat, clear chat state
            if (sectionId !== 'people') {
                sessionStorage.removeItem('activeChatUserId');
                sessionStorage.removeItem('activeChatName');
                sessionStorage.removeItem('activeChatInitials');
            }
            
            if(sectionId === 'dashboard') setTimeout(() => initDashboardMap(), 100);
            if(sectionId === 'businesses') setTimeout(() => { if(typeof initBusinessesMap === 'function') initBusinessesMap(); }, 100);
            if(sectionId === 'people') setTimeout(() => initPeopleMap(), 150);
            if(sectionId === 'my-friends') setTimeout(() => { if(typeof loadFriendsList === 'function') loadFriendsList(); }, 100);
            if(sectionId === 'friend-requests') setTimeout(() => { if(typeof loadFriendRequests === 'function') loadFriendRequests(); }, 100);
            if(sectionId === 'messages') setTimeout(() => { if(typeof loadConversations === 'function') loadConversations(); }, 100);
            if(sectionId === 'employers') setTimeout(() => { if(typeof EmployersModule !== 'undefined') EmployersModule.init(); if(typeof loadStats === 'function') loadStats(); }, 100);
            if(sectionId === 'jobs') setTimeout(() => { if(typeof JobsModule !== 'undefined') JobsModule.init(); }, 100);
            if(sectionId === 'job-listings') setTimeout(() => { if(typeof loadJobListings === 'function') loadJobListings(); }, 100);
            if(sectionId === 'my-applications') setTimeout(() => { if(typeof loadMyApplications === 'function') loadMyApplications(); }, 100);
            if(sectionId === 'destinations') setTimeout(() => { if(typeof initDestinationsSection === 'function') initDestinationsSection(); }, 100);
            if(sectionId === 'profile') setTimeout(() => { if(typeof initProfileSection === 'function') initProfileSection(); }, 100);
            if(sectionId === 'my-business') setTimeout(() => { if(typeof initMyBusinessSection === 'function') initMyBusinessSection(); }, 100);
            if(sectionId === 'admin-panel') setTimeout(() => { if(typeof AdminModule !== 'undefined') AdminModule.init(); }, 100);
            if(sectionId === 'events') setTimeout(() => { if(typeof EventsModule !== 'undefined') EventsModule.init(); }, 100);
            if(sectionId === 'groups') setTimeout(() => { if(typeof initGroupsSection === 'function') initGroupsSection(); }, 100);
        };

        // Restore active section on page load - use sessionStorage for refresh, dashboard for fresh login
        document.addEventListener('DOMContentLoaded', function() {
            // Only restore section if this is a same-tab page refresh (not a fresh navigation)
            const isRefresh = sessionStorage.getItem('pageLoaded') === 'true';
            const activeSection = isRefresh ? (sessionStorage.getItem('activeSection') || 'dashboard') : 'dashboard';
            sessionStorage.setItem('pageLoaded', 'true');
            const sectionElement = document.getElementById(activeSection);
            const sidebarItem = document.querySelector(`[onclick="showSection('${activeSection}')"]`);
            
            if(sectionElement) {
                document.querySelectorAll('.content-section').forEach(s => s.classList.remove('active'));
                document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
                sectionElement.classList.add('active');
                if(sidebarItem) sidebarItem.classList.add('active');
                
                // Initialize the section
                if(activeSection === 'dashboard') setTimeout(() => initDashboardMap(), 100);
                if(activeSection === 'businesses') setTimeout(() => { if(typeof initBusinessesMap === 'function') initBusinessesMap(); }, 100);
                if(activeSection === 'people') setTimeout(() => initPeopleMap(), 150);
                if(activeSection === 'my-friends') setTimeout(() => { if(typeof loadFriendsList === 'function') loadFriendsList(); }, 100);
                if(activeSection === 'friend-requests') setTimeout(() => { if(typeof loadFriendRequests === 'function') loadFriendRequests(); }, 100);
                if(activeSection === 'messages') setTimeout(() => { if(typeof loadConversations === 'function') loadConversations(); }, 100);
                if(activeSection === 'employers') setTimeout(() => { if(typeof EmployersModule !== 'undefined') EmployersModule.init(); if(typeof loadStats === 'function') loadStats(); }, 100);
                if(activeSection === 'jobs') setTimeout(() => { if(typeof JobsModule !== 'undefined') JobsModule.init(); }, 100);
                if(activeSection === 'job-listings') setTimeout(() => { if(typeof loadJobListings === 'function') loadJobListings(); }, 100);
                if(activeSection === 'my-applications') setTimeout(() => { if(typeof loadMyApplications === 'function') loadMyApplications(); }, 100);
                if(activeSection === 'destinations') setTimeout(() => { if(typeof initDestinationsSection === 'function') initDestinationsSection(); }, 100);
                if(activeSection === 'admin-panel') setTimeout(() => { if(typeof AdminModule !== 'undefined') AdminModule.init(); }, 100);
                if(activeSection === 'events') setTimeout(() => { if(typeof EventsModule !== 'undefined') EventsModule.init(); }, 100);
                if(activeSection === 'profile') setTimeout(() => { if(typeof initProfileSection === 'function') initProfileSection(); }, 100);
                if(activeSection === 'my-business') setTimeout(() => { if(typeof initMyBusinessSection === 'function') initMyBusinessSection(); }, 100);
                if(activeSection === 'groups') setTimeout(() => { if(typeof initGroupsSection === 'function') initGroupsSection(); }, 100);
            }
        });

        window.toggleMobileMenu = function() {
            document.querySelector('.sidebar')?.classList.toggle('active');
        };

        window.closeMobileMenu = function() {
            document.querySelector('.sidebar')?.classList.remove('active');
        };

        // Close menu when clicking outside on mobile
        document.addEventListener('click', function(event) {
            const sidebar = document.querySelector('.sidebar');
            const menuToggle = document.querySelector('.menu-toggle');
            
            if (sidebar && menuToggle && window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuToggle.contains(event.target)) {
                    sidebar.classList.remove('active');
                }
            }
        });

        // Close menu when a sidebar item is clicked on mobile
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    document.querySelector('.sidebar')?.classList.remove('active');
                }
            });
        });

        // Badge polling — unread messages + pending friend requests
        let badgeUpdateInProgress = false;
        
        function updateBadges() {
            if (badgeUpdateInProgress) return; // Prevent overlapping requests
            badgeUpdateInProgress = true;
            
            let unreadCount = 0;
            let friendRequestCount = 0;
            let completed = 0;
            
            // Unread messages badge
            fetch('/api/messages/unread/count', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    // API response structure: { success, message, data: { unread_count } }
                    unreadCount = data?.data?.unread_count || 0;
                    const badge = document.getElementById('unread-msg-badge');
                    if (badge) { 
                        badge.textContent = unreadCount; 
                        badge.style.display = unreadCount > 0 ? 'inline-flex' : 'none'; 
                    }
                    completed++;
                    if (completed === 2) updatePeopleBadge(unreadCount, friendRequestCount);
                }).catch(() => {
                    completed++;
                    if (completed === 2) updatePeopleBadge(0, friendRequestCount);
                });

            // Friend requests badge
            fetch('/api/friends/requests', { credentials: 'include' })
                .then(r => r.json())
                .then(data => {
                    const requests = data?.data || [];
                    friendRequestCount = Array.isArray(requests) ? requests.length : 0;
                    const badge = document.getElementById('friend-req-badge');
                    if (badge) {
                        badge.textContent = friendRequestCount;
                        badge.style.display = friendRequestCount > 0 ? 'inline-flex' : 'none';
                    }
                    completed++;
                    if (completed === 2) updatePeopleBadge(unreadCount, friendRequestCount);
                }).catch(() => {
                    completed++;
                    if (completed === 2) updatePeopleBadge(unreadCount, 0);
                });
        }

        // Helper function to update People badge with combined count
        function updatePeopleBadge(unreadCount, friendRequestCount) {
            const peopleBadge = document.getElementById('people-badge');
            if (!peopleBadge) return;
            
            const totalCount = (unreadCount || 0) + (friendRequestCount || 0);
            
            if (totalCount > 0) {
                peopleBadge.textContent = totalCount;
                peopleBadge.style.display = 'inline-flex';
            } else {
                peopleBadge.style.display = 'none';
            }
            
            badgeUpdateInProgress = false;
        }

        // Start badge polling on page load
        document.addEventListener('DOMContentLoaded', function() {
            updateBadges();
            // Poll every 2 seconds for badge updates
            setInterval(updateBadges, 2000);
        });

        let dashboardMap = null;
        function initDashboardMap() {
            const container = document.getElementById('dashboard-map-container');
            if(!container || dashboardMap) return;
            
            // Create Leaflet map centered on Sagay City
            dashboardMap = L.map('dashboard-map-container', { zoomControl: false }).setView([10.8967, 123.4253], 13);
            
            // Add OpenStreetMap tiles
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors',
                maxZoom: 19
            }).addTo(dashboardMap);
            
            // Get user's real-time GPS location
            if(navigator.geolocation) {
                function placeUserMarker(lat, lng) {
                    // Share coordinates globally so other sections (e.g. destinations map) stay in sync
                    window.currentUserLat = lat;
                    window.currentUserLng = lng;
                    if(window.userLocationMarker) dashboardMap.removeLayer(window.userLocationMarker);
                    if(window.userLocationAccuracy) dashboardMap.removeLayer(window.userLocationAccuracy);

                    // Accuracy circle
                    window.userLocationAccuracy = L.circle([lat, lng], {
                        radius: 50,
                        color: '#00bcd4',
                        fillColor: '#00bcd4',
                        fillOpacity: 0.15,
                        weight: 1
                    }).addTo(dashboardMap);

                    // Cyan dot marker
                    window.userLocationMarker = L.circleMarker([lat, lng], {
                        radius: 10,
                        fillColor: '#00bcd4',
                        color: 'white',
                        weight: 3,
                        opacity: 1,
                        fillOpacity: 1
                    }).addTo(dashboardMap).bindPopup('<b>You are here</b>');

                    dashboardMap.setView([lat, lng], 15);
                }

                // First call to trigger browser permission prompt
                navigator.geolocation.getCurrentPosition(
                    function(pos) {
                        placeUserMarker(pos.coords.latitude, pos.coords.longitude);
                        navigator.geolocation.watchPosition(
                            function(pos) { placeUserMarker(pos.coords.latitude, pos.coords.longitude); },
                            function(err) { console.warn('Watch error:', err.message); },
                            { enableHighAccuracy: true, maximumAge: 0 }
                        );
                    },
                    function(err) {
                        // GPS blocked (http:// or denied) — use saved DB location
                        @if(auth()->user()->latitude && auth()->user()->longitude)
                            placeUserMarker({{ auth()->user()->latitude }}, {{ auth()->user()->longitude }});
                        @else
                            // No DB location — try IP fallback
                            fetch('https://ipapi.co/json/')
                                .then(r => r.json())
                                .then(d => { if(d.latitude) placeUserMarker(d.latitude, d.longitude); })
                                .catch(() => {});
                        @endif
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            } else {
                @if(auth()->user()->latitude && auth()->user()->longitude)
                    placeUserMarker({{ auth()->user()->latitude }}, {{ auth()->user()->longitude }});
                @else
                    fetch('https://ipapi.co/json/')
                        .then(r => r.json())
                        .then(d => { if(d.latitude) placeUserMarker(d.latitude, d.longitude); })
                        .catch(() => {});
                @endif
            }
        }
        
        // Initialize dashboard map on page load
        document.addEventListener('DOMContentLoaded', initDashboardMap);

        window.locateMe = function() {
            const btn = document.getElementById('locate-btn');
            if(btn) { btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Locating...'; btn.disabled = true; }

            function placeAndSave(lat, lng) {
                // Share coordinates globally so other sections (e.g. destinations map) stay in sync
                window.currentUserLat = lat;
                window.currentUserLng = lng;
                if(dashboardMap) {
                    if(window.userLocationMarker) dashboardMap.removeLayer(window.userLocationMarker);
                    if(window.userLocationAccuracy) dashboardMap.removeLayer(window.userLocationAccuracy);
                    window.userLocationAccuracy = L.circle([lat, lng], {
                        radius: 100, color: '#00bcd4', fillColor: '#00bcd4', fillOpacity: 0.15, weight: 1
                    }).addTo(dashboardMap);
                    window.userLocationMarker = L.circleMarker([lat, lng], {
                        radius: 12, fillColor: '#00bcd4', color: 'white', weight: 3, opacity: 1, fillOpacity: 1
                    }).addTo(dashboardMap).bindPopup('<b>📍 You are here</b>').openPopup();
                    dashboardMap.setView([lat, lng], 16);
                }
                fetch('/api/profile/update-location', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content, 'Content-Type': 'application/json' },
                    body: JSON.stringify({ latitude: lat, longitude: lng })
                });
                if(btn) { btn.innerHTML = '<i class="fas fa-check"></i> Located'; btn.style.background = '#27ae60'; btn.disabled = false; }
            }

            // Try GPS first
            if(navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function(pos) { placeAndSave(pos.coords.latitude, pos.coords.longitude); },
                    function() {
                        // GPS blocked — fall back to IP geolocation (no permission needed)
                        fetch('https://ipapi.co/json/')
                            .then(r => r.json())
                            .then(data => {
                                if(data.latitude && data.longitude) {
                                    placeAndSave(data.latitude, data.longitude);
                                    if(btn) { btn.innerHTML = '<i class="fas fa-map-marker-alt"></i> Located (IP)'; btn.style.background = '#f39c12'; btn.disabled = false; }
                                }
                            })
                            .catch(() => {
                                if(btn) { btn.innerHTML = '<i class="fas fa-crosshairs"></i> Locate Me'; btn.disabled = false; }
                            });
                    },
                    { enableHighAccuracy: true, timeout: 8000 }
                );
            } else {
                // No GPS support — use IP geolocation
                fetch('https://ipapi.co/json/')
                    .then(r => r.json())
                    .then(data => { if(data.latitude) placeAndSave(data.latitude, data.longitude); });
            }
        };
    </script>

    <!-- Load all module scripts globally -->
    <script src="{{ asset('js/sidebar-toggle.js') }}"></script>
    <script src="{{ asset('js/sidebar-location-icon.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/jobs.js') }}"></script>
    <script src="{{ asset('js/messages.js') }}"></script>
    <script src="{{ asset('js/events.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/groups.js') }}"></script>
    <script src="{{ asset('js/people.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/profile.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/my-business.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/businesses.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/business-management.js') }}?v={{ microtime(true) }}"></script>
    <script src="{{ asset('js/online-status.js') }}?v={{ time() }}"></script>
    @if(auth()->user()->role === 'admin')
    <script src="{{ asset('js/admin.js') }}?v={{ time() }}"></script>
    @endif
    <script src="{{ asset('js/destinations.js') }}?v={{ time() }}"></script>
    <script src="{{ asset('js/employers.js') }}?v={{ time() }}"></script>

    @stack('scripts')
    
    <!-- Force reload businesses.js with inline version -->
    <script>
        // This ensures the latest version is always loaded
        fetch('{{ asset("js/businesses.js") }}?v={{ microtime(true) }}')
            .then(r => r.text())
            .then(code => {
                eval(code);
                console.log('✅ Businesses.js reloaded from server');
            })
            .catch(err => console.error('Failed to reload businesses.js:', err));
    </script>
</body>
</html>
