<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'photo',
        'password',
        'actif',
        'bureau_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'actif' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getPhotoUrlAttribute(): ?string
    {
        return $this->photo ? asset('storage/'.$this->photo) : null;
    }

    public function bureau()
    {
        return $this->belongsTo(Bureau::class);
    }

    /**
     * Raccourci pratique pour les vues/formulaires qui n'ont pas encore de
     * modèle métier (achat/vente) à interroger directement — ex. avant la
     * création d'une opération. Un utilisateur sans bureau (superadmin) se
     * rabat sur la devise par défaut de Bureau::devise.
     */
    public function getDeviseSymboleAttribute(): string
    {
        return $this->bureau?->devise_symbole ?? config('pays_devises.Mali.symbole');
    }
}
