<?php

namespace App\Policies;

use App\Models\Review;
use App\Models\User;

class ReviewPolicy
{
    /**
     * Determine if the given review can be updated by the user.
     */
    public function update(User $user, Review $review): bool
    {
        // ✅ Only the user who created the review can update it
        return $user->id === $review->user_id;
    }

    /**
     * Determine if the given review can be deleted by the user.
     */
    public function delete(User $user, Review $review): bool
    {
        // ✅ Only the user who created the review can delete it
        return $user->id === $review->user_id;
    }
}
