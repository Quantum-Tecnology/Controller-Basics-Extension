<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class OrderSupport
{
    public function parse(array $data): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            if (preg_match('/^order_(column|direction)([._].+)?$/', $key, $matches)) {
                $type   = $matches[1];
                $suffix = $matches[2] ?? '';
                $suffix = mb_ltrim($suffix, '._');
                $parts  = $suffix ? preg_split('/[._]/', $suffix) : [];

                // Determine where to place the value
                if (empty($parts)) {
                    // No suffix: top-level order
                    if (!isset($result['order'])) {
                        $result['order'] = [];
                    }
                    $result['order'][$type] = $value;
                } elseif (1 === count($parts)) {
                    // Single part: e.g., items
                    if (!isset($result[$parts[0]]['order'])) {
                        $result[$parts[0]]['order'] = [];
                    }
                    $result[$parts[0]]['order'][$type] = $value;
                } elseif (2 === count($parts)) {
                    // Two parts: e.g., items.comment
                    if (!isset($result[$parts[0]]['children']['order'])) {
                        $result[$parts[0]]['children']['order'] = [];
                    }
                    $result[$parts[0]]['children']['order'][$type] = $value;
                }
            }
        }

        return $result;
    }
}
