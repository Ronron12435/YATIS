<?php

namespace App\Services;

use App\DTOs\Post\CreatePostDTO;
use App\Repositories\PostRepository;
use App\Responses\ApiResponse;

class PostService
{
    public function __construct(private PostRepository $postRepository) {}

    public function getAll(): ApiResponse
    {
        return new ApiResponse(true, $this->postRepository->getAll(), 'Success');
    }

    public function getById(int $id): ApiResponse
    {
        $post = $this->postRepository->findById($id);

        if (!$post) {
            return new ApiResponse(false, null, 'Post not found', 404);
        }

        return new ApiResponse(true, $post, 'Success');
    }

    public function create(CreatePostDTO $dto): ApiResponse
    {
        $post = $this->postRepository->create([
            'user_id' => $dto->userId,
            'content' => $dto->content,
            'image'   => $dto->image,
        ]);

        return new ApiResponse(true, $post, 'Post created successfully', 201);
    }

    public function update(int $id, int $authId, array $data): ApiResponse
    {
        $post = $this->postRepository->findById($id);

        if (!$post) {
            return new ApiResponse(false, null, 'Post not found', 404);
        }

        if ($authId !== $post->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        return new ApiResponse(true, $this->postRepository->update($post, $data), 'Post updated successfully');
    }

    public function delete(int $id, int $authId): ApiResponse
    {
        $post = $this->postRepository->findById($id);

        if (!$post) {
            return new ApiResponse(false, null, 'Post not found', 404);
        }

        if ($authId !== $post->user_id) {
            return new ApiResponse(false, null, 'Unauthorized', 403);
        }

        $this->postRepository->delete($post);

        return new ApiResponse(true, null, 'Post deleted successfully');
    }

    public function getByUser(int $userId): ApiResponse
    {
        return new ApiResponse(true, $this->postRepository->getByUser($userId), 'Success');
    }
}
