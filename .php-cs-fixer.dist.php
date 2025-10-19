<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$rules = [
    '@PhpCsFixer' => true,
    '@PhpCsFixer:risky' => true,
    '@Symfony' => true,
    '@Symfony:risky' => true,
    '@PHP8x4Migration' => true,
    '@PHPUnit10x0Migration:risky' => true,

    // Типизация
    'declare_strict_types' => true,
    'strict_comparison' => true,
    'strict_param' => true,

    // Современный PHP
    'array_syntax' => ['syntax' => 'short'],
    'list_syntax' => ['syntax' => 'short'],
    'modernize_types_casting' => true,
    'no_unneeded_final_method' => true,
    'native_function_invocation' => [
        'include' => ['@compiler_optimized'],
        'scope' => 'namespaced',
        'strict' => true,
    ],

    // PHPDoc
    'phpdoc_align' => ['align' => 'vertical'],
    'phpdoc_order' => true,
    'phpdoc_separation' => true,
    'phpdoc_trim_consecutive_blank_line_separation' => true,
    'no_empty_phpdoc' => true,
    'no_superfluous_phpdoc_tags' => ['allow_mixed' => true],

    // Форматирование
    'concat_space' => ['spacing' => 'one'],
    'single_line_empty_body' => true,
    'yoda_style' => ['equal' => false, 'identical' => false],
    'blank_line_before_statement' => ['statements' => ['return', 'throw', 'if', 'for', 'foreach', 'while', 'do', 'switch']],
    'method_argument_space' => ['on_multiline' => 'ensure_fully_multiline'],
    'trailing_comma_in_multiline' => ['elements' => ['arrays']],
    'ordered_imports' => ['imports_order' => ['class', 'function', 'const'], 'sort_algorithm' => 'alpha'],
    'no_unused_imports' => true,
];

return new Config()
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    ->setCacheFile(__DIR__ . '/var/cache/.php-cs-fixer.cache')
    ->setRiskyAllowed(true)
    ->setRules($rules)
    ->setFinder(
        new Finder()
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
            ->append([__FILE__])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->exclude(['var', 'vendor', 'node_modules'])
            ->name('*.php')
            ->notName('*.twig')
            ->notName('*.js')
            ->notName('*.css')
    )
;
