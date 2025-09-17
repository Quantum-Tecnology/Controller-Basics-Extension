<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\Enum;

enum FilterOperation
{
    case Equal;
    case Like;
    case LessOrEqual;
    case GreaterOrEqual;
    case In;
    case NotIn;
    case Between;
    case NotBetween;
    case Null;
    case NotNull;
    case Exists;
    case NotExists;
}
