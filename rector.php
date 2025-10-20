<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/public',
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withSkip([
        __DIR__ . '/var',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
    ])
    ->withAutoloadPaths([
        __DIR__ . '/vendor/autoload.php',
    ])
    ->withPhpSets(php84: true)
    ->withPreparedSets(
        codingStyle: true,
        privatization: true,
        naming: true,
        instanceOf: true,
        rectorPreset: true,
        doctrineCodeQuality: true,
        symfonyCodeQuality: true,
        symfonyConfigs: true,
    )
    ->withComposerBased(twig: true, doctrine: true, phpunit: true, symfony: true)
    ->withTypeCoverageLevel(10)
    ->withDeadCodeLevel(10)
    ->withCodeQualityLevel(10)
    ->withParallel(
        timeoutSeconds: (int) ($_ENV['RECTOR_PARALLEL_TIMEOUT'] ?? 120),
        maxNumberOfProcess: (int) ($_ENV['RECTOR_PARALLEL_MAX_NODES'] ?? 8),
        jobSize: (int) ($_ENV['RECTOR_PARALLEL_JOB_SIZE'] ?? 8),
    )
    ->withCache(__DIR__ . '/var/rector_cache')
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
;
