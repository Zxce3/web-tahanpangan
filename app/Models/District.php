<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\File;

class District extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'regency_id',
        'province',
        'geojson_file_path',
        'custom_coordinates', // Add this field
        'security_level',
        'population',
        'area_hectares',
        'administrative_level',
        'parent_district_id',
        'is_active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'regency_id' => 'integer',
            'area_hectares' => 'decimal:2',
            'parent_district_id' => 'integer',
            'is_active' => 'boolean',
            'custom_coordinates' => 'array', // Cast JSON to array
        ];
    }

    /**
     * Get coordinates for Leaflet map (prioritize custom, then file-based)
     */
    public function getMapCoordinatesAttribute(): ?array
    {
        // First try custom coordinates (already in Leaflet format [lat, lng])
        if ($this->custom_coordinates && is_array($this->custom_coordinates)) {
            return $this->custom_coordinates;
        }

        // Fallback to file coordinates (need conversion from [lng, lat] to [lat, lng])
        $coordinates = $this->polygon_coordinates;

        if (!$coordinates) {
            return null;
        }

        // Convert from GeoJSON format [lng, lat] to Leaflet format [lat, lng]
        return $this->convertToLeafletFormat($coordinates);
    }

    /**
     * Get polygon coordinates - prioritize custom coordinates, then file-based
     */
    public function getPolygonCoordinatesAttribute(): ?array
    {
        // First try custom coordinates (manually drawn) - already in correct format
        if ($this->custom_coordinates) {
            return $this->custom_coordinates;
        }

        // Fallback to file-based coordinates (from GeoJSON files)
        if (!$this->geojson_file_path) {
            return null;
        }

        $filePath = resource_path($this->geojson_file_path);

        if (!File::exists($filePath)) {
            return null;
        }

        $geojson = json_decode(File::get($filePath), true);

        if (!$geojson || !isset($geojson['geometry'])) {
            // Handle FeatureCollection format
            if (isset($geojson['features']) && !empty($geojson['features'])) {
                $geometry = $geojson['features'][0]['geometry'] ?? null;
            } else {
                return null;
            }
        } else {
            $geometry = $geojson['geometry'];
        }

        return $this->extractCoordinatesFromGeometry($geometry);
    }

    /**
     * Get coordinates for editing - prioritize custom over file
     */
    public function getEditCoordinatesAttribute(): ?array
    {
        // Return custom coordinates first (already in correct format)
        if ($this->custom_coordinates) {
            return $this->custom_coordinates;
        }

        // Return converted file coordinates as fallback
        return $this->map_coordinates;
    }

    /**
     * Convert GeoJSON coordinates to Leaflet format
     */
    private function convertToLeafletFormat(array $coordinates): array
    {
        $converted = [];

        foreach ($coordinates as $ring) {
            $convertedRing = [];
            foreach ($ring as $point) {
                // Convert [lng, lat] to [lat, lng]
                $convertedRing[] = [$point[1], $point[0]];
            }
            $converted[] = $convertedRing;
        }

        return $converted;
    }

    /**
     * Get full GeoJSON data from file
     */
    public function getGeoJsonData(): ?array
    {
        if (!$this->geojson_file_path) {
            return null;
        }

        $filePath = resource_path($this->geojson_file_path);

        if (!File::exists($filePath)) {
            return null;
        }

        return json_decode(File::get($filePath), true);
    }

    /**
     * Extract coordinates from GeoJSON geometry
     */
    private function extractCoordinatesFromGeometry(?array $geometry): ?array
    {
        if (!$geometry || !isset($geometry['type']) || !isset($geometry['coordinates'])) {
            return null;
        }

        $type = $geometry['type'];
        $coordinates = $geometry['coordinates'];

        switch ($type) {
            case 'Polygon':
                return $coordinates;

            case 'MultiPolygon':
                return $this->getLargestPolygonFromMultiPolygon($coordinates) ?? $coordinates[0] ?? null;

            case 'Point':
                $lng = $coordinates[0];
                $lat = $coordinates[1];
                $buffer = 0.01;
                return [[
                    [$lng - $buffer, $lat - $buffer],
                    [$lng + $buffer, $lat - $buffer],
                    [$lng + $buffer, $lat + $buffer],
                    [$lng - $buffer, $lat + $buffer],
                    [$lng - $buffer, $lat - $buffer]
                ]];

            case 'LineString':
                if (count($coordinates) >= 2) {
                    $firstPoint = $coordinates[0];
                    $lastPoint = $coordinates[count($coordinates) - 1];
                    $buffer = 0.001;

                    return [[
                        [$firstPoint[0] - $buffer, $firstPoint[1] - $buffer],
                        [$lastPoint[0] + $buffer, $firstPoint[1] - $buffer],
                        [$lastPoint[0] + $buffer, $lastPoint[1] + $buffer],
                        [$firstPoint[0] - $buffer, $lastPoint[1] + $buffer],
                        [$firstPoint[0] - $buffer, $firstPoint[1] - $buffer]
                    ]];
                }
                return null;

            case 'GeometryCollection':
                if (isset($geometry['geometries']) && is_array($geometry['geometries'])) {
                    foreach ($geometry['geometries'] as $geom) {
                        if (in_array($geom['type'] ?? '', ['Polygon', 'MultiPolygon'])) {
                            return $this->extractCoordinatesFromGeometry($geom);
                        }
                    }
                }
                return null;

            default:
                return null;
        }
    }

    /**
     * Get the largest polygon from MultiPolygon based on area
     */
    private function getLargestPolygonFromMultiPolygon(array $coordinates): ?array
    {
        if (empty($coordinates)) {
            return null;
        }

        $largestPolygon = null;
        $largestArea = 0;

        foreach ($coordinates as $polygon) {
            $area = $this->calculatePolygonArea($polygon[0] ?? []);
            if ($area > $largestArea) {
                $largestArea = $area;
                $largestPolygon = $polygon;
            }
        }

        return $largestPolygon;
    }

    /**
     * Calculate approximate area of a polygon
     */
    private function calculatePolygonArea(array $coordinates): float
    {
        if (count($coordinates) < 3) {
            return 0;
        }

        $area = 0;
        $j = count($coordinates) - 1;

        for ($i = 0; $i < count($coordinates); $i++) {
            $area += ($coordinates[$j][0] + $coordinates[$i][0]) * ($coordinates[$j][1] - $coordinates[$i][1]);
            $j = $i;
        }

        return abs($area / 2);
    }

    public function regency(): BelongsTo
    {
        return $this->belongsTo(District::class, 'regency_id');
    }

    public function districts(): HasMany
    {
        return $this->hasMany(District::class, 'regency_id');
    }

    public function parentDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class, 'parent_district_id');
    }

    public function childDistricts(): HasMany
    {
        return $this->hasMany(District::class, 'parent_district_id');
    }

    public function productionDatas(): HasMany
    {
        return $this->hasMany(ProductionData::class);
    }

    public function commodityPrices(): HasMany
    {
        return $this->hasMany(CommodityPrice::class);
    }

    public function securityLevelHistories(): HasMany
    {
        return $this->hasMany(SecurityLevelHistory::class);
    }

    /**
     * Scope for provinces only
     */
    public function scopeProvinces($query)
    {
        return $query->where('administrative_level', 'province');
    }

    /**
     * Scope for regencies only
     */
    public function scopeRegencies($query)
    {
        return $query->where('administrative_level', 'regency');
    }

    /**
     * Scope for districts only
     */
    public function scopeDistricts($query)
    {
        return $query->where('administrative_level', 'district');
    }

    /**
     * Get raw coordinate data from database (already in [lat, lng] format)
     */
    public function getRawCoordinates(): ?array
    {
        $rawData = $this->getRawOriginal('polygon_coordinates');
        if ($rawData) {
            return json_decode($rawData, true);
        }
        return null;
    }
}
