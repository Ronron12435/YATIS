<?php

namespace App\Http\Controllers;

use App\Repositories\FriendshipRepository;
use App\Repositories\UserRepository;

class PeopleController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private FriendshipRepository $friendshipRepository,
    ) {}

    public function getAllUsers()
    {
        $response = $this->userRepository->search(null, 'user');
        return response()->json(['success' => true, 'data' => $response]);
    }

    public function getFriendsCount()
    {
        $userId = auth()->id();

        $data = [
            'friends' => count($this->friendshipRepository->getFriends($userId)),
            'pending' => $this->friendshipRepository->getPendingRequests($userId)->total(),
        ];

        return response()->json(['success' => true, 'data' => $data]);
    }
}
