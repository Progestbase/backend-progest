<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Unidades;

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
     * Relacionamento com Unidades (many-to-many)
     * Um usuário pode pertencer a várias unidades
     */
    public function unidades()
    {
        // O model `Unidades` está no plural, então o Laravel pode inferir a chave pivot errada
        // Definimos explicitamente as chaves pivot: 'user_id' (para este modelo) e 'unidade_id' (para Unidades)
        return $this->belongsToMany(Unidades::class, 'unidade_user', 'user_id', 'unidade_id');
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
