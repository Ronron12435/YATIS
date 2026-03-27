<?php

namespace App\Http\Controllers;

use App\Services\FriendshipService;
use Illuminate\Http\Request;

class FriendshipController extends Controller
{
    public function __construct(private FriendshipService $friendshipService) {}

    public function index(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->friendshipService->getFriends(auth()->id());
        \Log::info('Friends API called for user ' . auth()->id(), ['response' => $response->toArray()]);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function requests(Request $request)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->friendshipService->getRequests(auth()->id());
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function add(Request $request, $userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        try {
            $response = $this->friendshipService->add($request->user()->id, (int) $userId);
            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error sending friend request: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function accept(Request $request, $userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        try {
            $response = $this->friendshipService->accept(auth()->id(), (int) $userId);
            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error accepting friend request: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function reject(Request $request, $userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        try {
            $response = $this->friendshipService->reject(auth()->id(), (int) $userId);
            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error rejecting friend request: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function remove(Request $request, $userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        try {
            $response = $this->friendshipService->remove(auth()->id(), (int) $userId);
            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error removing friend: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function block(Request $request, $userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        try {
            $response = $this->friendshipService->block(auth()->id(), (int) $userId);
            return response()->json($response->toArray(), $response->statusCode);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error blocking user: ' . $e->getMessage(),
                'data' => null
            ], 500);
        }
    }

    public function status(Request $request, $userId)
    {
        if (!auth()->check()) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }
        $response = $this->friendshipService->getStatus(auth()->id(), (int) $userId);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
