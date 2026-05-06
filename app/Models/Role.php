<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\Role as RoleContract;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Custom Role model to support team-scoped uniqueness without enabling Spatie Teams.
 *
 * It respects the unique index [team_id, name, guard_name] that exists in the DB.
 */
class Role extends SpatieRole implements RoleContract
{
    /**
     * Override create to include team_id in the duplication check when provided.
     * This prevents false-positive RoleAlreadyExists for different teams.
     *
     * @throws RoleAlreadyExists
     */
    public static function create(array $attributes = [])
    {
        $attributes['guard_name'] ??= Guard::getDefaultName(static::class);

        $params = [
            'name' => $attributes['name'] ?? null,
            'guard_name' => $attributes['guard_name'],
        ];

        // When team_id is present, include it in the uniqueness check
        if (array_key_exists('team_id', $attributes)) {
            $params['team_id'] = $attributes['team_id'];
        }

        // Check for an existing record using our custom scoping
        if (static::findByParam($params)) {
            throw RoleAlreadyExists::create($attributes['name'], $attributes['guard_name']);
        }

        return static::query()->create($attributes);
    }

    /**
     * Override findByParam to avoid Spatie's teams logic and allow our team_id column.
     */
    protected static function findByParam(array $params = []): ?\Spatie\Permission\Contracts\Role
    {
        $query = static::query();

        foreach ($params as $key => $value) {
            $query->where($key, $value);
        }

        return $query->first();
    }
}
