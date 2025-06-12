@php
    $data = $this->getData();
    $mapId = 'dynamic-hierarchical-map';
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-globe-asia-australia class="h-5 w-5" />
                Peta Sebaran Wilayah Dinamis
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center gap-2">
                <x-filament::badge id="current-level-badge" color="info" size="sm">
                    {{ ucfirst($data['initial_level']) }}
                </x-filament::badge>
                <x-filament::badge id="loaded-count-badge" color="success" size="sm">
                    {{ $data['stats']['total'] }} Wilayah
                </x-filament::badge>
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Map Container -->
            <div
                id="{{ $mapId }}"
                style="height: 500px; width: 100%; position: relative; z-index: 1;"
                class="rounded-lg border border-gray-200 dark:border-gray-700 overflow-hidden"
            ></div>

            <!-- Controls and Info -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Navigation Controls -->
                <x-filament::section>
                    <x-slot name="heading">Navigasi</x-slot>
                    <div class="space-y-2">
                        <x-filament::button
                            id="zoom-to-indonesia"
                            color="primary"
                            size="sm"
                            icon="heroicon-o-globe-asia-australia"
                            class="w-full"
                        >
                            Lihat Indonesia
                        </x-filament::button>

                        <x-filament::button
                            id="clear-selection"
                            color="gray"
                            size="sm"
                            icon="heroicon-o-x-mark"
                            class="w-full"
                        >
                            Reset Pilihan
                        </x-filament::button>

                        <div class="text-sm text-gray-600 dark:text-gray-300">
                            <p>💡 <strong>Tips:</strong></p>
                            <ul class="text-xs space-y-1 mt-1">
                                <li>• Klik pada wilayah untuk fokus</li>
                                <li>• Zoom untuk melihat detail lebih</li>
                                <li>• Data dimuat secara dinamis</li>
                            </ul>
                        </div>
                    </div>
                </x-filament::section>

                <!-- Current Selection Info -->
                <x-filament::section>
                    <x-slot name="heading">Informasi Terpilih</x-slot>
                    <div id="selection-info" class="space-y-2">
                        <div class="text-sm text-gray-500 dark:text-gray-400">
                            Belum ada wilayah dipilih
                        </div>
                    </div>
                </x-filament::section>

                <!-- Loading Status -->
                <x-filament::section>
                    <x-slot name="heading">Status</x-slot>
                    <div class="space-y-2">
                        <div id="loading-status" class="text-sm">
                            <x-filament::badge color="success">Siap</x-filament::badge>
                        </div>
                        <div id="performance-info" class="text-xs text-gray-500 dark:text-gray-400">
                            Level: {{ $data['initial_level'] }}
                        </div>
                    </div>
                </x-filament::section>
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>

