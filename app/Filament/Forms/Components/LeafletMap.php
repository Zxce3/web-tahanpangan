<?php

namespace App\Filament\Forms\Components;

use Filament\Forms\Components\Field;
use Filament\Forms\Components\Concerns\HasPlaceholder;

class LeafletMap extends Field
{
    use HasPlaceholder;

    protected string $view = 'filament.forms.components.leaflet-map';

    protected float $defaultLat = -2.5;
    protected float $defaultLng = 140.0;
    protected int $defaultZoom = 6;
    protected string $height = '400px';

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (LeafletMap $component, $state, $record) {
            // Priority 1: Use existing state from form if available
            if ($state && is_string($state) && $state !== 'null') {
                $coordinates = json_decode($state, true);
                if ($coordinates && is_array($coordinates)) {
                    $component->state($coordinates);
                    return;
                }
            }

            // Priority 2: Load from custom_coordinates field if it exists in record
            if ($record && isset($record->custom_coordinates) && $record->custom_coordinates) {
                $component->state($record->custom_coordinates);
                return;
            }

            // Priority 3: Load from GeoJSON file via model accessor (but don't set state to avoid duplication)
            // This will be handled by the JavaScript "Load from GeoJSON" functionality
            if ($record && $record->geojson_file_path) {
                // Don't auto-load here to avoid conflicts
                // Let the JavaScript handle this based on user action
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            if (is_array($state) && !empty($state)) {
                // Store coordinates in Leaflet format [lat, lng]
                return json_encode($state);
            }
            return null;
        });
    }

    /**
     * Load coordinates from GeoJSON file and convert to Leaflet format
     */
    private function loadCoordinatesFromFile(string $geojsonPath): ?array
    {
        $filePath = resource_path($geojsonPath);

        if (!file_exists($filePath)) {
            return null;
        }

        $geojson = json_decode(file_get_contents($filePath), true);

        if (!$geojson) {
            return null;
        }

        // Extract coordinates from GeoJSON
        $coordinates = $this->extractCoordinatesFromGeoJson($geojson);

        if (!$coordinates) {
            return null;
        }

        // Convert from GeoJSON format [lng, lat] to Leaflet format [lat, lng]
        return $this->convertToLeafletFormat($coordinates);
    }

    /**
     * Extract coordinates from GeoJSON structure
     */
    private function extractCoordinatesFromGeoJson(array $geojson): ?array
    {
        // Handle FeatureCollection
        if (isset($geojson['features']) && !empty($geojson['features'])) {
            $geometry = $geojson['features'][0]['geometry'] ?? null;
        } elseif (isset($geojson['geometry'])) {
            $geometry = $geojson['geometry'];
        } else {
            return null;
        }

        if (!$geometry || !isset($geometry['coordinates'])) {
            return null;
        }

        $type = $geometry['type'] ?? '';
        $coordinates = $geometry['coordinates'];

        switch ($type) {
            case 'Polygon':
                return $coordinates;
            case 'MultiPolygon':
                // Return the largest polygon
                return $coordinates[0] ?? null;
            default:
                return null;
        }
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

    public function defaultLatLng(float $lat, float $lng): static
    {
        $this->defaultLat = $lat;
        $this->defaultLng = $lng;

        return $this;
    }

    public function defaultZoom(int $zoom): static
    {
        $this->defaultZoom = $zoom;

        return $this;
    }

    public function height(string $height): static
    {
        $this->height = $height;

        return $this;
    }

    public function getDefaultLat(): float
    {
        return $this->defaultLat;
    }

    public function getDefaultLng(): float
    {
        return $this->defaultLng;
    }

    public function getDefaultZoom(): int
    {
        return $this->defaultZoom;
    }

    public function getHeight(): string
    {
        return $this->height;
    }
}
