@php
    $id = $getId();
    $statePath = $getStatePath();
    $isDisabled = $isDisabled();
    $defaultLat = $getDefaultLat();
    $defaultLng = $getDefaultLng();
    $defaultZoom = $getDefaultZoom();
    $height = $getHeight();
    $state = $getState();
    $record = $getRecord();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div class="space-y-2">
        <!-- Map Container -->
        <div
            id="{{ $id }}-map"
            style="height: {{ $height }}; width: 100%; position: relative; z-index: 1;"
            class="border border-gray-300 dark:border-gray-600 rounded-lg overflow-hidden"
        ></div>

        <!-- Hidden input to store the coordinate data -->
        <input type="hidden" name="{{ $statePath }}" id="{{ $id }}-input" value="{{ is_array($state) ? json_encode($state) : $state }}" />

        <!-- Map Controls -->
        @unless($isDisabled)
            <x-filament::section>
                <div class="flex flex-wrap gap-3">
                    <x-filament::button
                        type="button"
                        id="{{ $id }}-clear"
                        color="danger"
                        icon="heroicon-o-trash"
                        size="sm"
                    >
                        Hapus Poligon
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        id="{{ $id }}-draw"
                        color="primary"
                        icon="heroicon-o-pencil"
                        size="sm"
                    >
                        Gambar Poligon
                    </x-filament::button>

                    <x-filament::button
                        type="button"
                        id="{{ $id }}-edit"
                        color="success"
                        icon="heroicon-o-pencil-square"
                        size="sm"
                    >
                        Edit Poligon
                    </x-filament::button>

                    @if($record && $record->geojson_file_path)
                        <x-filament::button
                            type="button"
                            id="{{ $id }}-load-geojson"
                            color="info"
                            icon="heroicon-o-cloud-arrow-down"
                            size="sm"
                        >
                            Muat dari GeoJSON
                        </x-filament::button>
                    @endif

                    <div class="ml-auto">
                        <x-filament::badge color="info" icon="heroicon-o-information-circle">
                            Klik dan seret untuk menggambar batas wilayah
                        </x-filament::badge>
                    </div>
                </div>
            </x-filament::section>
        @endunless
    </div>

    @push('scripts')
        <!-- Leaflet CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />

        <!-- Leaflet Draw CSS -->
        <link rel="stylesheet" href="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.css" />

        <!-- Leaflet JS -->
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

        <!-- Leaflet Draw JS -->
        <script src="https://unpkg.com/leaflet-draw@1.0.4/dist/leaflet.draw.js"></script>

        <script>
            // Use multiple ready states to ensure map loads in tabs
            function initializeMap_{{ str_replace(['-', '.'], ['_', '_'], $id) }}() {
                // Define variables with proper escaping
                const mapId = '{{ $id }}-map';
                const inputId = '{{ $id }}-input';
                const clearBtnId = '{{ $id }}-clear';
                const drawBtnId = '{{ $id }}-draw';
                const editBtnId = '{{ $id }}-edit';
                const loadGeoJsonBtnId = '{{ $id }}-load-geojson';
                const recordId = {{ $record ? $record->id : 'null' }};
                const hasGeoJsonFile = {{ $record && $record->geojson_file_path ? 'true' : 'false' }};
                const isDisabled = {{ $isDisabled ? 'true' : 'false' }};

                console.log('🗺️ EDIT MAP initialization (EMERGENCY FIX):', {
                    recordId: recordId,
                    hasGeoJsonFile: hasGeoJsonFile,
                    isDisabled: isDisabled,
                    mapId: mapId
                });

                // Check if map container already exists and has been initialized
                const mapContainer = document.getElementById(mapId);
                if (!mapContainer) {
                    console.error('❌ Map container not found:', mapId);
                    return false;
                }

                // Check if container is visible (important for tabs)
                if (mapContainer.offsetWidth === 0 || mapContainer.offsetHeight === 0) {
                    console.warn('⚠️ Map container not visible, retrying in 500ms...');
                    setTimeout(() => initializeMap_{{ str_replace(['-', '.'], ['_', '_'], $id) }}(), 500);
                    return false;
                }

                // Check if map is already initialized
                if (mapContainer._leaflet_id) {
                    console.warn('⚠️ Map already initialized, refreshing...');
                    // Refresh existing map
                    if (window.leafletMaps && window.leafletMaps[mapId]) {
                        window.leafletMaps[mapId].invalidateSize();
                        return true;
                    }
                }

                // Initialize map
                let map;
                try {
                    map = L.map(mapId).setView([{{ $defaultLat }}, {{ $defaultLng }}], {{ $defaultZoom }});
                    console.log('✅ Map initialized successfully');

                    // Store map reference globally for tab switching
                    if (!window.leafletMaps) window.leafletMaps = {};
                    window.leafletMaps[mapId] = map;

                } catch (error) {
                    console.error('❌ Failed to initialize map:', error);
                    return false;
                }

                // Add OpenStreetMap tiles
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '© OpenStreetMap contributors'
                }).addTo(map);

                // Feature group to store drawn polygons
                const drawnItems = new L.FeatureGroup();
                map.addLayer(drawnItems);

                // Initialize draw control
                const drawControl = new L.Control.Draw({
                    position: 'topright',
                    draw: {
                        polygon: {
                            allowIntersection: false,
                            drawError: {
                                color: '#e1e100',
                                message: '<strong>Kesalahan:</strong> Garis poligon tidak boleh bersilangan!'
                            },
                            shapeOptions: {
                                color: '#3b82f6',
                                fillColor: '#3b82f6',
                                fillOpacity: 0.2,
                                weight: 2
                            }
                        },
                        polyline: false,
                        rectangle: false,
                        circle: false,
                        marker: false,
                        circlemarker: false
                    },
                    edit: {
                        featureGroup: drawnItems,
                        remove: true
                    }
                });
                map.addControl(drawControl);

                // Function to load existing polygon from input
                function loadExistingPolygon() {
                    const input = document.getElementById(inputId);
                    if (!input) {
                        console.warn('⚠️ Input element not found:', inputId);
                        return false;
                    }

                    const existingData = input.value;

                    console.log('🔍 Loading existing polygon data from input:', existingData ? 'Data found' : 'No data');

                    if (existingData && existingData !== 'null' && existingData !== '') {
                        try {
                            const coordinates = JSON.parse(existingData);

                            if (coordinates && Array.isArray(coordinates) && coordinates.length > 0) {
                                // Clear existing polygons first
                                drawnItems.clearLayers();

                                console.log('📍 Creating polygon with coordinates points:', coordinates[0]?.length || 0);

                                // Create polygon - coordinates should be in [lat, lng] format
                                const polygon = L.polygon(coordinates, {
                                    color: '#059669',
                                    fillColor: '#10b981',
                                    fillOpacity: 0.3,
                                    weight: 3
                                });

                                drawnItems.addLayer(polygon);

                                // Fit map to polygon bounds with a slight delay to ensure rendering
                                setTimeout(() => {
                                    if (drawnItems.getLayers().length > 0) {
                                        map.fitBounds(drawnItems.getBounds(), { padding: [20, 20] });
                                    }
                                }, 100);

                                console.log('✅ Successfully loaded existing polygon from input');
                                return true;
                            }
                        } catch (e) {
                            console.warn('❌ Error parsing existing polygon data:', e);
                        }
                    }

                    return false;
                }

                // Function to load GeoJSON EXACTLY like the view page does
                function loadGeoJsonFromModel() {
                    if (!recordId || !hasGeoJsonFile) {
                        alert('Tidak ada file GeoJSON yang tersedia untuk distrik ini');
                        return;
                    }

                    // Load coordinates EXACTLY like view page - directly from PHP
                    @if($record && $record->map_coordinates)
                        const existingCoordinates = @json($record->map_coordinates);
                        console.log('📁 Memuat koordinat dari model (DARURAT):', existingCoordinates);

                        if (existingCoordinates && existingCoordinates.length > 0) {
                            // Clear existing polygons
                            drawnItems.clearLayers();

                            // Create polygon EXACTLY like view page
                            const polygon = L.polygon(existingCoordinates, {
                                color: '#f59e0b',
                                fillColor: '#fbbf24',
                                fillOpacity: 0.3,
                                weight: 3
                            });

                            drawnItems.addLayer(polygon);

                            // Fit map to district bounds
                            if (drawnItems.getLayers().length > 0) {
                                map.fitBounds(drawnItems.getBounds(), { padding: [20, 20] });
                                updateInput();
                            }

                            console.log('✅ DARURAT: Berhasil menampilkan koordinat GeoJSON dari model');
                            return true;
                        } else {
                            console.log('❌ Tidak ada koordinat yang tersedia di model');
                            alert('Tidak ada koordinat yang ditemukan di model');
                        }
                    @else
                        console.log('❌ Tidak ada record atau koordinat peta yang tersedia');
                        alert('Tidak ada koordinat GeoJSON yang tersedia untuk distrik ini');
                    @endif

                    return false;
                }

                // Auto-load coordinates on initialization EXACTLY like view page
                const loaded = loadExistingPolygon();

                // If no custom coordinates exist but we have GeoJSON file, auto-load it
                if (!loaded && hasGeoJsonFile) {
                    console.log('ℹ️ EMERGENCY: No custom coordinates, auto-loading from GeoJSON file');
                    setTimeout(() => {
                        loadGeoJsonFromModel();
                    }, 300);
                }

                // Function to update hidden input
                function updateInput() {
                    const input = document.getElementById(inputId);
                    if (!input) {
                        console.warn('⚠️ Input element not found for update:', inputId);
                        return;
                    }

                    const layers = drawnItems.getLayers();
                    if (layers.length > 0) {
                        const polygon = layers[0];
                        let latLngs;

                        if (polygon.getLatLngs) {
                            latLngs = polygon.getLatLngs()[0];
                        } else if (polygon.feature && polygon.feature.geometry) {
                            // Handle GeoJSON layer
                            const coords = polygon.feature.geometry.coordinates[0];
                            latLngs = coords.map(coord => L.latLng(coord[1], coord[0]));
                        }

                        if (latLngs) {
                            // Store as [lat, lng] format for Leaflet
                            const coordinates = [latLngs.map(point => [point.lat, point.lng])];
                            input.value = JSON.stringify(coordinates);
                            input.dispatchEvent(new Event('change'));

                            console.log('💾 EMERGENCY: Updated input with coordinates:', coordinates.length, 'rings,', coordinates[0]?.length, 'points');
                        }
                    } else {
                        input.value = '';
                        input.dispatchEvent(new Event('change'));
                        console.log('🗑️ Cleared input coordinates');
                    }
                }

                // Event handlers
                map.on(L.Draw.Event.CREATED, function(e) {
                    console.log('✏️ New polygon created');
                    drawnItems.clearLayers();
                    drawnItems.addLayer(e.layer);
                    updateInput();
                });

                map.on(L.Draw.Event.EDITED, function(e) {
                    console.log('✏️ Polygon edited');
                    updateInput();
                });

                map.on(L.Draw.Event.DELETED, function(e) {
                    console.log('🗑️ Polygon deleted');
                    updateInput();
                });

                // Button event listeners
                if (!isDisabled) {
                    const clearBtn = document.getElementById(clearBtnId);
                    if (clearBtn) {
                        clearBtn.addEventListener('click', function() {
                            console.log('🗑️ Clear button clicked');
                            drawnItems.clearLayers();
                            updateInput();
                        });
                    }

                    const drawBtn = document.getElementById(drawBtnId);
                    if (drawBtn) {
                        drawBtn.addEventListener('click', function() {
                            console.log('✏️ Draw button clicked');
                            new L.Draw.Polygon(map, drawControl.options.draw.polygon).enable();
                        });
                    }

                    const editBtn = document.getElementById(editBtnId);
                    if (editBtn) {
                        editBtn.addEventListener('click', function() {
                            console.log('✏️ Edit button clicked');
                            if (drawnItems.getLayers().length > 0) {
                                new L.EditToolbar.Edit(map, {
                                    featureGroup: drawnItems
                                }).enable();
                            } else {
                                alert('Tidak ada poligon untuk diedit. Silakan gambar poligon terlebih dahulu atau muat dari GeoJSON.');
                            }
                        });
                    }

                    const loadGeoJsonBtn = document.getElementById(loadGeoJsonBtnId);
                    if (loadGeoJsonBtn && hasGeoJsonFile) {
                        loadGeoJsonBtn.addEventListener('click', function() {
                            console.log('📁 EMERGENCY: Load GeoJSON button clicked');
                            loadGeoJsonFromModel();
                        });
                    }
                }

                // Make map responsive and handle tab switching
                window.addEventListener('resize', function() {
                    setTimeout(function() {
                        if (map) {
                            map.invalidateSize();
                        }
                    }, 100);
                });

                // Handle tab switching
                const observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                            const container = document.getElementById(mapId);
                            if (container && container.offsetWidth > 0) {
                                setTimeout(() => {
                                    if (map) {
                                        map.invalidateSize();
                                    }
                                }, 200);
                            }
                        }
                    });
                });

                observer.observe(mapContainer.parentElement, {
                    attributes: true,
                    attributeFilter: ['style', 'class'],
                    subtree: true
                });

                console.log('🎯 EMERGENCY: Final EDIT map state setup complete');
                return true;
            }

            // Multiple initialization attempts for tab compatibility
            document.addEventListener('DOMContentLoaded', function() {
                initializeMap_{{ str_replace(['-', '.'], ['_', '_'], $id) }}();
            });

            // Also try after a short delay for tab switching
            setTimeout(() => {
                initializeMap_{{ str_replace(['-', '.'], ['_', '_'], $id) }}();
            }, 1000);

            // Listen for tab changes (Filament specific)
            document.addEventListener('livewire:update', function() {
                setTimeout(() => {
                    initializeMap_{{ str_replace(['-', '.'], ['_', '_'], $id) }}();
                }, 200);
            });
        </script>
    @endpush

    @push('styles')
        <style>
            /* Fix z-index for Leaflet map controls to not overlap Filament UI */
            #{{ $id }}-map .leaflet-control-container {
                z-index: 10 !important;
            }

            #{{ $id }}-map .leaflet-control {
                z-index: 10 !important;
            }

            #{{ $id }}-map .leaflet-popup {
                z-index: 15 !important;
            }

            #{{ $id }}-map .leaflet-popup-pane {
                z-index: 15 !important;
            }

            #{{ $id }}-map .leaflet-tooltip {
                z-index: 12 !important;
            }

            #{{ $id }}-map .leaflet-draw-toolbar {
                z-index: 10 !important;
            }

            #{{ $id }}-map .leaflet-draw-actions {
                z-index: 11 !important;
            }

            /* Fix drawing controls z-index */
            .leaflet-draw-section {
                z-index: 10 !important;
            }

            .leaflet-draw-toolbar a {
                z-index: 10 !important;
            }
        </style>
    @endpush
</x-dynamic-component>
