<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AuthController;
use App\Models\User;
use App\Models\Friendship;

Route::get('/', function () {
    return view('welcome');
});

// Auth routes
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('guest');

Route::get('/register', function () {
    return view('auth.register');
})->name('register')->middleware('guest');

Route::post('/register', [AuthController::class, 'register'])->middleware('guest');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/debug', function () {
        return view('debug');
    });
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        return redirect('/');
    })->name('logout');
});

// Temporary route to create test friendships - REMOVE AFTER TESTING
Route::get('/setup/create-friendships', function () {
    if (!auth()->check()) {
        return response()->json(['error' => 'Unauthorized'], 403);
    }

    $users = User::limit(5)->get();
    
    if ($users->count() < 2) {
        return response()->json(['error' => 'Not enough users'], 400);
    }

    $count = 0;
    for ($i = 0; $i < $users->count() - 1; $i++) {
        for ($j = $i + 1; $j < $users->count(); $j++) {
            Friendship::firstOrCreate(
                ['user_id' => $users[$i]->id, 'friend_id' => $users[$j]->id],
                ['status' => 'accepted']
            );
            $count++;
        }
    }

    $total = Friendship::where('status', 'accepted')->count();
    
    return response()->json([
        'success' => true,
        'message' => "Created $count test friendships",
        'total_friendships' => $total
    ]);
});
