<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;


class CreateDefaultAdminUser extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::table('users')->insert([
            'name' => 'ADMIN',
            'email' => 'admin@admin.com',
            'password' => bcrypt('admin'),
            'telefone' => '00000000000',
            'data_nascimento' => '1990-01-01',
            'cpf' => '00000000000',
            'status' => 'A',
            'tipo_vinculo' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('users')->where('email', 'admin@example.com')->delete();
    }
}
