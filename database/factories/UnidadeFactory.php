<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Unidade;

class UnidadeFactory extends Factory
{
    protected $model = Unidade::class;

    public function definition()
    {
        return [
            'nome' => $this->faker->company . ' Unidade',
            'status' => 'A'
        ];
    }
}
