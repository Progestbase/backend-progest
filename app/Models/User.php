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
        // Usar a tabela pivot canônica `usuario_setor` que contém o campo `perfil`
        // Note: a migration cria as colunas `usuario_id` e `setor_id` (não `user_id`)
        return $this->belongsToMany(Setores::class, 'usuario_setor', 'usuario_id', 'setor_id')
            ->withPivot('perfil')
            ->withTimestamps();
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

    /**
     * Usuário super-admin hardcoded (tem todas as permissões)
     * Retorna true se o email for admin@admin.com
     */
    public function isSuperAdmin(): bool
    {
        return isset($this->email) && mb_strtolower($this->email) === 'admin@admin.com';
    }
}
