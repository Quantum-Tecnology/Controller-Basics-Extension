<?php

declare(strict_types = 1);

namespace QuantumTecnology\ControllerBasicsExtension\Libs\Cryptography;

use Illuminate\Support\Str;

/**
 * O que é um id para o Hashids: a regra única do `Decoder` (entrada) e do
 * `Encoder` (saída).
 *
 * Antes cada um tinha a sua cópia de `isIdentifier`, e elas já tinham
 * divergido (só a da entrada olhava os cabeçalhos). Com a regra num lugar só,
 * mudar o que conta como id muda os dois lados juntos.
 */
final class Identifier
{
    /**
     * Sufixos que sempre são id, além do `hashids.regex` configurado.
     *
     * ⚠️ Sem tipo na constante: o pacote suporta PHP 8.2, e constante tipada
     * só existe a partir do 8.3.
     */
    private const DEFAULT_REGEX = '/_id$|Id$/';

    /**
     * O campo é um id pelo NOME (`id`, `user_id`, `plan_ids`...)?
     *
     * Nunca olha o valor: para isso existe `isIgnoredUuid`.
     */
    public static function isIdentifier(string | int $key): bool
    {
        $key = (string) $key;

        return (bool) preg_match((string) config('hashids.regex', ''), $key)
            || (bool) preg_match(self::DEFAULT_REGEX, $key)
            || in_array($key, config('hashids.attributes', []), true);
    }

    /** O cabeçalho carrega id (`X-Admin`, `X-Agent`, `X-User`)? Só a entrada lê cabeçalho. */
    public static function isIdentifierHeader(string | int $key): bool
    {
        return (bool) preg_match((string) config('hashids.headers.regex', ''), (string) $key);
    }

    /**
     * Valor que passa pelo Hashids sem ser tocado: UUID, com `hashids.ignore_uuid`.
     *
     * Por VALOR, não por nome: uma tabela de chave UUID recebe e devolve o id
     * em `id`/`*_id` como qualquer outra. Na saída o UUID já não seria
     * codificado (o Hashids só codifica inteiro), mas a regra é a mesma dos
     * dois lados para não depender desse detalhe.
     */
    public static function isIgnoredUuid(mixed $value): bool
    {
        return is_string($value)
            && config('hashids.ignore_uuid', false)
            && Str::isUuid($value);
    }
}
