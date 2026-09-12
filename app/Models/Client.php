<?php

namespace App\Models;

use App\Concerns\BelongsToBureau;
use App\Concerns\HasNumeroSequentiel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory, HasNumeroSequentiel, BelongsToBureau;

    protected $fillable = [
        'identifiant',
        'nom',
        'prenom',
        'telephone',
        'adresse',
        'actif',
        'user_id',
        'bureau_id',
    ];

    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Client $client) {
            if (empty($client->identifiant)) {
                $client->identifiant = static::genererNumeroSequentiel('CL', 'identifiant');
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getNomCompletAttribute(): string
    {
        return trim($this->prenom.' '.$this->nom);
    }
}
