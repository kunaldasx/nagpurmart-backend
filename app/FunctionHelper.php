<?php

use Illuminate\Support\Str;

if (!function_exists('hyperAsset')) {
    function hyperAsset($path, $secure = null)
    {
        $url = app('url')->asset($path, $secure);

        // Optional: Add cache-busting using file modification time
        $fullPath = public_path($path);
        if (file_exists($fullPath)) {
            $timestamp = filemtime($fullPath);
            return $url . '?v=' . $timestamp;
        }

        return $url;
    }
}

if (!function_exists('generateUniqueSlug')) {
    function generateUniqueSlug($model, $title, $slugField = 'slug', $id = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $i = 1;

        while ($model::withoutGlobalScopes()
            ->where($slugField, $slug)
            ->when($id, function ($query) use ($id) {
                $query->where('id', '!=', $id);
            })
            ->exists()) {
            $slug = $originalSlug . '-' . $i++;
        }
        return $slug;
    }
}
