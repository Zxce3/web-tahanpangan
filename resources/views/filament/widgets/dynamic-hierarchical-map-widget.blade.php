@php
    $data = $this->getData();
    $mapId = 'dynamic-hierarchical-map';
@endphp

<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-globe-asia-australia class="h-5 w-5" />
                Peta Hierarkis Indonesia
            </div>
        </x-slot>

        <x-slot name="headerEnd">
            <div class="flex items-center gap-2">
                <x-filament::badge id="current-level-badge" color="info" size="sm">
                    Provinsi
                </x-filament::badge>
                <x-filament::badge id="loaded-count-badge" color="success" size="sm">
                    0 Wilayah
                </x-filament::badge>
            </div>
        </x-slot>

        <div class="space-y-4">
            <!-- Map Container -->
            <div
                id="{{ $mapId }}"
                style="height: 600px; width: 100%; position: relative; z-index: 1;"
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
                                <li>• Zoom in untuk melihat detail lebih</li>
                                <li>• Klik wilayah untuk fokus</li>
                                <li>• Zoom out untuk kembali ke level atas</li>
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
                            Performa: Optimal
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

            console.log('🗺️ Initializing Dynamic Hierarchical Map');

            // Map state management
            let map = null;
            let currentLevel = 'province';
            let loadedLayers = new Map(); // Level -> LayerGroup
            let selectedRegion = null;
            let isLoading = false;
            let loadedDataCache = new Map(); // Cache for loaded data

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

                    // Add tiles
                    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        maxZoom: 19,
                        attribution: '© OpenStreetMap contributors'
                    }).addTo(map);

                    // Initialize layer groups for each level
                    loadedLayers.set('province', new L.LayerGroup().addTo(map));
                    loadedLayers.set('regency', new L.LayerGroup().addTo(map));
                    loadedLayers.set('district', new L.LayerGroup().addTo(map));

                    // Add event listeners
                    map.on('zoomend', handleZoomChange);
                    map.on('moveend', handleMapMove);

                    // Load initial data
                    loadDataForLevel('province');

                    console.log('✅ Dynamic map initialized');
                } catch (error) {
                    console.error('❌ Failed to initialize map:', error);
                }
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

                const oldLevel = currentLevel;
                currentLevel = newLevel;

                // Hide layers that are not appropriate for current zoom
                hideInappropriateLayers();

                // Load data for new level if needed
                if (shouldLoadLevel(newLevel)) {
                    loadDataForLevel(newLevel);
                }

                updateUI();
            }

            // Hide layers not appropriate for current zoom level
            function hideInappropriateLayers() {
                const zoom = map.getZoom();

                // Hide province level when zoomed in too much
                if (zoom > 8) {
                    loadedLayers.get('province').clearLayers();
                }

                // Hide regency level when zoomed out or in too much
                if (zoom <= 7 || zoom > 12) {
                    loadedLayers.get('regency').clearLayers();
                }

                // Hide district level when zoomed out
                if (zoom <= 10) {
                    loadedLayers.get('district').clearLayers();
                }
            }

            // Check if we should load data for a level
            function shouldLoadLevel(level) {
                // Check if data is already loaded or cached
                if (loadedDataCache.has(level)) return true;

                // Check if we have required parent selection for detailed levels
                if (level === 'regency' && !selectedRegion?.province) return false;
                if (level === 'district' && !selectedRegion?.regency_id) return false;

                return true;
            }

            // Load data for specific level
            async function loadDataForLevel(level) {
                if (isLoading) return;

                const cacheKey = getCacheKey(level);

                // Check cache first
                if (loadedDataCache.has(cacheKey)) {
                    console.log(`📋 Loading ${level} from cache`);
                    displayData(level, loadedDataCache.get(cacheKey));
                    return;
                }

                console.log(`🔄 Loading ${level} data from server`);
                setLoadingStatus(true, `Memuat data ${level}...`);

                try {
                    const url = getApiUrl(level);
                    const response = await fetch(url);
                    const result = await response.json();

                    if (result.success) {
                        loadedDataCache.set(cacheKey, result.data);
                        displayData(level, result.data);
                        console.log(`✅ Loaded ${result.data.length} ${level} items`);
                    } else {
                        console.error(`❌ Failed to load ${level}:`, result.message);
                    }
                } catch (error) {
                    console.error(`❌ Error loading ${level}:`, error);
                } finally {
                    setLoadingStatus(false);
                }
            }

            // Get cache key for current context
            function getCacheKey(level) {
                let key = level;
                if (level === 'regency' && selectedRegion?.province) {
                    key += `_${selectedRegion.province}`;
                }
                if (level === 'district' && selectedRegion?.regency_id) {
                    key += `_regency_${selectedRegion.regency_id}`;
                }
                return key;
            }

            // Get API URL for level
            function getApiUrl(level) {
                let url = `/api/map/${level}`;
                const params = new URLSearchParams();

                if (level === 'regency' && selectedRegion?.province) {
                    params.append('province', selectedRegion.province);
                }
                if (level === 'district' && selectedRegion?.regency_id) {
                    params.append('regency_id', selectedRegion.regency_id);
                }

                // Add map bounds for spatial filtering
                const bounds = map.getBounds();
                params.append('bounds', JSON.stringify({
                    north: bounds.getNorth(),
                    south: bounds.getSouth(),
                    east: bounds.getEast(),
                    west: bounds.getWest()
                }));

                return url + (params.toString() ? '?' + params.toString() : '');
            }

            // Display data on map
            function displayData(level, data) {
                const layerGroup = loadedLayers.get(level);
                if (!layerGroup) return;

                // Clear existing data for this level
                layerGroup.clearLayers();

                let displayedCount = 0;
                data.forEach(item => {
                    try {
                        const polygon = L.polygon(item.coordinates, {
                            color: item.color,
                            fillColor: item.color,
                            fillOpacity: 0.3,
                            weight: 2,
                            opacity: 0.8
                        });

                        // Add click handler for drilling down
                        polygon.on('click', () => handleRegionClick(item, level));

                        // Add hover effects
                        polygon.on('mouseover', function() {
                            this.setStyle({ fillOpacity: 0.6, weight: 3 });
                            showTooltip(item);
                        });

                        polygon.on('mouseout', function() {
                            this.setStyle({ fillOpacity: 0.3, weight: 2 });
                            hideTooltip();
                        });

                        // Bind popup
                        polygon.bindPopup(createPopupContent(item), {
                            maxWidth: 300
                        });

                        layerGroup.addLayer(polygon);
                        displayedCount++;
                    } catch (error) {
                        console.warn(`⚠️ Error displaying ${item.name}:`, error);
                    }
                });

                updateLoadedCount(displayedCount);
                optimizePerformance();
            }

            // Handle region click for drilling down
            function handleRegionClick(item, level) {
                console.log(`🎯 Clicked ${level}: ${item.name}`);

                selectedRegion = item;
                updateSelectionInfo(item);

                // Zoom to region and load child data
                if (level === 'province') {
                    // Focus on province and load regencies
                    map.setZoom(8);
                    setTimeout(() => loadDataForLevel('regency'), 300);
                } else if (level === 'regency') {
                    // Focus on regency and load districts
                    map.setZoom(11);
                    setTimeout(() => loadDataForLevel('district'), 300);
                } else if (level === 'district' && item.view_url) {
                    // Open district detail page
                    window.open(item.view_url, '_blank');
                }
            }

            // Create popup content
            function createPopupContent(item) {
                return `
                    <div style="padding: 12px; min-width: 180px;">
                        <h3 style="font-weight: bold; margin-bottom: 8px; color: ${item.color};">
                            ${item.name}
                        </h3>
                        <div style="font-size: 0.875rem;">
                            <div>Tingkat: <strong>${item.level}</strong></div>
                            ${item.code ? `<div>Kode: <span style="font-family: monospace;">${item.code}</span></div>` : ''}
                            ${item.province ? `<div>Provinsi: ${item.province}</div>` : ''}
                            ${item.child_count ? `<div>Sub-wilayah: ${item.child_count}</div>` : ''}
                            ${item.security_level ? `<div>Keamanan: <span style="color: ${item.color};">${getSecurityText(item.security_level)}</span></div>` : ''}
                            <div style="margin-top: 8px; font-size: 0.75rem; color: #666;">
                                ${item.level === 'district' ? 'Klik untuk detail →' : 'Klik untuk fokus →'}
                            </div>
                        </div>
                    </div>
                `;
            }

            // Helper functions
            function getSecurityText(level) {
                const levels = {
                    'low': 'Rendah',
                    'medium': 'Sedang',
                    'high': 'Tinggi',
                    'critical': 'Kritis'
                };
                return levels[level] || level;
            }

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
            }

            function updateSelectionInfo(item) {
                const infoEl = document.getElementById('selection-info');
                if (infoEl && item) {
                    infoEl.innerHTML = `
                        <div class="text-sm">
                            <div class="font-medium">${item.name}</div>
                            <div class="text-gray-500">Tingkat: ${item.level}</div>
                            ${item.child_count ? `<div class="text-gray-500">Sub-wilayah: ${item.child_count}</div>` : ''}
                        </div>
                    `;
                }
            }

            function optimizePerformance() {
                // Memory cleanup for old cached data
                if (loadedDataCache.size > 10) {
                    const keys = Array.from(loadedDataCache.keys());
                    keys.slice(0, 5).forEach(key => loadedDataCache.delete(key));
                }

                // Update performance info
                const perfEl = document.getElementById('performance-info');
                if (perfEl) {
                    const cacheSize = loadedDataCache.size;
                    const memoryUsage = cacheSize < 5 ? 'Optimal' : cacheSize < 10 ? 'Normal' : 'Tinggi';
                    perfEl.textContent = `Cache: ${cacheSize} | Memori: ${memoryUsage}`;
                }
            }

            function showTooltip(item) {
                // Could implement a floating tooltip here
            }

            function hideTooltip() {
                // Hide tooltip
            }

            function handleMapMove() {
                // Handle map movement if needed for spatial filtering
            }

            // Control buttons
            document.getElementById('zoom-to-indonesia')?.addEventListener('click', () => {
                map.setView(config.center, config.zoom);
                selectedRegion = null;
                currentLevel = 'province';
                loadDataForLevel('province');
                updateUI();
            });

            document.getElementById('clear-selection')?.addEventListener('click', () => {
                selectedRegion = null;
                updateSelectionInfo(null);
                // Clear cache for child levels
                Array.from(loadedDataCache.keys()).forEach(key => {
                    if (key.includes('_')) loadedDataCache.delete(key);
                });
            });

            // Initialize everything
            initializeMap();
        });
    </script>

    <style>
        #{{ $mapId }} .leaflet-control-container {
            z-index: 10 !important;
        }

        #{{ $mapId }} .leaflet-popup {
            z-index: 15 !important;
        }
    </style>
@endpush
