@php
    $record = $getRecord();
    $mapId = 'district-map-' . $record->id;
    $coordinates = $record->map_coordinates;
@endphp

<div class="space-y-4">
    <!-- Map Container -->
    <div
        id="{{ $mapId }}"
        style="height: 400px; width: 100%; position: relative; z-index: 1;"
        class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden shadow-sm"
    ></div>

    <!-- Map Info and Controls -->
    <x-filament::section>
        <!-- Control Button -->
        @if($record->geojson_file_path)
            <div class="flex justify-center mb-4">
                <x-filament::button
                    type="button"
                    id="{{ $mapId }}-load-geojson"
                    color="primary"
                    icon="heroicon-o-map"
                >
                    Muat Batas Wilayah
                </x-filament::button>
            </div>
        @endif

        <!-- Map Information Badges -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @if($record->geojson_file_path)
                <x-filament::badge color="success" icon="heroicon-o-document-arrow-down" class="justify-start p-3">
                    <div class="flex flex-col items-start">
                        <span class="font-medium">File GeoJSON</span>
                        <span class="text-xs opacity-75 font-mono">{{ basename($record->geojson_file_path) }}</span>
                    </div>
                </x-filament::badge>
            @endif

            @if($coordinates)
                <x-filament::badge color="info" icon="heroicon-o-map-pin" class="justify-start p-3">
                    <div class="flex flex-col items-start">
                        <span class="font-medium">Koordinat</span>
                        <span class="text-xs opacity-75">{{ number_format(count($coordinates[0] ?? [])) }} titik</span>
                    </div>
                </x-filament::badge>
            @endif

            @if($record->custom_coordinates)
                <x-filament::badge color="warning" icon="heroicon-o-pencil" class="justify-start p-3">
                    <div class="flex flex-col items-start">
                        <span class="font-medium">Batas Kustom</span>
                        <span class="text-xs opacity-75">Digambar manual</span>
                    </div>
                </x-filament::badge>
            @endif

            @if(!$record->geojson_file_path && !$coordinates)
                <x-filament::badge color="gray" icon="heroicon-o-x-circle" class="justify-start p-3">
                    <div class="flex flex-col items-start">
                        <span class="font-medium">Tidak Ada Data Peta</span>
                        <span class="text-xs opacity-75">Batas wilayah belum tersedia</span>
                    </div>
                </x-filament::badge>
            @endif
        </div>
    </x-filament::section>
</div>

