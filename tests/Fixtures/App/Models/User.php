<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\App\Models;

use Illuminate\Database\Eloquent\Relations\HasOne;
use QuantumTecnology\ControllerBasicsExtension\Tests\Fixtures\Database\Factories\UserFactory;

final class User extends BaseModel
{
    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class);
    }

    protected static function newFactory(): UserFactory
    {
        return UserFactory::new();
    }
}
