<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Setores;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'telefone',
        'matricula',
        'data_nascimento',
        'cpf',
        'status',
        'tipo_vinculo',
        'usuario_tipo',
        'password'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Relacionamento com Setores (many-to-many)
     * Um usuário pode pertencer a vários setores
     */
    public function setores()
    {
        // O model `Setores` está no plural, então o Laravel pode inferir a chave pivot errada
        // Definimos explicitamente as chaves pivot: 'user_id' (para este modelo) e 'setor_id' (para Setores)
        return $this->belongsToMany(Setores::class, 'setor_user', 'user_id', 'setor_id');
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function ($user) {
            if ($user->email === 'admin@admin.com') {
                throw new \Exception('O usuário Admin não pode ser excluído.');
            }
        });
    }
}
