<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductionData extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'district_id',
        'commodity_id',
        'production_volume',
        'harvest_area',
        'yield_per_hectare',
        'month',
        'year',
        'data_source',
        'verified_at',
        'verified_by',
        'verified_by_id',
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
            'district_id' => 'integer',
            'commodity_id' => 'integer',
            'production_volume' => 'decimal',
            'harvest_area' => 'decimal',
            'yield_per_hectare' => 'decimal',
            'verified_at' => 'datetime',
            'verified_by' => 'integer',
            'verified_by_id' => 'integer',
        ];
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

}
