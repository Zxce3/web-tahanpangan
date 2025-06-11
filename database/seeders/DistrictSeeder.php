<?php

namespace Database\Seeders;

use App\Models\District;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class DistrictSeeder extends Seeder
{
    private array $usedCodes = [];
    private array $availableProvinceFiles = [];
    private array $availableRegencyFiles = [];
    private array $availableDistrictFiles = [];

    /**
     * Get province name for a given regency
     */
    private function getProvinceForRegency(string $regencyName): string
    {
        // Create a mapping from the JSON data
        static $regencyToProvince = [];

        if (empty($regencyToProvince)) {
            $jsonPath = resource_path('maps/papua_structured_data/papua_districts_list.json');
            if (File::exists($jsonPath)) {
                $data = json_decode(File::get($jsonPath), true);
                foreach ($data as $item) {
                    $regencyToProvince[$item['regency']] = $item['province'];
                }
            }
        }

        return $regencyToProvince[$regencyName] ?? 'Papua';
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear existing districts to avoid duplicates
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        District::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Reset used codes
        $this->usedCodes = [];

        // Build available file mappings
        $this->buildAvailableFileMappings();

        // List available files for debugging
        $this->listAvailableGeoJsonFiles();

        // Load Papua districts data from JSON
        $jsonPath = resource_path('maps/papua_structured_data/papua_districts_list.json');
        if (!File::exists($jsonPath)) {
            $this->command->error("Papua districts JSON file not found at: {$jsonPath}");
            return;
        }

        $papuaDistrictsData = json_decode(File::get($jsonPath), true);
        if (!$papuaDistrictsData) {
            $this->command->error("Failed to parse Papua districts JSON file");
            return;
        }

        $this->command->info('');
        $this->command->info('🚀 Starting seeding process...');

        // Create provinces first
        $provinces = $this->createProvinces($papuaDistrictsData);

        // Create regencies
        $regencies = $this->createRegencies($papuaDistrictsData, $provinces);

        // Create districts
        $this->createDistrictsFromData($papuaDistrictsData, $regencies);

        // Show summary
        $this->showSeederSummary($provinces, $regencies, $papuaDistrictsData);
    }

    /**
     * Build mappings of available GeoJSON files
     */
    private function buildAvailableFileMappings(): void
    {
        // Build province file mapping - simple underscore replacement
        $provinceFiles = File::glob(resource_path('maps/papua_provinces/*.geojson'));
        foreach ($provinceFiles as $file) {
            $fileName = basename($file, '.geojson');
            $provinceName = str_replace('_', ' ', $fileName);
            $this->availableProvinceFiles[$provinceName] = $fileName;
        }

        // Build regency file mapping - parse {Regency}_{Province} pattern
        $regencyFiles = File::glob(resource_path('maps/papua_regencies/*.geojson'));
        foreach ($regencyFiles as $file) {
            $fileName = basename($file, '.geojson');

            // Try to match against known province patterns
            foreach ($this->availableProvinceFiles as $provinceName => $provinceFile) {
                $provincePattern = str_replace(' ', '_', $provinceName);
                if (str_ends_with($fileName, '_' . $provincePattern)) {
                    $regencyPart = str_replace('_' . $provincePattern, '', $fileName);
                    $regencyName = str_replace('_', ' ', $regencyPart);
                    $this->availableRegencyFiles[$regencyName] = $fileName;
                    break;
                }
            }
        }

        // Build district file mapping - improved logic for better matching
        $districtFiles = File::glob(resource_path('maps/papua_districts_detailed/*.geojson'));

        // First, create a more comprehensive regency pattern list
        $regencyPatterns = [];
        foreach ($this->availableRegencyFiles as $regencyName => $regencyFile) {
            // Create multiple possible patterns for each regency
            $patterns = [
                str_replace(' ', '_', $regencyName), // Standard pattern
                str_replace([' ', '-'], '_', $regencyName), // Handle hyphens
                preg_replace('/[^A-Za-z0-9_]/', '_', $regencyName), // Handle special chars
            ];

            foreach ($patterns as $pattern) {
                $regencyPatterns[$pattern] = $regencyName;
            }
        }

        // Add direct mapping for known problematic cases
        $regencyPatterns['Kota_Jayapura'] = 'Kota Jayapura';
        $regencyPatterns['Kota_Sorong'] = 'Kota Sorong';
        $regencyPatterns['Pegunungan_Bintang'] = 'Pegunungan Bintang';
        $regencyPatterns['Boven_Digoel'] = 'Boven Digoel';
        $regencyPatterns['Puncak_Jaya'] = 'Puncak Jaya';
        $regencyPatterns['Intan_Jaya'] = 'Intan Jaya';
        $regencyPatterns['Lanny_Jaya'] = 'Lanny Jaya';
        $regencyPatterns['Mamberamo_Raya'] = 'Mamberamo Raya';
        $regencyPatterns['Mamberamo_Tengah'] = 'Mamberamo Tengah';
        $regencyPatterns['Biak_Numfor'] = 'Biak Numfor';
        $regencyPatterns['Kepulauan_Yapen'] = 'Kepulauan Yapen';
        $regencyPatterns['Raja_Ampat'] = 'Raja Ampat';
        $regencyPatterns['Sorong_Selatan'] = 'Sorong Selatan';
        $regencyPatterns['Teluk_Wondama'] = 'Teluk Wondama';
        $regencyPatterns['Teluk_Bintuni'] = 'Teluk Bintuni';
        $regencyPatterns['Fak_Fak'] = 'Fak Fak';
        $regencyPatterns['Manokwari_Selatan'] = 'Manokwari Selatan';
        $regencyPatterns['Pegunungan_Arfak'] = 'Pegunungan Arfak';

        // Build comprehensive district mappings with fallback mechanisms
        foreach ($districtFiles as $file) {
            $fileName = basename($file, '.geojson');

            // Handle specific problematic file names first
            $this->handleSpecificFilePatterns($fileName, $regencyPatterns);

            // Try to find the best matching regency pattern
            $bestMatch = null;
            $longestMatch = 0;

            foreach ($regencyPatterns as $pattern => $regencyName) {
                if (str_ends_with($fileName, '_' . $pattern)) {
                    if (strlen($pattern) > $longestMatch) {
                        $longestMatch = strlen($pattern);
                        $bestMatch = [
                            'pattern' => $pattern,
                            'regency' => $regencyName
                        ];
                    }
                }
            }

            if ($bestMatch) {
                $districtPart = str_replace('_' . $bestMatch['pattern'], '', $fileName);
                $districtName = str_replace('_', ' ', $districtPart);
                $key = $districtName . '|' . $bestMatch['regency'];
                $this->availableDistrictFiles[$key] = $fileName;

                // Create alternative mappings for problematic district names
                $this->createAlternativeDistrictMappings($districtName, $bestMatch['regency'], $fileName);
            }
        }

        $this->command->info('📂 Built file mappings:');
        $this->command->info('   - ' . count($this->availableProvinceFiles) . ' Province files mapped');
        $this->command->info('   - ' . count($this->availableRegencyFiles) . ' Regency files mapped');
        $this->command->info('   - ' . count($this->availableDistrictFiles) . ' District files mapped');

        // Debug: Show some sample mappings
        if (!empty($this->availableProvinceFiles)) {
            $this->command->info('📋 Sample province mappings:');
            foreach (array_slice($this->availableProvinceFiles, 0, 3, true) as $name => $file) {
                $this->command->info("   '{$name}' → {$file}.geojson");
            }
        }

        if (!empty($this->availableRegencyFiles)) {
            $this->command->info('📋 Sample regency mappings:');
            foreach (array_slice($this->availableRegencyFiles, 0, 3, true) as $name => $file) {
                $this->command->info("   '{$name}' → {$file}.geojson");
            }
        }

        if (!empty($this->availableDistrictFiles)) {
            $this->command->info('📋 Sample district mappings:');
            foreach (array_slice($this->availableDistrictFiles, 0, 5, true) as $key => $file) {
                $this->command->info("   '{$key}' → {$file}.geojson");
            }
        }

        // Debug: Check specific cases
        $this->debugSpecificCases();
    }

    /**
     * Handle specific file patterns that don't follow standard naming
     */
    private function handleSpecificFilePatterns(string $fileName, array $regencyPatterns): void
    {
        // Handle Misool_Misool_Utara_Raja_Ampat.geojson
        if ($fileName === 'Misool_Misool_Utara_Raja_Ampat') {
            $this->availableDistrictFiles['Misool (Misool Utara)|Raja Ampat'] = $fileName;
            $this->availableDistrictFiles['Misool|Raja Ampat'] = $fileName;
            return;
        }

        // Handle Konda__Kondaga_Tolikara.geojson (double underscore)
        if ($fileName === 'Konda__Kondaga_Tolikara') {
            $this->availableDistrictFiles['Konda/ Kondaga|Tolikara'] = $fileName;
            $this->availableDistrictFiles['Konda Kondaga|Tolikara'] = $fileName;
            return;
        }

        // Handle other double underscore patterns
        if (str_contains($fileName, '__')) {
            // Try to parse double underscore as space-slash pattern
            foreach ($regencyPatterns as $pattern => $regencyName) {
                if (str_ends_with($fileName, '_' . $pattern)) {
                    $districtPart = str_replace('_' . $pattern, '', $fileName);

                    // Replace double underscore with "/ "
                    if (str_contains($districtPart, '__')) {
                        $cleanDistrictName = str_replace('__', '/ ', $districtPart);
                        $key = $cleanDistrictName . '|' . $regencyName;
                        $this->availableDistrictFiles[$key] = $fileName;

                        // Also create alternative without the slash
                        $altDistrictName = str_replace('__', ' ', $districtPart);
                        $altKey = $altDistrictName . '|' . $regencyName;
                        $this->availableDistrictFiles[$altKey] = $fileName;
                        return;
                    }
                }
            }
        }
    }

    /**
     * Create alternative district mappings for problematic names
     */
    private function createAlternativeDistrictMappings(string $districtName, string $regencyName, string $fileName): void
    {
        // Handle parentheses - remove content in parentheses
        if (str_contains($districtName, '(') && str_contains($districtName, ')')) {
            $alternativeName = trim(preg_replace('/\([^)]*\)/', '', $districtName));
            if ($alternativeName !== $districtName) {
                $key = $alternativeName . '|' . $regencyName;
                $this->availableDistrictFiles[$key] = $fileName;
            }
        }

        // Handle forward slashes - create alternatives with different slash handling
        if (str_contains($districtName, '/')) {
            // Replace / with space
            $alternative1 = str_replace('/', ' ', $districtName);
            $key1 = $alternative1 . '|' . $regencyName;
            $this->availableDistrictFiles[$key1] = $fileName;

            // Take only first part before /
            $alternative2 = trim(explode('/', $districtName)[0]);
            $key2 = $alternative2 . '|' . $regencyName;
            $this->availableDistrictFiles[$key2] = $fileName;
        }

        // Handle specific known problematic cases
        $knownMappings = [
            'Misool (Misool Utara)' => 'Misool Utara',
            'Wari/Taiyeve II' => 'Wari Taiyeve II',
            'Konda/ Kondaga' => 'Konda Kondaga',
            'Fak-Fak Tengah' => 'Fak Fak Tengah',
            'Fak-Fak' => 'Fak Fak',
            'Fak-Fak Barat' => 'Fak Fak Barat',
            'Fak-Fak Timur' => 'Fak Fak Timur',
            'Suru-suru' => 'Suru suru',
            'Citak-Mitak' => 'Citak Mitak',
        ];

        foreach ($knownMappings as $problematic => $clean) {
            if ($districtName === $clean) {
                $key = $problematic . '|' . $regencyName;
                $this->availableDistrictFiles[$key] = $fileName;
            }
        }

        // Handle the two remaining problematic cases specifically
        $specificCases = [
            // Maps "Misool Utara|Raja Ampat" to "Misool (Misool Utara)|Raja Ampat"
            'Misool Utara|Raja Ampat' => 'Misool (Misool Utara)|Raja Ampat',
            // Maps "Konda Kondaga|Tolikara" to "Konda/ Kondaga|Tolikara"
            'Konda Kondaga|Tolikara' => 'Konda/ Kondaga|Tolikara',
        ];

        foreach ($specificCases as $cleanKey => $problematicKey) {
            $currentKey = $districtName . '|' . $regencyName;
            if ($currentKey === $cleanKey) {
                $this->availableDistrictFiles[$problematicKey] = $fileName;
            }
        }
    }

    /**
     * Debug specific problematic cases
     */
    private function debugSpecificCases(): void
    {
        $testCases = [
            'Abepura|Kota Jayapura',
            'Misool (Misool Utara)|Raja Ampat',
            'Wari/Taiyeve II|Tolikara',
            'Konda/ Kondaga|Tolikara',
            'Bewani|Tolikara'
        ];

        $this->command->info('🔍 Debugging specific cases:');
        foreach ($testCases as $testCase) {
            if (isset($this->availableDistrictFiles[$testCase])) {
                $this->command->info("   ✅ '{$testCase}' → {$this->availableDistrictFiles[$testCase]}.geojson");
            } else {
                $this->command->warn("   ❌ '{$testCase}' not found");

                // Show similar keys
                $district = explode('|', $testCase)[0];
                $regency = explode('|', $testCase)[1];
                $similar = [];
                foreach ($this->availableDistrictFiles as $key => $file) {
                    if (str_contains($key, $regency) &&
                        (str_contains(strtolower($key), strtolower($district)) ||
                         str_contains(strtolower($district), strtolower(explode('|', $key)[0])))) {
                        $similar[] = $key;
                    }
                }
                if (!empty($similar)) {
                    $this->command->info("     Similar keys: " . implode(', ', array_slice($similar, 0, 3)));
                }
            }
        }
    }

    private function createProvinces(array $districtsData): array
    {
        $provinces = [];
        $uniqueProvinces = collect($districtsData)->pluck('province')->unique();

        foreach ($uniqueProvinces as $provinceName) {
            $code = $this->generateUniqueProvinceCode($provinceName);

            $province = District::create([
                'name' => $provinceName,
                'code' => $code,
                'province' => $provinceName,
                'geojson_file_path' => $this->getProvinceGeoJsonPath($provinceName),
                'security_level' => 'medium',
                'population' => $this->getProvincePopulation($provinceName),
                'area_hectares' => $this->getProvinceArea($provinceName),
                'administrative_level' => 'province',
                'parent_district_id' => null,
                'is_active' => true,
            ]);

            $provinces[$provinceName] = $province;
            $this->command->info("✅ Created province: {$provinceName} ({$code})");
        }

        return $provinces;
    }

    private function createRegencies(array $districtsData, array $provinces): array
    {
        $regencies = [];

        foreach ($districtsData as $districtData) {
            $regencyName = $districtData['regency'];
            $provinceName = $districtData['province'];

            if (!isset($regencies[$regencyName])) {
                $code = $this->generateUniqueRegencyCode($regencyName, $provinceName);

                $regency = District::create([
                    'name' => $regencyName,
                    'code' => $code,
                    'province' => $provinceName,
                    'geojson_file_path' => $this->getRegencyGeoJsonPath($regencyName, $provinceName),
                    'security_level' => 'medium',
                    'population' => $this->getRegencyPopulation($regencyName),
                    'area_hectares' => $this->getRegencyArea($regencyName),
                    'administrative_level' => 'regency',
                    'parent_district_id' => $provinces[$provinceName]->id,
                    'is_active' => true,
                ]);

                $regencies[$regencyName] = $regency;
                $this->command->info("✅ Created regency: {$regencyName} in {$provinceName} ({$code})");
            }
        }

        return $regencies;
    }

    private function createDistrictsFromData(array $districtsData, array $regencies): void
    {
        $processedCount = 0;
        foreach ($districtsData as $index => $districtData) {
            $districtName = $districtData['district'];
            $regencyName = $districtData['regency'];
            $provinceName = $districtData['province'];

            $code = $this->generateUniqueDistrictCode($districtName, $regencyName);

            $district = District::create([
                'name' => $districtName,
                'code' => $code,
                'regency_id' => $regencies[$regencyName]->id,
                'province' => $provinceName,
                'geojson_file_path' => $this->getDistrictGeoJsonPath($districtName, $regencyName),
                'custom_coordinates' => null, // Start with null, will be filled when user edits
                'security_level' => $this->getDistrictSecurityLevel($districtName, $regencyName),
                'population' => rand(5000, 50000),
                'area_hectares' => rand(10000, 100000),
                'administrative_level' => 'district',
                'parent_district_id' => $regencies[$regencyName]->id,
                'is_active' => true,
            ]);

            $processedCount++;
            if ($processedCount % 50 === 0) {
                $this->command->info("📍 Created {$processedCount} districts...");
            }
        }
    }

    /**
     * Get GeoJSON file path for province
     */
    private function getProvinceGeoJsonPath(string $provinceName): ?string
    {
        if (isset($this->availableProvinceFiles[$provinceName])) {
            $fileName = $this->availableProvinceFiles[$provinceName];
            $filePath = "maps/papua_provinces/{$fileName}.geojson";

            if (File::exists(resource_path($filePath))) {
                $this->logGeoJsonAttempt('Province', $provinceName, true);
                return $filePath;
            }
        }

        $this->logGeoJsonAttempt('Province', $provinceName, false);
        return null;
    }

    /**
     * Get GeoJSON file path for regency
     */
    private function getRegencyGeoJsonPath(string $regencyName, string $provinceName): ?string
    {
        if (isset($this->availableRegencyFiles[$regencyName])) {
            $fileName = $this->availableRegencyFiles[$regencyName];
            $filePath = "maps/papua_regencies/{$fileName}.geojson";

            if (File::exists(resource_path($filePath))) {
                $this->logGeoJsonAttempt('Regency', $regencyName, true);
                return $filePath;
            }
        }

        $this->logGeoJsonAttempt('Regency', $regencyName, false);
        return null;
    }

    /**
     * Get GeoJSON file path for district
     */
    private function getDistrictGeoJsonPath(string $districtName, string $regencyName): ?string
    {
        $districtKey = $districtName . '|' . $regencyName;
        if (isset($this->availableDistrictFiles[$districtKey])) {
            $fileName = $this->availableDistrictFiles[$districtKey];
            $filePath = "maps/papua_districts_detailed/{$fileName}.geojson";

            if (File::exists(resource_path($filePath))) {
                $this->logGeoJsonAttempt('District', "{$districtName} ({$regencyName})", true);
                return $filePath;
            }
        }

        $this->logGeoJsonAttempt('District', "{$districtName} ({$regencyName})", false);
        return null;
    }

    private function generateUniqueProvinceCode(string $provinceName): string
    {
        $baseCodes = [
            'Papua' => 'ID-PA',
            'Papua Barat' => 'ID-PB',
            'Papua Barat Daya' => 'ID-PBD',
            'Papua Pegunungan' => 'ID-PP',
            'Papua Selatan' => 'ID-PS',
            'Papua Tengah' => 'ID-PT',
        ];

        $baseCode = $baseCodes[$provinceName] ?? 'ID-' . strtoupper(substr($provinceName, 0, 2));

        // Don't use ensureUniqueCode for provinces - they should be fixed
        $this->usedCodes[] = $baseCode;
        return $baseCode;
    }

    private function generateUniqueRegencyCode(string $regencyName, string $provinceName): string
    {
        // Get fixed province code
        $provinceCodeMap = [
            'Papua' => 'ID-PA',
            'Papua Barat' => 'ID-PB',
            'Papua Barat Daya' => 'ID-PBD',
            'Papua Pegunungan' => 'ID-PP',
            'Papua Selatan' => 'ID-PS',
            'Papua Tengah' => 'ID-PT',
        ];

        $provinceCode = $provinceCodeMap[$provinceName] ?? 'ID-UN';

        // Extract meaningful parts from regency name
        $regencyParts = explode(' ', $regencyName);
        $regencyCode = '';

        // Build code from first letters of each word, max 3 characters
        foreach ($regencyParts as $part) {
            if (strlen($regencyCode) < 3 && !empty($part)) {
                $regencyCode .= strtoupper(substr($part, 0, 1));
            }
        }

        // If too short, add more characters from first word
        if (strlen($regencyCode) < 3 && !empty($regencyParts[0])) {
            $regencyCode = strtoupper(substr($regencyParts[0], 0, 3));
        }

        $baseCode = $provinceCode . '-' . $regencyCode;
        return $this->ensureUniqueCode($baseCode);
    }

    private function generateUniqueDistrictCode(string $districtName, string $regencyName): string
    {
        // Generate a unique code based on regency and district
        $regencyCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $regencyName), 0, 3));
        $districtCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $districtName), 0, 3));

        $baseCode = $regencyCode . '-' . $districtCode;
        return $this->ensureUniqueCode($baseCode, true);
    }

    private function ensureUniqueCode(string $baseCode, bool $addRandom = false): string
    {
        $code = $baseCode;
        $counter = 1;

        while (in_array($code, $this->usedCodes)) {
            if ($addRandom) {
                $code = $baseCode . '-' . str_pad($counter, 3, '0', STR_PAD_LEFT);
            } else {
                $code = $baseCode . $counter;
            }
            $counter++;
        }

        $this->usedCodes[] = $code;
        return $code;
    }

    private function normalizeForFilename(string $name): string
    {
        return str_replace([' ', '/', '-', '(', ')', '.', ',', "'"], ['_', '_', '_', '', '', '', '', ''], $name);
    }

    /**
     * Enhanced logging for coordinate extraction success/failure
     */
    private function logGeoJsonAttempt(string $type, string $name, bool $found): void
    {
        $status = $found ? '✅ FOUND' : '❌ NOT FOUND';
        $this->command->info("GeoJSON {$type} '{$name}': {$status}");
    }

    /**
     * Show summary statistics at the end
     */
    private function showSeederSummary(array $provinces, array $regencies, array $districtsData): void
    {
        $this->command->info('');
        $this->command->info('=== SEEDER SUMMARY ===');
        $this->command->info('✅ Created:');
        $this->command->info("   - " . count($provinces) . " Provinces");
        $this->command->info("   - " . count($regencies) . " Regencies");
        $this->command->info("   - " . count($districtsData) . " Districts");

        // Count how many actually have GeoJSON files
        $provincesWithGeoJson = 0;
        $regenciesWithGeoJson = 0;

        foreach ($provinces as $province) {
            if ($province->geojson_file_path) $provincesWithGeoJson++;
        }

        foreach ($regencies as $regency) {
            if ($regency->geojson_file_path) $regenciesWithGeoJson++;
        }

        $districtsWithGeoJson = District::whereNotNull('geojson_file_path')
            ->where('administrative_level', 'district')
            ->count();

        $this->command->info('📁 With GeoJSON files:');
        $this->command->info("   - {$provincesWithGeoJson}/" . count($provinces) . " Provinces");
        $this->command->info("   - {$regenciesWithGeoJson}/" . count($regencies) . " Regencies");
        $this->command->info("   - {$districtsWithGeoJson}/" . count($districtsData) . " Districts");
        $this->command->info('');
    }

    private function getDistrictSecurityLevel(string $districtName, string $regencyName): string
    {
        // Define security levels based on known conditions
        $highRiskRegencies = ['Mimika', 'Pegunungan Bintang', 'Nduga', 'Yahukimo'];
        $highRiskDistricts = ['Tembagapura', 'Oksibil', 'Mapenduma', 'Dekai'];

        if (in_array($regencyName, $highRiskRegencies) || in_array($districtName, $highRiskDistricts)) {
            return 'high';
        }

        if (str_contains(strtolower($districtName), 'timur') || str_contains(strtolower($districtName), 'utara')) {
            return 'medium';
        }

        return 'low';
    }

    private function getProvincePopulation(string $provinceName): int
    {
        $populations = [
            'Papua' => 1060550,
            'Papua Barat' => 1134068,
            'Papua Barat Daya' => 365054,
            'Papua Pegunungan' => 1448360,
            'Papua Selatan' => 522840,
            'Papua Tengah' => 1452514,
        ];

        return $populations[$provinceName] ?? 500000;
    }

    private function getProvinceArea(string $provinceName): float
    {
        $areas = [
            'Papua' => 9950000.0,
            'Papua Barat' => 13264487.0,
            'Papua Barat Daya' => 3602386.0,
            'Papua Pegunungan' => 4989381.0,
            'Papua Selatan' => 12909574.0,
            'Papua Tengah' => 6127844.0,
        ];

        return $areas[$provinceName] ?? 5000000.0;
    }

    private function getRegencyPopulation(string $regencyName): int
    {
        $largeRegencies = ['Merauke', 'Mimika', 'Jayawijaya', 'Biak Numfor'];

        if (in_array($regencyName, $largeRegencies)) {
            return rand(150000, 300000);
        }

        return rand(50000, 150000);
    }

    private function getRegencyArea(string $regencyName): float
    {
        $largeRegencies = ['Merauke', 'Mamberamo Raya', 'Boven Digoel'];

        if (in_array($regencyName, $largeRegencies)) {
            return rand(2000000, 4000000);
        }

        return rand(100000, 1000000);
    }

    /**
     * List available GeoJSON files for debugging
     */
    private function listAvailableGeoJsonFiles(): void
    {
        $this->command->info('=== Available GeoJSON Files ===');

        // List province files
        $provinceFiles = File::glob(resource_path('maps/papua_provinces/*.geojson'));
        $this->command->info('Province files (' . count($provinceFiles) . '):');
        foreach ($provinceFiles as $file) {
            $this->command->info('  - ' . basename($file));
        }

        // List regency files
        $regencyFiles = File::glob(resource_path('maps/papua_regencies/*.geojson'));
        $this->command->info('Regency files (' . count($regencyFiles) . '):');
        foreach (array_slice($regencyFiles, 0, 10) as $file) {
            $this->command->info('  - ' . basename($file));
        }
        if (count($regencyFiles) > 10) {
            $this->command->info('  ... and ' . (count($regencyFiles) - 10) . ' more');
        }

        // List district files
        $districtFiles = File::glob(resource_path('maps/papua_districts_detailed/*.geojson'));
        $this->command->info('District files (' . count($districtFiles) . '):');
        foreach (array_slice($districtFiles, 0, 10) as $file) {
            $this->command->info('  - ' . basename($file));
        }
        if (count($districtFiles) > 10) {
            $this->command->info('  ... and ' . (count($districtFiles) - 10) . ' more');
        }
    }
}
