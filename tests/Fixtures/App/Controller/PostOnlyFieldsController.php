<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller;

use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models\Post;
use QuantumTecnology\ControllerBasicsExtension\Traits\AsGraphQLController;

final class PostOnlyFieldsController
{
    use AsGraphQLController;

    protected function allowedFields(): array
    {
        return [
            'id',
            'title',
            'comments' => [
                'id',
                'likes' => [
                    'id',
                ],
            ],
        ];
    }

    protected function model(): Model
    {
        return new Post();
    }
}
