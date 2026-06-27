<?php

namespace App\Http\Controllers\Api\Portal;

use App\Http\Controllers\Controller;
use App\Http\Resources\PackageResource;
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
            ->get();

        return response()->json([
            'success' => true,
            'data' => PackageResource::collection($packages),
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
            'data' => PackageResource::make($package),
        ]);
    }
}
