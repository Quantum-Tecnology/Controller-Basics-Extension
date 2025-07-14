<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Controller;

use Illuminate\Database\Eloquent\Model;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Model\Comment;
use QuantumTecnology\ControllerBasicsExtension\Traits\AsGraphQLController;

final class PostCommentController
{
    use AsGraphQLController;

    protected function model(): Model
    {
        return new Comment();
    }
}
