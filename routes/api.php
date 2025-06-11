<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\District;
use Illuminate\Support\Facades\File;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Add GeoJSON API route with better error handling
Route::get('/districts/{district}/geojson', function (District $district) {
    try {
        if (!$district->geojson_file_path) {
            return response()->json(['error' => 'No GeoJSON file associated with this district'], 404);
        }

        $filePath = resource_path($district->geojson_file_path);

        if (!File::exists($filePath)) {
            return response()->json([
                'error' => 'GeoJSON file not found',
                'path' => $district->geojson_file_path,
                'full_path' => $filePath
            ], 404);
        }

        $geoJsonContent = File::get($filePath);
        $geoJsonData = json_decode($geoJsonContent, true);

        if (!$geoJsonData) {
            return response()->json([
                'error' => 'Invalid GeoJSON file',
                'json_error' => json_last_error_msg()
            ], 500);
        }

        return response()->json($geoJsonData)
            ->header('Content-Type', 'application/json')
            ->header('Access-Control-Allow-Origin', '*');

    } catch (\Exception $e) {
        return response()->json([
            'error' => 'Server error while loading GeoJSON',
            'message' => $e->getMessage()
        ], 500);
    }
})->name('api.districts.geojson');
