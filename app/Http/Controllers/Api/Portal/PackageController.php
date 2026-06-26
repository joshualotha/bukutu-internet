<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Models\Package;

class PackageController extends Controller
{
    /**
     * List all active packages.
     */
    public function index()
    {
        $packages = Package::active()
            ->sorted()
            ->get(['id', 'name', 'description', 'price', 'duration_minutes', 'upload_speed', 'download_speed', 'sort_order']);

        return response()->json([
            'success' => true,
            'data' => $packages,
        ]);
    }

    /**
     * Get a specific package.
     */
    public function show($id)
    {
        $package = Package::active()->find($id);

        if (! $package) {
            return response()->json([
                'success' => false,
                'message' => __('portal.package_not_found'),
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $package,
        ]);
    }
}
