<?php

namespace App\Repositories;

use App\Models\Business;
use App\Models\Post;
use App\Models\TouristDestination;
use App\Models\User;

class SearchRepository
{
    public function searchUsers(string $q, int $limit = 5)
    {
        return User::where('username', 'like', "%$q%")
            ->orWhere('first_name', 'like', "%$q%")
            ->orWhere('last_name', 'like', "%$q%")
            ->select('id', 'username', 'first_name', 'last_name', 'profile_picture')
            ->limit($limit)->get();
    }

    public function searchBusinesses(string $q, int $limit = 5)
    {
        return Business::where('name', 'like', "%$q%")
            ->orWhere('description', 'like', "%$q%")
            ->select('id', 'name as business_name', 'category as business_type', 'address')
            ->limit($limit)->get();
    }

    public function searchPosts(string $q, int $limit = 5)
    {
        return Post::where('content', 'like', "%$q%")->with('user')->limit($limit)->get();
    }

    public function searchDestinations(string $q, int $limit = 5)
    {
        return TouristDestination::where('name', 'like', "%$q%")
            ->orWhere('description', 'like', "%$q%")
            ->limit($limit)->get();
    }

    public function paginateUsers(string $q)
    {
        return User::where('username', 'like', "%$q%")
            ->orWhere('first_name', 'like', "%$q%")
            ->orWhere('last_name', 'like', "%$q%")
            ->select('id', 'username', 'first_name', 'last_name', 'profile_picture')
            ->paginate(15);
    }

    public function paginateBusinesses(string $q)
    {
        return Business::where('name', 'like', "%$q%")
            ->orWhere('description', 'like', "%$q%")
            ->select('id', 'name as business_name', 'category as business_type', 'address')
            ->paginate(15);
    }

    public function paginateDestinations(string $q)
    {
        return TouristDestination::where('name', 'like', "%$q%")
            ->orWhere('description', 'like', "%$q%")
            ->paginate(15);
    }

    public function paginatePosts(string $q)
    {
        return Post::where('content', 'like', "%$q%")->with('user')->paginate(15);
    }
}
