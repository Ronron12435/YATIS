<?php

namespace App\Http\Controllers;

use App\DTOs\Post\CreatePostDTO;
use App\Services\PostService;
use Illuminate\Http\Request;

class PostController extends Controller
{
    public function __construct(private PostService $postService) {}

    public function index()
    {
        $response = $this->postService->getAll();
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'image'   => 'nullable|image|max:5120',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('posts', 'public');
        }

        $dto = new CreatePostDTO(
            userId: $request->user()->id,
            content: $validated['content'],
            image: $imagePath,
        );

        $response = $this->postService->create($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->postService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'string|max:5000',
            'image'   => 'nullable|image|max:5120',
        ]);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('posts', 'public');
        }

        $response = $this->postService->update((int) $id, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->postService->delete((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function userPosts($userId)
    {
        $response = $this->postService->getByUser((int) $userId);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
