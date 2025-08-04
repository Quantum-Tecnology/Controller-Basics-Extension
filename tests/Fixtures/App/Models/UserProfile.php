<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\UserProfileFactory;

final class UserProfile extends BaseModel
{
    protected $table = 'user_profile';

    protected static function newFactory(): UserProfileFactory
    {
        return UserProfileFactory::new();
    }
}
