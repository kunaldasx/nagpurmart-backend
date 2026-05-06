<?php

namespace App\Services;

use App\Events\Seller\SellerRegistered;
use App\Models\Country;
use App\Models\Seller;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SellerService
{
    /**
     * Create a new seller
     *
     * @param array $data
     * @param array $files
     * @return Seller
     * @throws \Exception
     */
    public function createSeller(array $data, array $files = []): Seller
    {
        DB::beginTransaction();
        try {
            $user = $this->resolveOrCreateUser($data);

            $sellerData = $this->prepareSellerData($data, $user->id);
            $seller = Seller::create($sellerData);

            $user->assignRole('seller');

            $this->handleMediaUploads($seller, $files);

            DB::commit();

            event(new SellerRegistered($seller, $user));

            return $seller;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Resolve existing user or create new one
     *
     * @param array $data
     * @return User
     * @throws \Exception
     */
    protected function resolveOrCreateUser(array $data): User
    {
        if (isset($data['user_id'])) {
            $user = User::find($data['user_id']);

            if (!$user) {
                throw new \Exception('User not found', 404);
            }

            if (Seller::where('user_id', $user->id)->exists()) {
                throw new \Exception('Seller already exists for this user', 422);
            }

            return $user;
        }

        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'mobile' => $data['mobile'],
            'password' => $data['password'],
            'status' => 'active',
            'access_panel' => 'seller',
        ]);
    }

    /**
     * Prepare seller data for creation
     *
     * @param array $data
     * @param int $userId
     * @return array
     */
    protected function prepareSellerData(array $data, int $userId): array
    {
        $sellerData = collect($data)->except([
            'name', 'email', 'mobile', 'password', 'user_id',
            'business_license', 'articles_of_incorporation',
            'national_identity_card', 'authorized_signature'
        ])->toArray();

        if (isset($sellerData['country'])) {
            $country = Country::where('name', $sellerData['country'])->firstOrFail();
            if (!empty($country->phonecode)) {
                $sellerData['country_code'] = $country->phonecode;
            }
        }

        $sellerData['user_id'] = $userId;

        return $sellerData;
    }

    /**
     * Handle media uploads for seller
     *
     * @param Seller $seller
     * @param array $files
     * @return void
     */
    protected function handleMediaUploads(Seller $seller, array $files): void
    {
        $collections = [
            'business_license',
            'articles_of_incorporation',
            'national_identity_card',
            'authorized_signature'
        ];

        foreach ($collections as $collection) {
            if (isset($files[$collection]) && $files[$collection] instanceof UploadedFile) {
                $seller->addMedia($files[$collection])->toMediaCollection($collection);
            }
        }
    }
}
