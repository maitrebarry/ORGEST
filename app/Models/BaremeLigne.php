<?php

namespace App\Models;

use App\Concerns\BelongsToBureauOrGlobal;
use Illuminate\Database\Eloquent\Model;

class BaremeLigne extends Model
{
    use BelongsToBureauOrGlobal;

    protected $fillable = [
        'bareme_version_id',
        'bureau_id',
        'densite_min',
        'densite_max',
        'carat',
        'ordre',
    ];

    protected function casts(): array
    {
        return [
            'densite_min' => 'decimal:2',
            'densite_max' => 'decimal:2',
            'carat' => 'decimal:2',
        ];
    }

    public function version()
    {
        return $this->belongsTo(BaremeVersion::class, 'bareme_version_id');
    }
}
