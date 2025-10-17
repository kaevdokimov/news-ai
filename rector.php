<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/config',
        __DIR__.'/public',
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withPhpSets(php84: true)
    ->withParallel(
        timeoutSeconds: (int) ($_ENV['RECTOR_PARALLEL_TIMEOUT'] ?? 120),
        maxNumberOfProcess: (int) ($_ENV['RECTOR_PARALLEL_MAX_NODES'] ?? 8),
        jobSize: (int) ($_ENV['RECTOR_PARALLEL_JOB_SIZE'] ?? 8)
    )
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withTypeCoverageLevel(0)
    ->withDeadCodeLevel(0)
    ->withCodeQualityLevel(0);
