<?php

declare(strict_types=1);

use PhpCsFixer\Config;
use PhpCsFixer\Finder;
use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

return new Config()
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setUsingCache(true)
    ->setRiskyAllowed(true)
    // ->setUnsupportedPhpVersionAllowed(true)
    ->setCacheFile(__DIR__.'/var/cache/.php-cs-fixer.cache')
    ->setFormat('@auto')
    ->setRules([
        '@DoctrineAnnotation' => true,
        '@PHP8x3Migration' => true,
        '@PHP8x2Migration:risky' => true,
        '@PHPUnit10x0Migration:risky' => true,

        '@Symfony' => true,
        '@Symfony:risky' => true,

        'declare_strict_types' => true,
        'single_line_empty_body' => true,
        'single_line_throw' => false,
        'phpdoc_no_useless_inheritdoc' => false,
        'no_superfluous_phpdoc_tags' => false,
    ])
    ->setFinder(
        new Finder()
            ->in(__DIR__)
            ->append([__FILE__])
            ->ignoreDotFiles(true)
            ->ignoreVCS(true)
            ->exclude([
                'tests/_output',
                'var',
                'vendor',
            ])
            ->name('*.php')
    )
;
