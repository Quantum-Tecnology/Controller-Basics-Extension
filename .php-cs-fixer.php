<?php

/**
 * https://github.com/FriendsOfPHP/PHP-CS-Fixer/blob/master/doc/ruleSets/index.rst.
 */

$finder = PhpCsFixer\Finder::create()
    ->in(__DIR__);

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        'single_space_around_construct' => [],

        'no_extra_blank_lines' => [
            'tokens' => [],
        ],
        '@PSR12'                                 => true,
        '@PSR12:risky'                           => true,
        '@PSR1'                                  => true,
        'phpdoc_to_comment'                      => false,
        'declare_strict_types'                   => false,
        'array_indentation'                      => true,
        'explicit_string_variable'               => true,
        'multiline_whitespace_before_semicolons' => false,
        '@Symfony'                               => true,
        'binary_operator_spaces'                 => [
            'operators' => [
                '=>' => 'align_single_space_minimal',
                '+=' => 'align_single_space_minimal',
                '-=' => 'align_single_space_minimal',
                '='  => 'align_single_space_minimal',
            ],
        ],
    ])
    ->setLineEnding("\n")
    ->setFinder($finder);