@push('scripts')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapId = '{{ $mapId }}';
            const recordId = {{ $record->id }};

            console.log('🗺️ Menginisialisasi peta view untuk wilayah:', '{{ $record->name }}');

            // Initialize map centered on Papua
            const map = L.map(mapId).setView([-2.5, 140.0], 6);

            // Add OpenStreetMap tiles
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);

            // Feature group to store district boundaries
            const districtLayer = new L.FeatureGroup();
            map.addLayer(districtLayer);

            // Function to create popup content - let browser handle colors
            function createPopupContent() {
                return `
                    <div style="padding: 16px; min-width: 220px;">
                        <h3 style="font-weight: bold; font-size: 1.125rem; margin-bottom: 12px;">{{ $record->name }}</h3>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.875rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Kabupaten:</span>
                                <span style="font-weight: 500;">{{ $record->regency?->name ?? 'Tidak Diketahui' }}</span>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Provinsi:</span>
                                <span style="font-weight: 500;">{{ $record->province }}</span>
                            </div>
                            @if($record->population)
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Populasi:</span>
                                    <span style="font-weight: 500;">{{ number_format($record->population) }} jiwa</span>
                                </div>
                            @endif
                            @if($record->area_hectares)
                                <div style="display: flex; justify-content: space-between;">
                                    <span>Luas:</span>
                                    <span style="font-weight: 500;">{{ number_format($record->area_hectares, 2) }} ha</span>
                                </div>
                            @endif
                            <div style="display: flex; justify-content: space-between;">
                                <span>Tingkat Keamanan:</span>
                                <span style="font-weight: 600; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; background-color: {{ match($record->security_level) {
                                    'low' => '#dcfce7',
                                    'medium' => '#fef3c7',
                                    'high' => '#fed7aa',
                                    'critical' => '#fecaca',
                                    default => '#f3f4f6'
                                } }}; color: {{ match($record->security_level) {
                                    'low' => '#166534',
                                    'medium' => '#92400e',
                                    'high' => '#c2410c',
                                    'critical' => '#dc2626',
                                    default => '#374151'
                                } }};">{{ match($record->security_level) {
                                    'low' => 'Risiko Rendah',
                                    'medium' => 'Risiko Sedang',
                                    'high' => 'Risiko Tinggi',
                                    'critical' => 'Risiko Kritis',
                                    default => ucfirst($record->security_level)
                                } }}</span>
                            </div>
                        </div>
                    </div>
                `;
            }

            // Load coordinates directly from PHP data
            @if($coordinates)
                const coordinates = @json($coordinates);
                console.log('📍 Memuat koordinat dari model:', coordinates.length, 'ring(s)');

                if (coordinates && coordinates.length > 0) {
                    try {
                        // Coordinates should already be in [lat, lng] format from map_coordinates accessor
                        const polygon = L.polygon(coordinates, {
                            color: '#059669',
                            fillColor: '#10b981',
                            fillOpacity: 0.3,
                            weight: 3,
                            opacity: 0.8
                        });

                        districtLayer.addLayer(polygon);

                        // Fit map to district bounds
                        if (districtLayer.getLayers().length > 0) {
                            map.fitBounds(districtLayer.getBounds(), { padding: [20, 20] });
                        }

                        // Add popup with district info
                        polygon.bindPopup(createPopupContent(), {
                            maxWidth: 300,
                            className: 'custom-popup'
                        });

                        console.log('✅ Berhasil menampilkan batas wilayah');

                    } catch (error) {
                        console.error('❌ Error creating polygon:', error);
                        showFallbackMarker();
                    }
                } else {
                    showFallbackMarker();
                }
            @else
                console.log('ℹ️ Tidak ada koordinat untuk wilayah ini');
                showFallbackMarker();
            @endif

            // Function to show fallback marker
            function showFallbackMarker() {
                const marker = L.marker([-2.5, 140.0], {
                    icon: L.divIcon({
                        html: '<div style="width: 24px; height: 24px; background-color: #ef4444; border-radius: 50%; border: 2px solid white; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); display: flex; align-items: center; justify-content: center;"><span style="color: white; font-size: 12px; font-weight: bold;">!</span></div>',
                        className: 'custom-div-icon',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    })
                }).addTo(map);

                marker.bindPopup(`
                    <div style="padding: 16px;">
                        <h3 style="font-weight: bold; font-size: 1.125rem; margin-bottom: 12px;">{{ $record->name }}</h3>
                        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.875rem;">
                            <p>{{ $record->regency?->name ?? 'Kabupaten Tidak Diketahui' }}</p>
                            <p>{{ $record->province }}</p>
                            <p style="color: #ea580c; font-weight: 500; background-color: #fff7ed; padding: 4px 8px; border-radius: 4px;">⚠️ Batas wilayah tidak tersedia</p>
                        </div>
                    </div>
                `, {
                    maxWidth: 280
                });
            }

            // Load GeoJSON button functionality
            @if($record->geojson_file_path)
                const loadGeoJsonBtn = document.getElementById('{{ $mapId }}-load-geojson');
                if (loadGeoJsonBtn) {
                    loadGeoJsonBtn.addEventListener('click', function() {
                        console.log('🔄 Memuat ulang dari file GeoJSON...');

                        // Clear existing layers
                        districtLayer.clearLayers();

                        // Reload coordinates (same as initial load)
                        @if($coordinates)
                            const reloadCoordinates = @json($coordinates);
                            if (reloadCoordinates && reloadCoordinates.length > 0) {
                                const polygon = L.polygon(reloadCoordinates, {
                                    color: '#f59e0b',
                                    fillColor: '#fbbf24',
                                    fillOpacity: 0.4,
                                    weight: 3,
                                    opacity: 0.9
                                });

                                districtLayer.addLayer(polygon);
                                map.fitBounds(districtLayer.getBounds(), { padding: [20, 20] });
                                polygon.bindPopup(createPopupContent());

                                // Show success notification
                                polygon.openPopup();

                                console.log('✅ Berhasil memuat ulang batas wilayah');
                            }
                        @endif
                    });
                }
            @endif

            // Make map responsive
            window.addEventListener('resize', function() {
                setTimeout(function() {
                    map.invalidateSize();
                }, 100);
            });
        });
    </script>

    <style>
        .custom-popup .leaflet-popup-content-wrapper {
            border-radius: 8px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .custom-div-icon {
            background: transparent !important;
            border: none !important;
        }

        /* Fix z-index for Leaflet map controls to not overlap Filament UI */
        #{{ $mapId }} .leaflet-control-container {
            z-index: 10 !important;
        }

        #{{ $mapId }} .leaflet-control {
            z-index: 10 !important;
        }

        #{{ $mapId }} .leaflet-popup {
            z-index: 1050 !important;
        }

        #{{ $mapId }} .leaflet-popup-pane {
            z-index: 1050 !important;
        }

        #{{ $mapId }} .leaflet-popup-content-wrapper {
            z-index: 1051 !important;
        }

        #{{ $mapId }} .leaflet-popup-tip {
            z-index: 1051 !important;
        }

        #{{ $mapId }} .leaflet-tooltip {
            z-index: 1040 !important;
        }

        #{{ $mapId }} .leaflet-tooltip-pane {
            z-index: 1040 !important;
        }
    </style>
@endpush
