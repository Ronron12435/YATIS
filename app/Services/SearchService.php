<?php

namespace App\Services;

use App\Repositories\SearchRepository;
use App\Responses\ApiResponse;

class SearchService
{
    public function __construct(private SearchRepository $searchRepository) {}

    public function search(string $q, string $type): ApiResponse
    {
        $results = [];

        if (in_array($type, ['users', 'all'])) {
            $results['users'] = $this->searchRepository->searchUsers($q);
        }

        if (in_array($type, ['businesses', 'all'])) {
            $results['businesses'] = $this->searchRepository->searchBusinesses($q);
        }

        if (in_array($type, ['posts', 'all'])) {
            $results['posts'] = $this->searchRepository->searchPosts($q);
        }

        if (in_array($type, ['destinations', 'all'])) {
            $results['destinations'] = $this->searchRepository->searchDestinations($q);
        }

        return new ApiResponse(true, $results, 'Success');
    }

    public function searchUsers(string $q): ApiResponse
    {
        return new ApiResponse(true, $this->searchRepository->paginateUsers($q), 'Success');
    }

    public function searchBusinesses(string $q): ApiResponse
    {
        return new ApiResponse(true, $this->searchRepository->paginateBusinesses($q), 'Success');
    }

    public function searchDestinations(string $q): ApiResponse
    {
        return new ApiResponse(true, $this->searchRepository->paginateDestinations($q), 'Success');
    }

    public function searchPosts(string $q): ApiResponse
    {
        return new ApiResponse(true, $this->searchRepository->paginatePosts($q), 'Success');
    }
}
