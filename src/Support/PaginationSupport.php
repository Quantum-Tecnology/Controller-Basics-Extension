<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class PaginationSupport
{
    public function parse(array $data): array
    {
        $pagination = [];

        foreach ($data as $key => $value) {
            if (preg_match('/^(per_page|page)_(.+)$/', $key, $matches)) {
                [$type, $rawPath] = [$matches[1], $matches[2]];
                $pathParts        = explode('.', $rawPath);

                $ref = &$pagination;

                foreach ($pathParts as $part) {
                    if (!isset($ref[$part])) {
                        $ref[$part] = [];
                    }
                    $ref = &$ref[$part];
                }
                $ref[$type] = (int) $value;
                unset($ref);
            }
        }

        return $pagination;
    }
}
