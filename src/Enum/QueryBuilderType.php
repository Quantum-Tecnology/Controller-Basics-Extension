<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Enum;

enum QueryBuilderType: int
{
    case Null    = 1;
    case NotNull = 2;
}
