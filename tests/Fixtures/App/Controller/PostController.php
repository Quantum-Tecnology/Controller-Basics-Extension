<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller;

use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Post;
use QuantumTecnology\ControllerBasicsExtension\Traits\AsApiController;

final class PostController
{
    use AsApiController;

    protected function model(): Model
    {
        return new Post();
    }
}
