<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class District extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'code',
        'polygon_coordinates',
        'security_level',
        'population',
        'area_hectares',
        'administrative_level',
        'parent_district_id',
        'is_active',
        'parent_district_id_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'polygon_coordinates' => 'array',
            'area_hectares' => 'decimal',
            'parent_district_id' => 'integer',
            'is_active' => 'boolean',
            'parent_district_id_id' => 'integer',
        ];
    }

    public function parentDistrict(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function parentDistricts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function productionDatas(): HasMany
    {
        return $this->hasMany(ProductionData::class);
    }

    public function commodityPrices(): HasMany
    {
        return $this->hasMany(CommodityPrice::class);
    }

    public function securityLevelHistories(): HasMany
    {
        return $this->hasMany(SecurityLevelHistory::class);
    }
}
