<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;

final class PostLikeFactory extends Factory
{
    protected $model = self::class;

    public function definition(): array
    {
        return [
            'post_id'    => Post::factory(),
            'like'       => $this->faker->numberBetween(0, 5),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];
    }
}
