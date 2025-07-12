<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Author;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

final class PostFactory extends Factory
{
    protected $model = Post::class;

    public function definition(): array
    {
        return [
            'author_id'  => Author::factory(),
            'title'      => $this->faker->sentence(3),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
