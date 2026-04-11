<?php

namespace App\Services;

use App\DTOs\Profile\UpdateProfileDTO;
use App\DTOs\Profile\ChangePasswordDTO;
use App\DTOs\Profile\CreatePostDTO;
use App\Repositories\ProfileRepository;
use App\Models\Post;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

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
            if (!$file || !$file->isValid()) {
                return [
                    'success' => false,
                    'message' => 'Invalid file uploaded',
                    'data' => null,
                ];
            }

            $filename = 'avatar_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('avatars', $filename, 'public');
            
            if (!$path) {
                return [
                    'success' => false,
                    'message' => 'Failed to store file',
                    'data' => null,
                ];
            }

            $updated = $this->profileRepository->updateUser($userId, [
                'profile_picture' => $path,
            ]);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Failed to update user profile',
                    'data' => null,
                ];
            }

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Avatar uploaded successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload avatar: ' . $e->getMessage(),
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function deleteAvatar(int $userId)
    {
        $debug = [];
        try {
            $debug['step_1'] = 'Getting user';
            $user = $this->profileRepository->getUserById($userId);
            $debug['user_found'] = $user ? true : false;
            
            if ($user) {
                $debug['profile_picture'] = $user->profile_picture;
            }
            
            if ($user && $user->profile_picture) {
                $debug['step_2'] = 'Deleting file';
                $filename = $user->profile_picture;
                $filePath = storage_path('app/public/avatars/' . $filename);
                $debug['file_path'] = $filePath;
                $debug['file_exists'] = file_exists($filePath);
                
                if (file_exists($filePath)) {
                    $deleted = unlink($filePath);
                    $debug['file_deleted'] = $deleted;
                } else {
                    $debug['file_deleted'] = false;
                    $debug['reason'] = 'File does not exist';
                }
            } else {
                $debug['step_2'] = 'No file to delete';
            }
            
            $debug['step_3'] = 'Updating database';
            $updated = $this->profileRepository->updateUser($userId, [
                'profile_picture' => null,
            ]);
            $debug['db_updated'] = $updated;

            $user = $this->profileRepository->getUserById($userId);
            $debug['profile_picture_after'] = $user->profile_picture;

            return [
                'success' => true,
                'message' => 'Avatar deleted successfully',
                'data' => $user,
                'debug' => $debug,
            ];
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['trace'] = $e->getTraceAsString();
            return [
                'success' => false,
                'message' => 'Failed to delete avatar: ' . $e->getMessage(),
                'data' => null,
                'debug' => $debug,
            ];
        }
    }

    public function uploadCover(int $userId, $file)
    {
        try {
            if (!$file || !$file->isValid()) {
                return [
                    'success' => false,
                    'message' => 'Invalid file uploaded',
                    'data' => null,
                ];
            }

            $filename = 'cover_' . $userId . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('covers', $filename, 'public');
            
            if (!$path) {
                return [
                    'success' => false,
                    'message' => 'Failed to store file',
                    'data' => null,
                ];
            }

            $updated = $this->profileRepository->updateUser($userId, [
                'cover_photo' => $path,
            ]);

            if (!$updated) {
                return [
                    'success' => false,
                    'message' => 'Failed to update user profile',
                    'data' => null,
                ];
            }

            $user = $this->profileRepository->getUserById($userId);

            return [
                'success' => true,
                'message' => 'Cover photo uploaded successfully',
                'data' => $user,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to upload cover photo: ' . $e->getMessage(),
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    public function deleteCover(int $userId)
    {
        $debug = [];
        try {
            $debug['step_1'] = 'Getting user';
            $user = $this->profileRepository->getUserById($userId);
            $debug['user_found'] = $user ? true : false;
            
            if ($user) {
                $debug['cover_photo'] = $user->cover_photo;
            }
            
            if ($user && $user->cover_photo) {
                $debug['step_2'] = 'Deleting file';
                // Extract filename from stored path (e.g., "/storage/covers/filename.jpg" -> "covers/filename.jpg")
                $coverPath = $user->cover_photo;
                if (strpos($coverPath, '/storage/') === 0) {
                    $coverPath = substr($coverPath, 9); // Remove "/storage/" prefix
                }
                $filePath = storage_path('app/public/' . $coverPath);
                $debug['file_path'] = $filePath;
                $debug['file_exists'] = file_exists($filePath);
                
                if (file_exists($filePath)) {
                    $deleted = unlink($filePath);
                    $debug['file_deleted'] = $deleted;
                } else {
                    $debug['file_deleted'] = false;
                    $debug['reason'] = 'File does not exist';
                }
            } else {
                $debug['step_2'] = 'No file to delete';
            }
            
            $debug['step_3'] = 'Updating database';
            $updated = $this->profileRepository->updateUser($userId, [
                'cover_photo' => null,
            ]);
            $debug['db_updated'] = $updated;

            $user = $this->profileRepository->getUserById($userId);
            $debug['cover_photo_after'] = $user->cover_photo;

            return [
                'success' => true,
                'message' => 'Cover photo deleted successfully',
                'data' => $user,
                'debug' => $debug,
            ];
        } catch (\Exception $e) {
            $debug['error'] = $e->getMessage();
            $debug['trace'] = $e->getTraceAsString();
            return [
                'success' => false,
                'message' => 'Failed to delete cover photo: ' . $e->getMessage(),
                'data' => null,
                'debug' => $debug,
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

    public function getUserProfileForView(int $userId): array
    {
        try {
            $user = $this->profileRepository->getUserById($userId);

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                ];
            }

            // Get achievements with related event and task data
            $achievements = $user->achievements()
                ->with(['event', 'task'])
                ->get()
                ->map(function ($achievement) {
                    return [
                        'id' => $achievement->id,
                        'badge_name' => $achievement->event?->name ?? 'Achievement',
                        'badge_icon' => '🏆',
                        'description' => $achievement->task?->name ?? 'Completed task',
                        'points' => $achievement->points_earned,
                    ];
                })
                ->toArray();

            return [
                'success' => true,
                'message' => 'User profile retrieved successfully',
                'data' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'username' => $user->username,
                    'bio' => $user->bio,
                    'profile_picture' => $user->profile_picture,
                    'cover_photo' => $user->cover_photo,
                    'achievements' => $achievements,
                ],
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Failed to retrieve user profile',
                'data' => null,
                'error' => $e->getMessage(),
            ];
        }
    }
}
