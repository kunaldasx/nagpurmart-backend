<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'mobile' => $this->mobile,
            'referral_code' => $this->referral_code,
            'friends_code' => $this->friends_code,
            'reward_points' => $this->reward_points,
            'profile_image' => $this->profile_image,
            'status' => $this->status,
            'country' => $this->country,
            'iso_2' => $this->iso_2,
            'access_panel' => $this->access_panel,
            'created_at' => $this->created_at,
        ];
    }
}
