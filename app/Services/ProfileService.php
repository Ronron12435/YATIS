<?php

namespace App\Services;

use App\DTOs\Profile\UpdateProfileDTO;
use App\DTOs\Profile\ChangePasswordDTO;
use App\DTOs\Profile\CreatePostDTO;
use App\Repositories\ProfileRepository;
use App\Models\Post;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function __construct(private ProfileRepository $profileRepository) {}

    public function getProfile(int $userId)
    {
        $user = $this->profileRepository->getUserProfile($userId);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'User not found',
                'data' => null,
            ];
        }

        $userData = $user->toArray();
        $userData['posts_count'] = $user->posts()->count();
        $userData['friends_count'] = $user->friends()->count();
        $userData['achievements_count'] = $user->achievements()->count();
        $userData['visitors_count'] = 0; // Placeholder

        return [
            'success' => true,
            'message' => 'Profile retrieved successfully',
            'data' => $userData,
        ];
    }

    public function updateProfile(int $userId, UpdateProfileDTO $dto)
    {
        try {
            $this->profileRepository->updateUser($userId, [
                'first_name' => $dto->firstName,
                'last_name' => $dto->lastName,
                'bio' => $dto->bio,
                'is_private' => $dto->isPrivate,
            ]);

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Profile updated successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to update profile',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function changePassword(int $userId, ChangePasswordDTO $dto)
    {
        try {
            $user = $this->profileRepository->getUserById($userId);

            if (!$user || !Hash::check($dto->currentPassword, $user->password)) {
                return [
                    'success' => false,
                    'message' => 'Current password is incorrect',
                    'data' => null,
                ];
            }

            if ($dto->newPassword !== $dto->confirmPassword) {
                return [
                    'success' => false,
                    'message' => 'New passwords do not match',
                    'data' => null,
                ];
            }

            if (strlen($dto->newPassword) < 8) {
                return [
                    'success' => false,
                    'message' => 'Password must be at least 8 characters',
                    'data' => null,
                ];
            }

            $this->profileRepository->updateUser($userId, [
                'password' => Hash::make($dto->newPassword),
            ]);

            return [
                'success' => true,
                'message' => 'Password changed successfully',
                'data' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to change password',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function createPost(int $userId, CreatePostDTO $dto)
    {
        try {
            $post = Post::create([
                'user_id' => $userId,
                'content' => $dto->content,
                'privacy' => $dto->privacy,
                'image' => $dto->image,
            ]);

            return [
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to create post',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function deletePost(int $postId, int $userId)
    {
        try {
            $post = Post::find($postId);

            if (!$post) {
                return [
                    'success' => false,
                    'message' => 'Post not found',
                    'data' => null,
                ];
            }

            if ($post->user_id !== $userId) {
                return [
                    'success' => false,
                    'message' => 'Unauthorized to delete this post',
                    'data' => null,
                ];
            }

            $post->delete();

            return [
                'success' => true,
                'message' => 'Post deleted successfully',
                'data' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete post',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getUserPosts(int $userId, int $page = 1)
    {
        try {
            $posts = $this->profileRepository->getUserPosts($userId, $page);

            return [
                'success' => true,
                'message' => 'Posts retrieved successfully',
                'data' => $posts['data'],
                'pagination' => [
                    'total' => $posts['total'],
                    'per_page' => $posts['per_page'],
                    'current_page' => $posts['current_page'],
                    'last_page' => $posts['last_page'],
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve posts',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getVisitors(int $userId)
    {
        try {
            $visitors = $this->profileRepository->getVisitors($userId);

            return [
                'success' => true,
                'message' => 'Visitors retrieved successfully',
                'data' => $visitors,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve visitors',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function recordVisit(int $visitedUserId, int $visitorUserId)
    {
        try {
            $this->profileRepository->recordVisit($visitedUserId, $visitorUserId);

            return [
                'success' => true,
                'message' => 'Visit recorded',
                'data' => null,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to record visit',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function uploadAvatar(int $userId, $file)
    {
        try {
            $path = $file->store('avatars', 'public');
            $this->profileRepository->updateUser($userId, [
                'profile_picture' => '/storage/' . $path,
            ]);

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload avatar',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function deleteAvatar(int $userId)
    {
        try {
            $this->profileRepository->updateUser($userId, [
                'profile_picture' => null,
            ]);

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Avatar deleted successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete avatar',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function uploadCover(int $userId, $file)
    {
        try {
            $path = $file->store('covers', 'public');
            $this->profileRepository->updateUser($userId, [
                'cover_photo' => '/storage/' . $path,
            ]);

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Cover photo uploaded successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload cover photo',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function deleteCover(int $userId)
    {
        try {
            $this->profileRepository->updateUser($userId, [
                'cover_photo' => null,
            ]);

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Cover photo deleted successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to delete cover photo',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getAchievements(int $userId)
    {
        try {
            $achievements = $this->profileRepository->getAchievements($userId);

            return [
                'success' => true,
                'message' => 'Achievements retrieved successfully',
                'data' => $achievements,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve achievements',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function getUserBusinesses(int $userId)
    {
        try {
            $businesses = $this->profileRepository->getUserBusinesses($userId);

            return [
                'success' => true,
                'message' => 'Businesses retrieved successfully',
                'data' => $businesses,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve businesses',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
