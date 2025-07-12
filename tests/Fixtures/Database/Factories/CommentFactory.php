<?php

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;

class CommentFactory extends Factory
{
    protected $model = Comment::class;

    public function definition(): array
    {
        return [
            'body' => $this->faker->sentence(20),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
