<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Builder\QueryBuilder\Support;

final class FieldParser
{
    /**
     * Normalize a GraphQL-like field string into nested array format used by QueryBuilder.
     * Example: "id,title,author{id,name},comments{id,likes}".
     */
    public static function normalize(string $fields): array
    {
        // Tokenize into identifiers and braces, ignore other characters
        preg_match_all('/[A-Za-z0-9_]+|\{|\}/u', $fields, $matches);
        $tokens = $matches[0] ?? [];

        $root = [];
        $path = [];

        // Helper to get a reference to the current context by path
        $ctx = function &() use (&$root, &$path): array {
            $ref = &$root;

            foreach ($path as $seg) {
                $ref = &$ref[$seg];
            }

            return $ref;
        };

        $current         = &$ctx();
        $canUnwindToRoot = false;

        $i = 0;
        $n = count($tokens);

        $pushCurrent = function (array &$current, array $path, $name): void {
            // Push as scalar identifier. Relations are only created when followed by '{'.
            $current[] = $name;
        };

        while ($i < $n) {
            $tok = $tokens[$i];

            if ('{' === $tok) {
                // Begin a new nested block on the last added relation
                $lastKey = null;

                foreach (array_reverse(array_keys($current)) as $k) {
                    if (!is_int($k) && is_array($current[$k])) {
                        $lastKey = $k;

                        break;
                    }
                }

                if (null === $lastKey) {
                    ++$i;

                    continue;
                }

                // Dive into the relation
                $path[]          = $lastKey;
                $current         = &$ctx();
                $canUnwindToRoot = false;
                ++$i;

                continue;
            }

            if ('}' === $tok) {
                // Pop one level if possible
                if ([] !== $path) {
                    array_pop($path);
                    $current = &$ctx();
                    // If we're now exactly one level deep, allow unwinding to root on next identifier
                    $canUnwindToRoot = 1 === count($path);
                }
                ++$i;

                continue;
            }

            // Identifier
            $name = $tok;
            $next = $tokens[$i + 1] ?? null;

            // If we've just closed a child under a parent, treat the next item as root-level
            if ([] !== $path && $canUnwindToRoot) {
                $path            = [];
                $current         = &$ctx();
                $canUnwindToRoot = false;
            }

            if ('{' === $next) {
                // Prepare a relation key for the upcoming block
                $current[$name] = [];
                ++$i;

                continue;
            }

            // If we're at depth >= 2 and the next token closes the block, treat as a relation key
            if ('}' === $next && count($path) >= 2) {
                $current[$name] = [];
                ++$i;

                continue;
            }

            // Otherwise, push as scalar
            $pushCurrent($current, $path, $name);
            $canUnwindToRoot = false;
            ++$i;
        }

        return $root;
    }
}
