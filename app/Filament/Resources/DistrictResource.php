<?php

namespace App\Filament\Resources;

use App\Filament\Forms\Components\LeafletMap;
use App\Filament\Resources\DistrictResource\Pages;
use App\Models\District;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Support\Htmlable;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\View;
use Filament\Support\Enums\Alignment;

class DistrictResource extends Resource
{
    protected static ?string $model = District::class;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    protected static ?string $navigationLabel = 'Wilayah';

    protected static ?string $modelLabel = 'Wilayah';

    protected static ?string $pluralModelLabel = 'Wilayah';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Informasi Wilayah')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Informasi Dasar')
                            ->icon('heroicon-m-information-circle')
                            ->schema([
                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label('Nama Wilayah')
                                            ->required()
                                            ->maxLength(255)
                                            ->placeholder('Masukkan nama wilayah')
                                            ->columnSpanFull(),

                                        Forms\Components\TextInput::make('code')
                                            ->label('Kode Wilayah')
                                            ->required()
                                            ->maxLength(255)
                                            ->unique(ignoreRecord: true)
                                            ->placeholder('mis. ID-PA-ABC')
                                            ->helperText('Kode identifikasi unik untuk wilayah'),

                                        Forms\Components\Select::make('administrative_level')
                                            ->label('Tingkat Administratif')
                                            ->required()
                                            ->options([
                                                'province' => 'Provinsi',
                                                'regency' => 'Kabupaten',
                                                'district' => 'Distrik',
                                                'village' => 'Desa',
                                            ])
                                            ->default('district')
                                            ->reactive(),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\Select::make('parent_district_id')
                                            ->label('Wilayah Induk')
                                            ->relationship('parentDistrict', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Pilih wilayah induk')
                                            ->helperText('Wilayah administratif induk dari wilayah ini'),

                                        Forms\Components\Select::make('regency_id')
                                            ->label('Kabupaten')
                                            ->relationship('regency', 'name')
                                            ->searchable()
                                            ->preload()
                                            ->placeholder('Pilih kabupaten')
                                            ->visible(fn (Forms\Get $get) => $get('administrative_level') === 'district'),
                                    ]),

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('province')
                                            ->label('Provinsi')
                                            ->maxLength(255)
                                            ->placeholder('Masukkan nama provinsi'),

                                        Forms\Components\Toggle::make('is_active')
                                            ->label('Status Aktif')
                                            ->default(true)
                                            ->helperText('Apakah wilayah ini sedang aktif'),
                                    ]),

                            ]),

                        Forms\Components\Tabs\Tab::make('Data Geografis')
                            ->icon('heroicon-m-map')
                            ->schema([
                                Forms\Components\Section::make('Peta & Batas Wilayah')
                                    ->description('Tentukan batas geografis wilayah ini')
                                    ->schema([
                                        LeafletMap::make('custom_coordinates')
                                            ->label('Batas Wilayah')
                                            ->height('500px')
                                            ->defaultLatLng(-2.5, 140.0) // Pusat Papua
                                            ->defaultZoom(8)
                                            ->helperText('Gambar batas poligon atau muat dari file GeoJSON')
                                            ->columnSpanFull()
                                            ->lazy(), // Force immediate loading

                                        Forms\Components\TextInput::make('geojson_file_path')
                                            ->label('Path File GeoJSON')
                                            ->placeholder('maps/papua_districts_detailed/namafile.geojson')
                                            ->helperText('Path ke file GeoJSON yang berisi batas wilayah')
                                            ->columnSpanFull(),
                                    ])
                                    ->collapsible(false), // Don't allow collapsing

                                Forms\Components\Grid::make(2)
                                    ->schema([
                                        Forms\Components\TextInput::make('area_hectares')
                                            ->label('Luas (Hektar)')
                                            ->numeric()
                                            ->placeholder('Masukkan luas dalam hektar')
                                            ->suffix('ha'),

                                        Forms\Components\TextInput::make('population')
                                            ->label('Populasi')
                                            ->numeric()
                                            ->placeholder('Masukkan jumlah penduduk'),
                                    ]),
                            ]),

                        Forms\Components\Tabs\Tab::make('Keamanan & Status')
                            ->icon('heroicon-m-shield-check')
                            ->schema([
                                Forms\Components\Select::make('security_level')
                                    ->label('Tingkat Keamanan')
                                    ->required()
                                    ->options([
                                        'low' => 'Risiko Rendah',
                                        'medium' => 'Risiko Sedang',
                                        'high' => 'Risiko Tinggi',
                                        'critical' => 'Risiko Kritis',
                                    ])
                                    ->default('medium')
                                    ->helperText('Penilaian keamanan saat ini untuk wilayah ini'),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                // Main card layout with enhanced sorting
                Split::make([
                    // Left section - Main info with icon
                    Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->label('Nama Wilayah')
                            ->searchable()
                            ->sortable()
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->icon('heroicon-m-map-pin')
                            ->tooltip('Klik untuk mengurutkan berdasarkan nama')
                            ->description(fn ($record) => 'Kode: ' . $record->code),

                        Tables\Columns\TextColumn::make('code')
                            ->label('Kode')
                            ->fontFamily('mono')
                            ->copyable()
                            ->size('sm')
                            ->color('gray')
                            ->prefix('ID: ')
                            ->sortable()
                            ->searchable()
                            ->toggleable()
                            ->toggledHiddenByDefault(true),
                    ])
                        ->space(1),

                    // Middle section - Location info (visible from md)
                    Stack::make([
                        Tables\Columns\TextColumn::make('province')
                            ->label('Provinsi')
                            ->icon('heroicon-m-globe-asia-australia')
                            ->placeholder('Tidak diset')
                            ->sortable()
                            ->searchable()
                            ->size('sm')
                            ->weight(FontWeight::Medium)
                            ->badge()
                            ->color('info'),

                        Tables\Columns\TextColumn::make('regency.name')
                            ->label('Kabupaten')
                            ->icon('heroicon-m-building-office-2')
                            ->placeholder('Tidak diset')
                            ->sortable()
                            ->searchable()
                            ->size('sm')
                            ->color('primary')
                            ->badge(),
                    ])
                        ->space(1)
                        ->visibleFrom('lg'),

                    // Right section - Badges and status
                    Stack::make([
                        Split::make([
                            Tables\Columns\BadgeColumn::make('administrative_level')
                                ->label('Tingkat')
                                ->colors([
                                    'primary' => 'province',
                                    'success' => 'regency',
                                    'warning' => 'district',
                                    'gray' => 'village',
                                ])
                                ->formatStateUsing(fn (string $state): string => match($state) {
                                    'province' => 'Provinsi',
                                    'regency' => 'Kabupaten',
                                    'district' => 'Distrik',
                                    'village' => 'Desa',
                                    default => ucfirst($state)
                                })
                                ->sortable()
                                ->grow(false),

                            Tables\Columns\BadgeColumn::make('security_level')
                                ->label('Keamanan')
                                ->colors([
                                    'success' => 'low',
                                    'warning' => 'medium',
                                    'danger' => 'high',
                                    'gray' => 'critical',
                                ])
                                ->formatStateUsing(fn (string $state): string => match($state) {
                                    'low' => '🟢 Aman',
                                    'medium' => '🟡 Sedang',
                                    'high' => '🔴 Tinggi',
                                    'critical' => '⚫ Kritis',
                                    default => ucfirst($state)
                                })
                                ->sortable()
                                ->grow(false),
                        ])->from('sm'),

                        Split::make([
                            Tables\Columns\IconColumn::make('is_active')
                                ->label('Status')
                                ->boolean()
                                ->trueIcon('heroicon-o-check-circle')
                                ->falseIcon('heroicon-o-x-circle')
                                ->trueColor('success')
                                ->falseColor('danger')
                                ->tooltip(fn ($record) => $record->is_active ? 'Wilayah Aktif' : 'Wilayah Tidak Aktif')
                                ->sortable()
                                ->grow(false),

                            Tables\Columns\TextColumn::make('created_at')
                                ->label('Dibuat')
                                ->dateTime('d/m/y')
                                ->sortable()
                                ->size('xs')
                                ->color('gray')
                                ->tooltip(fn ($record) => $record->created_at->format('d F Y H:i'))
                                ->visibleFrom('xl')
                                ->grow(false)
                                ->toggleable(),
                        ])->from('md'),
                    ])
                        ->space(1)
                        ->alignment(Alignment::End),
                ])->from('md'),

                // Collapsible detailed information panel
                Panel::make([
                    Grid::make([
                        'sm' => 1,
                        'md' => 2,
                        'lg' => 4,
                    ])
                        ->schema([
                            // Geographic Quick Info
                            Stack::make([
                                Tables\Columns\TextColumn::make('parentDistrict.name')
                                    ->label('Wilayah Induk')
                                    ->icon('heroicon-m-arrow-up-circle')
                                    ->placeholder('Tidak ada induk')
                                    ->color('info')
                                    ->size('sm')
                                    ->badge(),
                            ])
                                ->space(1),

                            // Demographics Stack with sorting
                            Stack::make([
                                Tables\Columns\TextColumn::make('population')
                                    ->label('Populasi')
                                    ->icon('heroicon-m-users')
                                    ->formatStateUsing(fn ($state) => $state ? '👥 ' . number_format($state) : '👥 -')
                                    ->sortable()
                                    ->size('sm')
                                    ->weight(FontWeight::Medium)
                                    ->tooltip('Klik untuk mengurutkan berdasarkan populasi'),

                                Tables\Columns\TextColumn::make('area_hectares')
                                    ->label('Luas')
                                    ->icon('heroicon-m-square-3-stack-3d')
                                    ->formatStateUsing(fn ($state) => $state ? '📐 ' . number_format($state, 1) . ' ha' : '📐 -')
                                    ->sortable()
                                    ->size('sm')
                                    ->weight(FontWeight::Medium)
                                    ->tooltip('Klik untuk mengurutkan berdasarkan luas wilayah'),
                            ])
                                ->space(1),

                            // Map Data Status
                            Stack::make([
                                Split::make([
                                    Tables\Columns\IconColumn::make('geojson_file_path')
                                        ->label('')
                                        ->boolean()
                                        ->trueIcon('heroicon-o-map')
                                        ->falseIcon('heroicon-o-map')
                                        ->trueColor('success')
                                        ->falseColor('gray')
                                        ->grow(false),

                                    Tables\Columns\TextColumn::make('geojson_file_path')
                                        ->label('Peta')
                                        ->formatStateUsing(fn ($state) => $state ? 'Tersedia' : 'Tidak ada')
                                        ->color(fn ($state) => $state ? 'success' : 'gray')
                                        ->size('sm')
                                        ->badge(),
                                ]),
                            ])
                                ->space(1),

                            // Quick Actions
                            Stack::make([
                                Tables\Columns\TextColumn::make('updated_at')
                                    ->label('Update Terakhir')
                                    ->since()
                                    ->icon('heroicon-m-clock')
                                    ->size('xs')
                                    ->color('gray')
                                    ->tooltip(fn ($record) => 'Diperbarui: ' . $record->updated_at->format('d F Y H:i'))
                                    ->sortable(),
                            ])
                                ->space(1)
                                ->visibleFrom('lg'),
                        ]),
                ])
                    ->collapsible()
                    ->collapsed(true),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 1,
                'lg' => 1,
                'xl' => 1,
            ])
            ->striped(false)
            ->defaultSort('name', 'asc')
            ->paginated([10, 25, 50, 100])
            ->deferLoading()
            ->persistSortInSession()
            ->persistSearchInSession()
            ->persistFiltersInSession()
            ->recordUrl(fn (Model $record): string => static::getUrl('view', ['record' => $record]))
            ->emptyStateIcon('heroicon-o-map')
            ->emptyStateHeading('Belum ada data wilayah')
            ->emptyStateDescription('Mulai dengan menambahkan wilayah baru ke sistem.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Tambah Wilayah')
                    ->icon('heroicon-m-plus'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('administrative_level')
                    ->label('Tingkat Administratif')
                    ->options([
                        'province' => 'Provinsi',
                        'regency' => 'Kabupaten',
                        'district' => 'Distrik',
                        'village' => 'Desa',
                    ]),

                Tables\Filters\SelectFilter::make('regency_id')
                    ->label('Kabupaten')
                    ->relationship('regency', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('security_level')
                    ->label('Tingkat Keamanan')
                    ->options([
                        'low' => 'Risiko Rendah',
                        'medium' => 'Risiko Sedang',
                        'high' => 'Risiko Tinggi',
                        'critical' => 'Risiko Kritis',
                    ]),

                Tables\Filters\SelectFilter::make('province')
                    ->label('Provinsi')
                    ->options(fn () => District::provinces()->pluck('name', 'province')->unique()),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status Aktif')
                    ->placeholder('Semua wilayah')
                    ->trueLabel('Hanya yang aktif')
                    ->falseLabel('Hanya yang tidak aktif'),

                Tables\Filters\TernaryFilter::make('has_geojson')
                    ->label('Memiliki Data Peta')
                    ->placeholder('Semua wilayah')
                    ->trueLabel('Dengan data peta')
                    ->falseLabel('Tanpa data peta')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('geojson_file_path'),
                        false: fn ($query) => $query->whereNull('geojson_file_path'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('Lihat')
                    ->icon('heroicon-o-eye'),
                Tables\Actions\EditAction::make()
                    ->label('Edit')
                    ->icon('heroicon-o-pencil'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus'),
                ]),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Ringkasan Wilayah')
                    ->description('Informasi utama wilayah ini')
                    ->icon('heroicon-m-information-circle')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('name')
                                    ->label('Nama Wilayah')
                                    ->weight(FontWeight::Bold)
                                    ->size('lg')
                                    ->icon('heroicon-m-map-pin'),

                                Infolists\Components\TextEntry::make('code')
                                    ->label('Kode Wilayah')
                                    ->fontFamily('mono')
                                    ->copyable()
                                    ->icon('heroicon-m-identification'),

                                Infolists\Components\TextEntry::make('administrative_level')
                                    ->label('Tingkat Administratif')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'province' => 'primary',
                                        'regency' => 'success',
                                        'district' => 'warning',
                                        'village' => 'gray',
                                        default => 'gray'
                                    })
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'province' => 'Provinsi',
                                        'regency' => 'Kabupaten',
                                        'district' => 'Distrik',
                                        'village' => 'Desa',
                                        default => ucfirst($state)
                                    }),

                                Infolists\Components\TextEntry::make('security_level')
                                    ->label('Tingkat Keamanan')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'low' => 'success',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        'critical' => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'low' => '🟢 Risiko Rendah',
                                        'medium' => '🟡 Risiko Sedang',
                                        'high' => '🔴 Risiko Tinggi',
                                        'critical' => '⚫ Risiko Kritis',
                                        default => ucfirst($state)
                                    }),
                            ]),
                    ])
                    ->collapsible(),

                Infolists\Components\Section::make('Informasi Geografis')
                    ->description('Data lokasi dan demografi wilayah')
                    ->icon('heroicon-m-globe-asia-australia')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('province')
                                    ->label('Province'),

                                Infolists\Components\TextEntry::make('regency.name')
                                    ->label('Regency'),

                                Infolists\Components\TextEntry::make('parentDistrict.name')
                                    ->label('Parent District'),

                                Infolists\Components\TextEntry::make('population')
                                    ->label('Population')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state) : 'Not specified'),

                                Infolists\Components\TextEntry::make('area_hectares')
                                    ->label('Area')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' hectares' : 'Not specified'),

                                Infolists\Components\TextEntry::make('geojson_file_path')
                                    ->label('GeoJSON File')
                                    ->formatStateUsing(fn ($state) => $state ? 'Available' : 'Not available')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDistricts::route('/'),
            'create' => Pages\CreateDistrict::route('/create'),
            'view' => Pages\ViewDistrict::route('/{record}'),
            'edit' => Pages\EditDistrict::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGlobalSearchResultTitle(Model $record): string|Htmlable
    {
        return $record->name;
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $details = [];

        if ($record->regency) {
            $details['Kabupaten'] = $record->regency->name;
        }

        if ($record->province) {
            $details['Provinsi'] = $record->province;
        }

        $details['Tingkat'] = match($record->administrative_level) {
            'province' => 'Provinsi',
            'regency' => 'Kabupaten',
            'district' => 'Distrik',
            'village' => 'Desa',
            default => ucfirst($record->administrative_level)
        };

        return $details;
    }

    public static function getGlobalSearchEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getGlobalSearchEloquentQuery()
            ->with(['regency', 'parentDistrict']);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'code', 'province', 'regency.name', 'parentDistrict.name'];
    }
}
