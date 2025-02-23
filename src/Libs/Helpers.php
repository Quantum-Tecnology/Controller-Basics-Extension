<?php

/*
 * Helpers.
 */

if (!function_exists('hideEmail')) {
    /**
     * hideEmail function.
     */
    function hideEmail(string $email): string
    {
        return preg_replace("/^([\w_]{2})(.+)([\w_]{2}@)(.+)([\w_]{2}.com)/u", '$1********$3********$5', $email);
    }
}

if (!function_exists('cleanExceptNumber')) {
    /**
     * Limpa tudo da variavel com exceção numero.
     *
     * @param string|null $v1 variavel a ser limpada
     *
     * @return void
     */
    function cleanExceptNumber(?string $v1 = null)
    {
        return preg_replace('/[^0-9]/i', '', $v1);
    }
}

if (!function_exists('formatCnpjCpf')) {
    /**
     * Formatar para CPF||CNPJ.
     *
     * @param $cnpjCpf cnpj ou cpf a ser formatado
     *
     * @return void
     */
    function formatCnpjCpf($cnpjCpf)
    {
        $cnpjCpf = preg_replace("/\D/", '', $cnpjCpf);

        if ('' == $cnpjCpf) {
            return $cnpjCpf = 'Não informado';
        }

        if (11 === strlen($cnpjCpf)) {
            return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", '$1.$2.$3-$4', $cnpjCpf);
        }

        return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", '$1.$2.$3/$4-$5', $cnpjCpf);
    }
}

if (!function_exists('isSequentialValues')) {
    /**
     * Checa se o Array possui os valores sequenciais.
     *
     * @param array $arr array a ser checado
     */
    function isSequentialValues(array $arr = []): bool
    {
        sort($arr);

        $indice = 0;
        for ($i = min($arr); $i <= max($arr); ++$i) {
            if ($i != $arr[$indice]) {
                return false;
            }
            ++$indice;
        }

        return true;
    }
}

if (!function_exists('unaccent')) {
    /**
     * Formatar para CPF||CNPJ.
     *
     * @param $str string a ser formatada
     *
     * @return string
     */
    function unaccent($str)
    {
        return preg_replace(['/(á|à|ã|â|ä)/', '/(Á|À|Ã|Â|Ä)/', '/(é|è|ê|ë)/', '/(É|È|Ê|Ë)/', '/(í|ì|î|ï)/', '/(Í|Ì|Î|Ï)/', '/(ó|ò|õ|ô|ö)/', '/(Ó|Ò|Õ|Ô|Ö)/', '/(ú|ù|û|ü)/', '/(Ú|Ù|Û|Ü)/', '/(ñ)/', '/(Ñ)/', '/(-)/'], explode(' ', 'a A e E i I o O u U n N '), $str);
    }
}

if (!function_exists('removeSpecialChar')) {
    /**
     * @param $str string a ser formatada
     */
    function removeSpecialChar(string $str): string
    {
        return preg_replace('/[@\.\;\-\" "]+/', '', $str);
    }
}

if (!function_exists('toObject')) {
    /**
     * toStd function.
     *
     * @param $arr array a ser transformado em objeto
     *
     * @return object
     */
    function toObject($arr)
    {
        return json_decode(json_encode($arr));
    }
}

if (!function_exists('database')) {
    function database(
        bool $active = true,
        bool $refresh = false,
    ): Illuminate\Database\Eloquent\Model|false {
        return auth()->database(
            active: $active,
            refresh: $refresh,
        );
    }
}

if (!function_exists('getCardBrand')) {
    /**
     * toStd function.
     *
     * @return object
     */
    function getCardBrand(string $cardNumber): string
    {
        $cardNumber = preg_replace('/\D/', '', $cardNumber);

        $visa       = '/^4[0-9]{12}(?:[0-9]{3})?$/';
        $mastercard = '/^5[1-5][0-9]{14}$/';
        $amex       = '/^3[47][0-9]{13}$/';
        $discover   = '/^6(?:011|5[0-9]{2})[0-9]{12}$/';

        if (preg_match($visa, $cardNumber)) {
            return 'visa';
        } elseif (preg_match($mastercard, $cardNumber)) {
            return 'mastercard';
        } elseif (preg_match($amex, $cardNumber)) {
            return 'amex';
        } elseif (preg_match($discover, $cardNumber)) {
            return 'discover';
        } else {
            return 'unknown';
        }
    }
}
