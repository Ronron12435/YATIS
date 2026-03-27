<?php

namespace App\Repositories;

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PostRepository
{
    public function getAll(): LengthAwarePaginator
    {
        return Post::with('user')->latest()->paginate(10);
    }

    public function findById(int $id): ?Post
    {
        return Post::with('user')->find($id);
    }

    public function create(array $data): Post
    {
        return Post::create($data)->load('user');
    }

    public function update(Post $post, array $data): Post
    {
        $post->update($data);
        return $post->fresh();
    }

    public function delete(Post $post): void
    {
        $post->delete();
    }

    public function getByUser(int $userId): LengthAwarePaginator
    {
        return Post::where('user_id', $userId)->with('user')->latest()->paginate(10);
    }

    /**
     * Get posts by user with optional privacy filter.
     */
    public function getByUserWithPrivacy(int $userId, ?string $privacy = null): Collection
    {
        $query = Post::where('user_id', $userId)->with('user');

        if ($privacy) {
            $query->where('privacy', $privacy);
        }

        return $query->latest()->get();
    }
}