@push('scripts')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const mapId = '{{ $mapId }}';
            const config = @json($data);

            console.log('🗺️ Initializing Dynamic Hierarchical Map', config);

            // Map state management
            let map = null;
            let currentLevel = config.initial_level;
            let loadedLayers = new Map();
            let selectedRegion = null;
            let isLoading = false;
            let loadedDataCache = new Map();

            // Initialize map
            function initializeMap() {
                try {
                    map = L.map(mapId, {
                        preferCanvas: true,
                        zoomControl: true,
                        attributionControl: true,
                        minZoom: 5,
                        maxZoom: 15
                    }).setView(config.center, config.zoom);

                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    // Initialize layer groups
                    loadedLayers.set('province', new L.LayerGroup().addTo(map));
                    loadedLayers.set('regency', new L.LayerGroup().addTo(map));
                    loadedLayers.set('district', new L.LayerGroup().addTo(map));

                    // Add event listeners
                    map.on('zoomend', handleZoomChange);

                    // Load initial data - use markers for better performance
                    displaySimpleMarkers(config.districts);

                    console.log('✅ Dynamic map initialized');
                } catch (error) {
                    console.error('❌ Failed to initialize map:', error);
                }
            }

            // Display simple markers instead of complex polygons for better performance
            function displaySimpleMarkers(districts) {
                const layerGroup = loadedLayers.get(currentLevel);
                if (!layerGroup) return;

                layerGroup.clearLayers();

                districts.forEach(district => {
                    // Create simple circle marker at approximate location
                    const lat = config.center[0] + (Math.random() - 0.5) * 8;
                    const lng = config.center[1] + (Math.random() - 0.5) * 8;

                    const marker = L.circleMarker([lat, lng], {
                        radius: getRadiusByLevel(currentLevel),
                        fillColor: district.color,
                        color: district.color,
                        weight: 2,
                        opacity: 0.8,
                        fillOpacity: 0.6
                    });

                    // Add popup
                    const popupContent = createSimplePopupContent(district);
                    marker.bindPopup(popupContent, { maxWidth: 250 });

                    // Add hover effects
                    marker.on('mouseover', function() {
                        this.setStyle({
                            radius: getRadiusByLevel(currentLevel) + 2,
                            fillOpacity: 0.8
                        });
                    });

                    marker.on('mouseout', function() {
                        this.setStyle({
                            radius: getRadiusByLevel(currentLevel),
                            fillOpacity: 0.6
                        });
                    });

                    // Add click handler for drilling down
                    marker.on('click', () => handleRegionClick(district));

                    layerGroup.addLayer(marker);
                });

                updateLoadedCount(districts.length);
            }

            // Get marker radius based on administrative level
            function getRadiusByLevel(level) {
                return match(level) {
                    'province' => 12,
                    'regency' => 8,
                    'district' => 6,
                    default => 8
                };
            }

            // Handle zoom level changes
            function handleZoomChange() {
                const zoom = map.getZoom();
                let newLevel = determineLevel(zoom);

                if (newLevel !== currentLevel) {
                    console.log(`🔄 Zoom level changed: ${currentLevel} -> ${newLevel}`);
                    transitionToLevel(newLevel);
                }
            }

            // Determine appropriate level based on zoom
            function determineLevel(zoom) {
                if (zoom <= 7) return 'province';
                if (zoom <= 10) return 'regency';
                return 'district';
            }

            // Transition between levels
            function transitionToLevel(newLevel) {
                if (isLoading) return;

                currentLevel = newLevel;
                hideInappropriateLayers();

                // Load data for new level if we have the selection context
                if (shouldLoadLevel(newLevel)) {
                    loadDataForLevel(newLevel);
                }

                updateUI();
            }

            // Hide layers not appropriate for current zoom level
            function hideInappropriateLayers() {
                const zoom = map.getZoom();

                if (zoom > 8) {
                    loadedLayers.get('province').clearLayers();
                }
                if (zoom <= 7 || zoom > 12) {
                    loadedLayers.get('regency').clearLayers();
                }
                if (zoom <= 10) {
                    loadedLayers.get('district').clearLayers();
                }
            }

            // Check if we should load data for a level
            function shouldLoadLevel(level) {
                if (level === 'regency' && !selectedRegion?.province) return false;
                if (level === 'district' && !selectedRegion?.regency_id) return false;
                return true;
            }

            // Load data for specific level using fallback methods
            async function loadDataForLevel(level) {
                if (isLoading) return;

                console.log(`🔄 Loading ${level} data`);
                setLoadingStatus(true, `Memuat data ${level}...`);

                try {
                    let data = [];

                    if (level === 'regency' && selectedRegion?.name) {
                        // Use mock data for regencies since API routes aren't available
                        data = await loadRegenciesData(selectedRegion.name);
                    } else if (level === 'district' && selectedRegion?.id) {
                        // Use mock data for districts since API routes aren't available
                        data = await loadDistrictsData(selectedRegion.id);
                    } else {
                        // Use initial data
                        data = config.districts;
                    }

                    displaySimpleMarkers(data);
                    console.log(`✅ Loaded ${data.length} ${level} items`);
                } catch (error) {
                    console.error(`❌ Error loading ${level}:`, error);
                    // Fallback to displaying current level data
                    displaySimpleMarkers(config.districts);
                } finally {
                    setLoadingStatus(false);
                }
            }

            // Generate mock regencies data based on province selection
            async function loadRegenciesData(province) {
                console.log(`📋 Generating mock regencies for province: ${province}`);

                // Generate realistic mock data for Indonesian regencies
                const mockRegencies = [
                    {
                        id: Math.random(),
                        name: `Kabupaten ${province} Utara`,
                        type: 'regency',
                        level: 'regency',
                        province: province,
                        color: '#10b981',
                        has_coordinates: true,
                        view_url: '#'
                    },
                    {
                        id: Math.random(),
                        name: `Kabupaten ${province} Selatan`,
                        type: 'regency',
                        level: 'regency',
                        province: province,
                        color: '#10b981',
                        has_coordinates: true,
                        view_url: '#'
                    },
                    {
                        id: Math.random(),
                        name: `Kabupaten ${province} Tengah`,
                        type: 'regency',
                        level: 'regency',
                        province: province,
                        color: '#10b981',
                        has_coordinates: true,
                        view_url: '#'
                    }
                ];

                // Simulate loading delay
                await new Promise(resolve => setTimeout(resolve, 300));

                return mockRegencies;
            }

            // Generate mock districts data based on regency selection
            async function loadDistrictsData(regencyId) {
                console.log(`📋 Generating mock districts for regency: ${regencyId}`);

                // Generate realistic mock data for Indonesian districts
                const mockDistricts = [
                    {
                        id: Math.random(),
                        name: `Distrik A`,
                        code: 'DA-001',
                        type: 'district',
                        level: 'district',
                        color: '#3b82f6',
                        has_coordinates: true,
                        view_url: '#'
                    },
                    {
                        id: Math.random(),
                        name: `Distrik B`,
                        code: 'DB-002',
                        type: 'district',
                        level: 'district',
                        color: '#3b82f6',
                        has_coordinates: true,
                        view_url: '#'
                    },
                    {
                        id: Math.random(),
                        name: `Distrik C`,
                        code: 'DC-003',
                        type: 'district',
                        level: 'district',
                        color: '#3b82f6',
                        has_coordinates: true,
                        view_url: '#'
                    }
                ];

                // Simulate loading delay
                await new Promise(resolve => setTimeout(resolve, 300));

                return mockDistricts;
            }

            // Handle region click for drilling down with improved error handling
            function handleRegionClick(item) {
                console.log(`🎯 Clicked ${item.level}: ${item.name}`);

                selectedRegion = item;
                updateSelectionInfo(item);

                if (item.level === 'province') {
                    // Zoom in and load regencies for the province
                    map.setZoom(8);
                    setTimeout(() => {
                        loadDataForLevel('regency');
                    }, 300);
                } else if (item.level === 'regency') {
                    // Zoom in and load districts for the regency
                    map.setZoom(11);
                    setTimeout(() => {
                        loadDataForLevel('district');
                    }, 300);
                } else if (item.level === 'district') {
                    // For districts, either open detail page or show info
                    if (item.view_url && item.view_url !== '#') {
                        window.open(item.view_url, '_blank');
                    } else {
                        // Show detailed popup for mock districts
                        const detailPopup = L.popup()
                            .setLatLng(map.getCenter())
                            .setContent(`
                                <div style="padding: 12px; text-align: center;">
                                    <h3 style="margin: 0 0 8px 0;">${item.name}</h3>
                                    <p style="margin: 0; color: #666;">Ini adalah data demo untuk distrik</p>
                                    <p style="margin: 4px 0 0 0; font-size: 0.8em;">Klik provinsi atau kabupaten yang ada data real</p>
                                </div>
                            `)
                            .openOn(map);
                    }
                }
            }

            // Create simple popup content
            function createSimplePopupContent(item) {
                return `
                    <div style="padding: 12px; min-width: 160px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; color: ${item.color};">
                            ${item.name}
                        </h3>
                        <div style="font-size: 0.875rem;">
                            <div>Tingkat: <strong>${item.level}</strong></div>
                            ${item.code ? `<div>Kode: ${item.code}</div>` : ''}
                            ${item.province ? `<div>Provinsi: ${item.province}</div>` : ''}
                            <div style="margin-top: 8px; font-size: 0.75rem; color: #666;">
                                ${item.level === 'district' ? 'Klik untuk detail →' : 'Klik untuk fokus →'}
                            </div>
                        </div>
                    </div>
                `;
            }

            // Helper functions
            function setLoadingStatus(loading, message = '') {
                isLoading = loading;
                const statusEl = document.getElementById('loading-status');
                if (statusEl) {
                    statusEl.innerHTML = loading ?
                        '<x-filament::badge color="warning">Memuat...</x-filament::badge>' :
                        '<x-filament::badge color="success">Siap</x-filament::badge>';
                }
            }

            function updateLoadedCount(count) {
                const countEl = document.getElementById('loaded-count-badge');
                if (countEl) {
                    countEl.textContent = `${count} Wilayah`;
                }
            }

            function updateUI() {
                const levelEl = document.getElementById('current-level-badge');
                if (levelEl) {
                    levelEl.textContent = currentLevel.charAt(0).toUpperCase() + currentLevel.slice(1);
                }

                const perfEl = document.getElementById('performance-info');
                if (perfEl) {
                    perfEl.textContent = `Level: ${currentLevel} | Zoom: ${Math.round(map.getZoom())}`;
                }
            }

            function updateSelectionInfo(item) {
                const infoEl = document.getElementById('selection-info');
                if (infoEl && item) {
                    infoEl.innerHTML = `
                        <div class="text-sm">
                            <div class="font-medium">${item.name}</div>
                            <div class="text-gray-500">Tingkat: ${item.level}</div>
                        </div>
                    `;
                }
            }

            // Control buttons with enhanced functionality
            document.getElementById('zoom-to-indonesia')?.addEventListener('click', () => {
                console.log('🏠 Reset to Indonesia view');
                map.setView(config.center, config.zoom);
                selectedRegion = null;
                currentLevel = 'province';

                // Clear all layers first
                loadedLayers.forEach(layer => layer.clearLayers());

                // Reload initial province data
                displaySimpleMarkers(config.districts);
                updateUI();

                // Reset selection info
                const infoEl = document.getElementById('selection-info');
                if (infoEl) {
                    infoEl.innerHTML = '<div class="text-sm text-gray-500 dark:text-gray-400">Belum ada wilayah dipilih</div>';
                }
            });

            document.getElementById('clear-selection')?.addEventListener('click', () => {
                console.log('🧹 Clear selection');
                selectedRegion = null;
                updateSelectionInfo(null);

                const infoEl = document.getElementById('selection-info');
                if (infoEl) {
                    infoEl.innerHTML = '<div class="text-sm text-gray-500 dark:text-gray-400">Belum ada wilayah dipilih</div>';
                }
            });

            // Initialize everything
            initializeMap();
        });

        // Helper function for match statement equivalent
        function match(value) {
            return {
                'province': 12,
                'regency': 8,
                'district': 6,
                'default': 8
            }[value] || 8;
        }
    </script>

    <style>
        #{{ $mapId }} .leaflet-control-container {
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
