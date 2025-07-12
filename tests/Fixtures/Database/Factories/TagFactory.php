<?php

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Tag;

class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->sentence(3),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
