<?php

namespace App\Http\Controllers;

use App\DTOs\Group\CreateGroupDTO;
use App\Services\GroupService;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct(private GroupService $groupService) {}

    public function index()
    {
        $response = $this->groupService->getPublic();
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function publicGroups()
    {
        $response = $this->groupService->getPublic();
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'          => 'required|string|max:255',
            'description'   => 'nullable|string',
            'privacy'       => 'nullable|string|in:public,private',
            'member_limit'  => 'nullable|integer|min:10|max:500',
            'avatar'        => 'nullable|image|max:2048',
        ]);

        $avatarPath = null;
        if ($request->hasFile('avatar')) {
            $avatarPath = $request->file('avatar')->store('groups', 'public');
        }

        $isPrivate = ($validated['privacy'] ?? 'public') === 'private';

        $dto = new CreateGroupDTO(
            creatorId: $request->user()->id,
            name: $validated['name'],
            description: $validated['description'] ?? null,
            isPrivate: $isPrivate,
            avatar: $avatarPath,
        );

        $response = $this->groupService->create($dto);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function show($id)
    {
        $response = $this->groupService->getById((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'        => 'string|max:255',
            'description' => 'nullable|string',
            'is_private'  => 'boolean',
            'avatar'      => 'nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $validated['avatar'] = $request->file('avatar')->store('groups', 'public');
        }

        $response = $this->groupService->update((int) $id, $request->user()->id, $validated);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->groupService->delete((int) $id, $request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function addMember(Request $request, $id)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $response = $this->groupService->addMember((int) $id, (int) $validated['user_id']);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function removeMember(Request $request, $id, $userId)
    {
        $response = $this->groupService->removeMember((int) $id, $request->user()->id, (int) $userId);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function messages($id)
    {
        $response = $this->groupService->getMessages((int) $id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function userGroups(Request $request)
    {
        $response = $this->groupService->getUserGroups($request->user()->id);
        return response()->json($response->toArray(), $response->statusCode);
    }

    public function sendMessage(Request $request, $id)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $response = $this->groupService->sendMessage((int) $id, $request->user()->id, $validated['message']);
        return response()->json($response->toArray(), $response->statusCode);
    }
}
