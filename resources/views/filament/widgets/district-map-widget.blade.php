{{-- filepath: /home/memet/works/tahanpangan/web-tahanpangan/resources/views/filament/widgets/district-map-widget.blade.php --}}
<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-map class="h-5 w-5 text-gray-500 dark:text-gray-400" />
                Peta Wilayah
            </div>
        </x-slot>

        <x-slot name="description">
            {{ $totalWithMap }} dari {{ $totalDistricts }} wilayah memiliki data peta
        </x-slot>

        <div class="space-y-6">
            <!-- Map placeholder with statistics -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-xl p-8 min-h-[320px] flex items-center justify-center border border-gray-200 dark:border-gray-700">
                <div class="text-center space-y-6 max-w-2xl">
                    <x-heroicon-o-map class="h-20 w-20 text-gray-400 dark:text-gray-500 mx-auto" />
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-white">
                            Peta Interaktif Wilayah
                        </h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-2">
                            Menampilkan {{ $totalWithMap }} wilayah dengan data geografis
                        </p>
                    </div>

                    <!-- Quick stats grid -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        @foreach($securityLevels as $level => $count)
                            <div class="bg-white dark:bg-gray-700 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-600 hover:shadow-md transition-shadow">
                                <div class="text-2xl font-bold mb-1
                                    @if($level === 'low') text-emerald-600 dark:text-emerald-400
                                    @elseif($level === 'medium') text-amber-600 dark:text-amber-400
                                    @elseif($level === 'high') text-red-600 dark:text-red-400
                                    @elseif($level === 'critical') text-gray-600 dark:text-gray-400
                                    @endif
                                ">
                                    {{ $count }}
                                </div>
                                <div class="text-xs font-medium text-gray-600 dark:text-gray-300">
                                    {{ match($level) {
                                        'low' => 'Aman',
                                        'medium' => 'Sedang',
                                        'high' => 'Tinggi',
                                        'critical' => 'Kritis',
                                        default => ucfirst($level)
                                    } }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="flex flex-col sm:flex-row justify-center gap-3 mt-6">
                        <a href="{{ \App\Filament\Resources\DistrictResource::getUrl('index') }}"
                           class="inline-flex items-center justify-center px-6 py-2.5 bg-primary-600 hover:bg-primary-700 dark:bg-primary-500 dark:hover:bg-primary-600 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm">
                            <x-heroicon-o-eye class="h-4 w-4 mr-2" />
                            Lihat Semua Wilayah
                        </a>
                        <a href="{{ \App\Filament\Resources\DistrictResource::getUrl('create') }}"
                           class="inline-flex items-center justify-center px-6 py-2.5 bg-gray-600 hover:bg-gray-700 dark:bg-gray-500 dark:hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors duration-200 shadow-sm">
                            <x-heroicon-o-plus class="h-4 w-4 mr-2" />
                            Tambah Wilayah
                        </a>
                    </div>
                </div>
            </div>

            <!-- Statistics cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-map-pin class="h-10 w-10 text-blue-500 dark:text-blue-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Wilayah</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalDistricts }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-document-text class="h-10 w-10 text-emerald-500 dark:text-emerald-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Dengan Data Peta</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">{{ $totalWithMap }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <x-heroicon-o-chart-bar class="h-10 w-10 text-purple-500 dark:text-purple-400" />
                        </div>
                        <div class="ml-4">
                            <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Kelengkapan Data</p>
                            <p class="text-3xl font-bold text-gray-900 dark:text-white">
                                {{ $totalDistricts > 0 ? round(($totalWithMap / $totalDistricts) * 100) : 0 }}%
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- District list with map data -->
            @if($districts->count() > 0)
                <div class="bg-white dark:bg-gray-800 rounded-xl p-6 shadow-sm border border-gray-200 dark:border-gray-700">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Wilayah dengan Data Peta
                        </h4>
                        @if($districts->count() > 6)
                            <a href="{{ \App\Filament\Resources\DistrictResource::getUrl('index') }}"
                               class="text-sm font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition-colors">
                                Lihat Semua →
                            </a>
                        @endif
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($districts->take(6) as $district)
                            <div class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">
                                <div class="flex items-center space-x-3 min-w-0 flex-1">
                                    <x-heroicon-o-map-pin class="h-5 w-5 text-emerald-500 dark:text-emerald-400 flex-shrink-0" />
                                    <div class="min-w-0 flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                            {{ $district->name }}
                                        </p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                            {{ $district->regency->name ?? 'No regency' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium ml-3 flex-shrink-0
                                    @if($district->security_level === 'low') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300
                                    @elseif($district->security_level === 'medium') bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300
                                    @elseif($district->security_level === 'high') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300
                                    @elseif($district->security_level === 'critical') bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                    @endif
                                ">
                                    {{ match($district->security_level) {
                                        'low' => 'Aman',
                                        'medium' => 'Sedang',
                                        'high' => 'Tinggi',
                                        'critical' => 'Kritis',
                                        default => ucfirst($district->security_level)
                                    } }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
