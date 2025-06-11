@php
    $record = $getRecord();
    $mapId = 'district-map-' . $record->id;
    $coordinates = $record->map_coordinates; // Get converted coordinates
@endphp

<div class="space-y-4">
    <!-- Map Container -->
    <div
        id="{{ $mapId }}"
        style="height: 400px; width: 100%;"
        class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden shadow-sm"
    ></div>

    <!-- Map Info and Controls -->
    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
        <!-- Control Button -->
        @if($record->geojson_file_path)
            <div class="flex justify-center mb-4">
                <button
                    type="button"
                    id="{{ $mapId }}-load-geojson"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-indigo-600 hover:to-blue-700 text-white rounded-lg shadow-md hover:shadow-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m0 0L9 7"></path>
                    </svg>
                    Muat Batas Wilayah
                </button>
            </div>
        @endif

        <!-- Map Information Badges -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
            @if($record->geojson_file_path)
                <div class="flex items-center p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-green-800 dark:text-green-200">File GeoJSON</p>
                        <p class="text-xs text-green-600 dark:text-green-400 font-mono">{{ basename($record->geojson_file_path) }}</p>
                    </div>
                </div>
            @endif

            @if($coordinates)
                <div class="flex items-center p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-200">Koordinat</p>
                        <p class="text-xs text-blue-600 dark:text-blue-400">{{ number_format(count($coordinates[0] ?? [])) }} titik</p>
                    </div>
                </div>
            @endif

            @if($record->custom_coordinates)
                <div class="flex items-center p-3 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-purple-800 dark:text-purple-200">Batas Kustom</p>
                        <p class="text-xs text-purple-600 dark:text-purple-400">Digambar manual</p>
                    </div>
                </div>
            @endif

            <!-- Status badge jika tidak ada data -->
            @if(!$record->geojson_file_path && !$coordinates)
                <div class="flex items-center p-3 bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-700 rounded-lg">
                    <div class="flex-shrink-0">
                        <svg class="w-5 h-5 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-300">Tidak Ada Data Peta</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Batas wilayah belum tersedia</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

    <!-- Leaflet JS -->
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

            // Function to create popup content
            function createPopupContent() {
                return `
                    <div class="p-3 min-w-[200px]">
                        <h3 class="font-bold text-lg text-gray-900 mb-2">{{ $record->name }}</h3>
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Kabupaten:</span>
                                <span class="font-medium">{{ $record->regency?->name ?? 'Tidak Diketahui' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Provinsi:</span>
                                <span class="font-medium">{{ $record->province }}</span>
                            </div>
                            @if($record->population)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Populasi:</span>
                                    <span class="font-medium">{{ number_format($record->population) }} jiwa</span>
                                </div>
                            @endif
                            @if($record->area_hectares)
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Luas:</span>
                                    <span class="font-medium">{{ number_format($record->area_hectares, 2) }} ha</span>
                                </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tingkat Keamanan:</span>
                                <span class="font-medium text-{{ match($record->security_level) {
                                    'low' => 'green',
                                    'medium' => 'yellow',
                                    'high' => 'orange',
                                    'critical' => 'red',
                                    default => 'gray'
                                } }}-600">{{ match($record->security_level) {
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
                        html: '<div class="w-6 h-6 bg-red-500 rounded-full border-2 border-white shadow-lg flex items-center justify-center"><span class="text-white text-xs font-bold">!</span></div>',
                        className: 'custom-div-icon',
                        iconSize: [24, 24],
                        iconAnchor: [12, 12]
                    })
                }).addTo(map);

                marker.bindPopup(`
                    <div class="p-3">
                        <h3 class="font-bold text-lg mb-2">{{ $record->name }}</h3>
                        <div class="space-y-1 text-sm">
                            <p class="text-gray-600">{{ $record->regency?->name ?? 'Kabupaten Tidak Diketahui' }}</p>
                            <p class="text-gray-600">{{ $record->province }}</p>
                            <p class="text-orange-600 font-medium">⚠️ Batas wilayah tidak tersedia</p>
                        </div>
                    </div>
                `, {
                    maxWidth: 250
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

        .custom-popup .leaflet-popup-tip {
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
        }

        .custom-div-icon {
            background: transparent !important;
            border: none !important;
        }
    </style>
@endpush
