<?php

namespace App\Filament\Resources\DistrictResource\Pages;

use App\Filament\Resources\DistrictResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;

class ViewDistrict extends ViewRecord
{
    protected static string $resource = DistrictResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil'),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('Ikhtisar Wilayah')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Nama Wilayah')
                                    ->weight('bold')
                                    ->size('lg'),

                                TextEntry::make('code')
                                    ->label('Kode Wilayah')
                                    ->fontFamily('mono')
                                    ->copyable(),

                                TextEntry::make('administrative_level')
                                    ->label('Tingkat Administratif')
                                    ->badge()
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'province' => 'Provinsi',
                                        'regency' => 'Kabupaten',
                                        'district' => 'Distrik',
                                        'village' => 'Desa',
                                        default => ucfirst($state)
                                    }),

                                TextEntry::make('security_level')
                                    ->label('Tingkat Keamanan')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'low' => 'success',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        'critical' => 'gray',
                                    })
                                    ->formatStateUsing(fn (string $state): string => match($state) {
                                        'low' => 'Risiko Rendah',
                                        'medium' => 'Risiko Sedang',
                                        'high' => 'Risiko Tinggi',
                                        'critical' => 'Risiko Kritis',
                                        default => ucfirst($state)
                                    }),
                            ]),
                    ]),

                Section::make('Informasi Geografis')
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                TextEntry::make('province')
                                    ->label('Provinsi'),

                                TextEntry::make('regency.name')
                                    ->label('Kabupaten'),

                                TextEntry::make('parentDistrict.name')
                                    ->label('Wilayah Induk'),

                                TextEntry::make('population')
                                    ->label('Populasi')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state) . ' jiwa' : 'Tidak ditentukan'),

                                TextEntry::make('area_hectares')
                                    ->label('Luas')
                                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 2) . ' hektar' : 'Tidak ditentukan'),

                                TextEntry::make('geojson_file_path')
                                    ->label('File GeoJSON')
                                    ->formatStateUsing(fn ($state) => $state ? 'Tersedia' : 'Tidak tersedia')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'gray'),
                            ]),
                    ]),

                Section::make('Peta Wilayah')
                    ->schema([
                        ViewEntry::make('district_map')
                            ->label('')
                            ->view('filament.infolists.components.district-map')
                            ->viewData(fn ($record) => [
                                'record' => $record,
                                'coordinates' => $record->map_coordinates,
                                'geojson_path' => $record->geojson_file_path,
                                'has_custom_coordinates' => !empty($record->custom_coordinates),
                            ]),
                    ]),
            ]);
    }
}
