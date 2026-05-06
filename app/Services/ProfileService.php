<?php

namespace App\Services;

use App\Enums\SpatieMediaCollectionName;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileService
{
    /**
     * Update user profile
     *
     * @param User $user
     * @param array $validatedData
     * @param Request|null $request
     * @return User
     * @throws \Exception
     */
    public function updateProfile(User $user, array $validatedData, Request $request = null): User
    {
        try {
            DB::beginTransaction();

            // Update user basic information
            $user->update($validatedData);

            // Handle profile image update if provided
            if ($request && !empty($validatedData['profile_image'])) {
                SpatieMediaService::update($request, $user, SpatieMediaCollectionName::PROFILE_IMAGE());
            }

            DB::commit();

            return $user->fresh();

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get user profile data
     *
     * @param User $user
     * @return array
     */
    public function getProfileData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'profile_image' => $user->profile_image,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }
}
