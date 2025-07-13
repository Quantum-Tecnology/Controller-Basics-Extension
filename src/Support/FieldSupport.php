<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Support;

class FieldSupport
{
    public function parse(string $input): array
    {
        $input = mb_trim($input);

        if ('' === $input || preg_match('/^\{\s*\}$/', $input)) {
            return [];
        }

        $tokens   = $this->tokenize($input);
        $position = 0;

        return $this->parseMultipleRoots($tokens, $position);
    }

    private function tokenize(string $input): array
    {
        preg_match_all('/\w+|[{]|[}]/', $input, $matches);

        return $matches[0];
    }

    private function parseMultipleRoots(array $tokens, int &$pos): array
    {
        $result = [];

        while ($pos < count($tokens)) {
            $token = $tokens[$pos];

            if ($this->isWord($token) && ($tokens[$pos + 1] ?? null) === '{') {
                $key = $token;
                $pos += 2; // pula o nome e o {
                $result[$key] = $this->parseGroup($tokens, $pos);
            } else {
                $result[] = $token;
                ++$pos;
            }
        }

        return $result;
    }

    private function parseGroup(array $tokens, int &$pos): array
    {
        $result = [];

        while ($pos < count($tokens)) {
            $token = $tokens[$pos];

            if ('}' === $token) {
                ++$pos;

                break;
            }

            $nextToken = $tokens[$pos + 1] ?? null;

            if ($this->isWord($token) && '{' === $nextToken) {
                $key = $token;
                $pos += 2;
                $result[$key] = $this->parseGroup($tokens, $pos);
            } else {
                $result[] = $token;
                ++$pos;
            }
        }

        return $result;
    }

    private function isWord(string $token): bool
    {
        return (bool) preg_match('/^\w+$/', $token);
    }
}
