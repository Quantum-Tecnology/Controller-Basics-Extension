<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller;

use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;
use QuantumTecnology\ControllerBasicsExtension\Traits\AsGraphQLController;

final class PostController
{
    use AsGraphQLController;

    protected function model(): Model
    {
        return new Post();
    }
}
