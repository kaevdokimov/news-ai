<?php

namespace App\Tests\Config;

use PHPUnit\Framework\TestCase;

/**
 * Comprehensive tests for Composer configuration files.
 * 
 * These tests validate JSON syntax, required fields, dependency versions,
 * and critical configuration settings to ensure proper package management.
 */
class ComposerConfigTest extends TestCase
{
    private string $projectRoot;
    private array $composerJson;
    private array $composerLock;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
        
        $composerJsonPath = $this->projectRoot . '/composer.json';
        $composerLockPath = $this->projectRoot . '/composer.lock';
        
        $this->composerJson = json_decode(file_get_contents($composerJsonPath), true);
        $this->composerLock = json_decode(file_get_contents($composerLockPath), true);
    }

    /**
     * Test that composer.json exists and is readable.
     */
    public function testComposerJsonExists(): void
    {
        $path = $this->projectRoot . '/composer.json';
        $this->assertFileExists($path, 'composer.json must exist');
        $this->assertFileIsReadable($path, 'composer.json must be readable');
    }

    /**
     * Test that composer.lock exists and is readable.
     */
    public function testComposerLockExists(): void
    {
        $path = $this->projectRoot . '/composer.lock';
        $this->assertFileExists($path, 'composer.lock must exist');
        $this->assertFileIsReadable($path, 'composer.lock must be readable');
    }

    /**
     * Test that composer.json contains valid JSON.
     */
    public function testComposerJsonIsValidJson(): void
    {
        $path = $this->projectRoot . '/composer.json';
        $content = file_get_contents($path);
        
        json_decode($content);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 
            'composer.json must be valid JSON: ' . json_last_error_msg());
        
        $this->assertIsArray($this->composerJson, 'Parsed composer.json must be an array');
    }

    /**
     * Test that composer.lock contains valid JSON.
     */
    public function testComposerLockIsValidJson(): void
    {
        $path = $this->projectRoot . '/composer.lock';
        $content = file_get_contents($path);
        
        json_decode($content);
        $this->assertEquals(JSON_ERROR_NONE, json_last_error(), 
            'composer.lock must be valid JSON: ' . json_last_error_msg());
        
        $this->assertIsArray($this->composerLock, 'Parsed composer.lock must be an array');
    }

    /**
     * Test that composer.json has required top-level keys.
     */
    public function testComposerJsonHasRequiredKeys(): void
    {
        $requiredKeys = ['type', 'license', 'require', 'require-dev', 'autoload', 'config'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->composerJson, 
                sprintf('composer.json must have "%s" key', $key));
        }
    }

    /**
     * Test that composer.lock has required top-level keys.
     */
    public function testComposerLockHasRequiredKeys(): void
    {
        $requiredKeys = ['packages', 'packages-dev', 'content-hash'];
        
        foreach ($requiredKeys as $key) {
            $this->assertArrayHasKey($key, $this->composerLock, 
                sprintf('composer.lock must have "%s" key', $key));
        }
    }

    /**
     * Test that composer.json has correct project type.
     */
    public function testComposerJsonHasCorrectProjectType(): void
    {
        $this->assertArrayHasKey('type', $this->composerJson, 'composer.json must have type');
        $this->assertEquals('project', $this->composerJson['type'], 
            'Project type should be "project"');
    }

    /**
     * Test that composer.json has license defined.
     */
    public function testComposerJsonHasLicense(): void
    {
        $this->assertArrayHasKey('license', $this->composerJson, 'composer.json must have license');
        $this->assertNotEmpty($this->composerJson['license'], 'License must not be empty');
        $this->assertIsString($this->composerJson['license'], 'License must be a string');
    }

    /**
     * Test that PHP version requirement is set correctly.
     */
    public function testComposerJsonHasCorrectPhpVersion(): void
    {
        $this->assertArrayHasKey('require', $this->composerJson, 
            'composer.json must have require section');
        $this->assertArrayHasKey('php', $this->composerJson['require'], 
            'composer.json must specify PHP version requirement');
        
        $phpVersion = $this->composerJson['require']['php'];
        $this->assertEquals('>=8.3', $phpVersion, 
            'PHP version requirement should be >=8.3');
    }

    /**
     * Test that Doctrine DBAL version is correct.
     */
    public function testComposerJsonHasCorrectDbalVersion(): void
    {
        $this->assertArrayHasKey('require', $this->composerJson);
        $this->assertArrayHasKey('doctrine/dbal', $this->composerJson['require'], 
            'composer.json must have doctrine/dbal dependency');
        
        $dbalVersion = $this->composerJson['require']['doctrine/dbal'];
        $this->assertEquals('^3.10.3', $dbalVersion, 
            'Doctrine DBAL version should be ^3.10.3 (downgraded from 4.x)');
    }

    /**
     * Test that Doctrine DBAL in composer.lock matches composer.json requirement.
     */
    public function testComposerLockHasMatchingDbalVersion(): void
    {
        $dbalPackage = $this->findPackageInLock('doctrine/dbal');
        
        $this->assertNotNull($dbalPackage, 
            'doctrine/dbal must be present in composer.lock');
        
        $this->assertArrayHasKey('version', $dbalPackage, 
            'doctrine/dbal package must have version');
        
        // Verify it's version 3.x, not 4.x
        $version = $dbalPackage['version'];
        $this->assertStringStartsWith('3.', $version, 
            'Doctrine DBAL locked version should be 3.x');
        
        // Verify specific version matches expected
        $this->assertEquals('3.10.3', $version, 
            'Doctrine DBAL locked version should be exactly 3.10.3');
    }

    /**
     * Test that required Symfony packages are present.
     */
    public function testComposerJsonHasSymfonyPackages(): void
    {
        $requiredSymfonyPackages = [
            'symfony/framework-bundle',
            'symfony/yaml',
            'symfony/console',
            'symfony/dotenv',
            'symfony/flex'
        ];
        
        foreach ($requiredSymfonyPackages as $package) {
            $this->assertArrayHasKey($package, $this->composerJson['require'], 
                sprintf('composer.json must have %s dependency', $package));
        }
    }

    /**
     * Test that Symfony version constraint is consistent.
     */
    public function testSymfonyPackagesHaveConsistentVersions(): void
    {
        $symfonyPackages = array_filter(
            array_keys($this->composerJson['require']),
            fn($package) => str_starts_with($package, 'symfony/')
        );
        
        $this->assertNotEmpty($symfonyPackages, 
            'composer.json should have Symfony packages');
        
        // Most Symfony packages should use 7.3.* version constraint
        $symfonyPackagesWithVersion = array_filter($symfonyPackages, function($package) {
            return $package !== 'symfony/flex' && 
                   $package !== 'symfony/runtime' &&
                   $package !== 'symfony/stimulus-bundle' &&
                   $package !== 'symfony/ux-turbo';
        });
        
        foreach ($symfonyPackagesWithVersion as $package) {
            $version = $this->composerJson['require'][$package];
            $this->assertEquals('7.3.*', $version, 
                sprintf('%s should use version constraint 7.3.*', $package));
        }
    }

    /**
     * Test that PHPUnit is in require-dev with correct version.
     */
    public function testPhpUnitIsInRequireDev(): void
    {
        $this->assertArrayHasKey('require-dev', $this->composerJson, 
            'composer.json must have require-dev section');
        $this->assertArrayHasKey('phpunit/phpunit', $this->composerJson['require-dev'], 
            'PHPUnit must be in require-dev');
        
        $phpunitVersion = $this->composerJson['require-dev']['phpunit/phpunit'];
        $this->assertEquals('^12.4.1', $phpunitVersion, 
            'PHPUnit version should be ^12.4.1');
    }

    /**
     * Test that autoload PSR-4 configuration is correct.
     */
    public function testAutoloadPsr4ConfigurationIsCorrect(): void
    {
        $this->assertArrayHasKey('autoload', $this->composerJson);
        $this->assertArrayHasKey('psr-4', $this->composerJson['autoload'], 
            'composer.json must have PSR-4 autoload configuration');
        
        $psr4 = $this->composerJson['autoload']['psr-4'];
        $this->assertArrayHasKey('App\\', $psr4, 
            'PSR-4 autoload must include App namespace');
        $this->assertEquals('src/', $psr4['App\\'], 
            'App namespace should map to src/ directory');
    }

    /**
     * Test that autoload-dev PSR-4 configuration is correct.
     */
    public function testAutoloadDevPsr4ConfigurationIsCorrect(): void
    {
        $this->assertArrayHasKey('autoload-dev', $this->composerJson);
        $this->assertArrayHasKey('psr-4', $this->composerJson['autoload-dev'], 
            'composer.json must have PSR-4 autoload-dev configuration');
        
        $psr4 = $this->composerJson['autoload-dev']['psr-4'];
        $this->assertArrayHasKey('App\\Tests\\', $psr4, 
            'PSR-4 autoload-dev must include App\\Tests namespace');
        $this->assertEquals('tests/', $psr4['App\\Tests\\'], 
            'App\\Tests namespace should map to tests/ directory');
    }

    /**
     * Test that config section has required settings.
     */
    public function testConfigSectionHasRequiredSettings(): void
    {
        $this->assertArrayHasKey('config', $this->composerJson);
        
        $config = $this->composerJson['config'];
        $this->assertArrayHasKey('allow-plugins', $config, 
            'Config must have allow-plugins setting');
        $this->assertArrayHasKey('sort-packages', $config, 
            'Config should have sort-packages setting');
        
        $this->assertTrue($config['sort-packages'], 
            'sort-packages should be enabled');
    }

    /**
     * Test that minimum-stability is set correctly.
     */
    public function testMinimumStabilityIsStable(): void
    {
        $this->assertArrayHasKey('minimum-stability', $this->composerJson, 
            'composer.json must have minimum-stability setting');
        $this->assertEquals('stable', $this->composerJson['minimum-stability'], 
            'minimum-stability should be "stable"');
    }

    /**
     * Test that prefer-stable is enabled.
     */
    public function testPreferStableIsEnabled(): void
    {
        $this->assertArrayHasKey('prefer-stable', $this->composerJson, 
            'composer.json must have prefer-stable setting');
        $this->assertTrue($this->composerJson['prefer-stable'], 
            'prefer-stable should be enabled');
    }

    /**
     * Test that composer.lock content-hash is set.
     */
    public function testComposerLockHasContentHash(): void
    {
        $this->assertArrayHasKey('content-hash', $this->composerLock, 
            'composer.lock must have content-hash');
        $this->assertNotEmpty($this->composerLock['content-hash'], 
            'content-hash must not be empty');
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $this->composerLock['content-hash'], 
            'content-hash should be a 32-character hexadecimal string');
    }

    /**
     * Test that composer.lock has correct content-hash for composer.json.
     */
    public function testComposerLockContentHashMatchesComposerJson(): void
    {
        $expectedHash = '4a067c092664226e9a3dbebb75878f9e';
        
        $this->assertEquals($expectedHash, $this->composerLock['content-hash'], 
            'composer.lock content-hash should match expected hash for current composer.json');
    }

    /**
     * Test that packages array in composer.lock is not empty.
     */
    public function testComposerLockPackagesAreNotEmpty(): void
    {
        $this->assertArrayHasKey('packages', $this->composerLock);
        $this->assertIsArray($this->composerLock['packages'], 
            'packages must be an array');
        $this->assertNotEmpty($this->composerLock['packages'], 
            'packages array should not be empty');
    }

    /**
     * Test that packages-dev array in composer.lock is not empty.
     */
    public function testComposerLockPackagesDevAreNotEmpty(): void
    {
        $this->assertArrayHasKey('packages-dev', $this->composerLock);
        $this->assertIsArray($this->composerLock['packages-dev'], 
            'packages-dev must be an array');
        $this->assertNotEmpty($this->composerLock['packages-dev'], 
            'packages-dev array should not be empty');
    }

    /**
     * Test that all packages in composer.lock have required fields.
     */
    public function testComposerLockPackagesHaveRequiredFields(): void
    {
        $requiredFields = ['name', 'version', 'source', 'dist', 'require', 'type'];
        
        foreach ($this->composerLock['packages'] as $package) {
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $package, 
                    sprintf('Package %s must have %s field', $package['name'] ?? 'unknown', $field));
            }
        }
    }

    /**
     * Test that Doctrine DBAL package has correct structure in lock file.
     */
    public function testDbalPackageHasCorrectStructureInLock(): void
    {
        $dbalPackage = $this->findPackageInLock('doctrine/dbal');
        
        $this->assertNotNull($dbalPackage, 'doctrine/dbal must be in composer.lock');
        
        // Verify package structure
        $this->assertArrayHasKey('name', $dbalPackage);
        $this->assertEquals('doctrine/dbal', $dbalPackage['name']);
        
        $this->assertArrayHasKey('version', $dbalPackage);
        $this->assertArrayHasKey('source', $dbalPackage);
        $this->assertArrayHasKey('dist', $dbalPackage);
        
        // Verify source information
        $this->assertArrayHasKey('type', $dbalPackage['source']);
        $this->assertEquals('git', $dbalPackage['source']['type']);
        $this->assertArrayHasKey('url', $dbalPackage['source']);
        $this->assertArrayHasKey('reference', $dbalPackage['source']);
        
        // Verify dist information
        $this->assertArrayHasKey('type', $dbalPackage['dist']);
        $this->assertEquals('zip', $dbalPackage['dist']['type']);
        $this->assertArrayHasKey('url', $dbalPackage['dist']);
        $this->assertArrayHasKey('reference', $dbalPackage['dist']);
    }

    /**
     * Test that DBAL package has correct dependencies in lock file.
     */
    public function testDbalPackageHasCorrectDependencies(): void
    {
        $dbalPackage = $this->findPackageInLock('doctrine/dbal');
        
        $this->assertNotNull($dbalPackage);
        $this->assertArrayHasKey('require', $dbalPackage, 
            'doctrine/dbal must have require section');
        
        $requires = $dbalPackage['require'];
        
        // DBAL 3.x should have different requirements than 4.x
        $this->assertArrayHasKey('php', $requires, 
            'doctrine/dbal must specify PHP requirement');
        
        // Version 3.x should support PHP 7.4+
        $phpRequirement = $requires['php'];
        $this->assertStringContainsString('7.4', $phpRequirement, 
            'doctrine/dbal 3.x should support PHP 7.4');
        
        // Should have doctrine/event-manager for version 3.x
        $this->assertArrayHasKey('doctrine/event-manager', $requires, 
            'doctrine/dbal 3.x should require doctrine/event-manager');
    }

    /**
     * Test that composer.json scripts are defined.
     */
    public function testComposerJsonHasScripts(): void
    {
        $this->assertArrayHasKey('scripts', $this->composerJson, 
            'composer.json should have scripts section');
        
        $scripts = $this->composerJson['scripts'];
        $this->assertArrayHasKey('auto-scripts', $scripts, 
            'scripts should include auto-scripts');
    }

    /**
     * Test that composer.json has extra configuration for Symfony.
     */
    public function testComposerJsonHasSymfonyExtraConfig(): void
    {
        $this->assertArrayHasKey('extra', $this->composerJson, 
            'composer.json should have extra section');
        $this->assertArrayHasKey('symfony', $this->composerJson['extra'], 
            'extra section should include symfony configuration');
        
        $symfonyConfig = $this->composerJson['extra']['symfony'];
        $this->assertArrayHasKey('require', $symfonyConfig, 
            'Symfony extra config should specify version requirement');
        $this->assertEquals('7.3.*', $symfonyConfig['require'], 
            'Symfony version requirement should be 7.3.*');
    }

    /**
     * Test that required extensions are specified.
     */
    public function testRequiredExtensionsAreSpecified(): void
    {
        $requiredExtensions = [
            'ext-ctype',
            'ext-iconv',
            'ext-simplexml',
            'ext-zend-opcache'
        ];
        
        foreach ($requiredExtensions as $extension) {
            $this->assertArrayHasKey($extension, $this->composerJson['require'], 
                sprintf('composer.json must require %s extension', $extension));
            $this->assertEquals('*', $this->composerJson['require'][$extension], 
                sprintf('%s should have * version constraint', $extension));
        }
    }

    /**
     * Test that composer.json doesn't have conflicting packages.
     */
    public function testNoConflictingSymfonyMonorepo(): void
    {
        $this->assertArrayHasKey('conflict', $this->composerJson, 
            'composer.json should have conflict section');
        $this->assertArrayHasKey('symfony/symfony', $this->composerJson['conflict'], 
            'Should conflict with symfony/symfony monorepo');
        $this->assertEquals('*', $this->composerJson['conflict']['symfony/symfony'], 
            'Should conflict with all versions of symfony/symfony monorepo');
    }

    /**
     * Test that security advisories package is in require-dev.
     */
    public function testSecurityAdvisoriesIsInRequireDev(): void
    {
        $this->assertArrayHasKey('roave/security-advisories', $this->composerJson['require-dev'], 
            'roave/security-advisories should be in require-dev for security checks');
        $this->assertEquals('dev-latest', $this->composerJson['require-dev']['roave/security-advisories'], 
            'roave/security-advisories should use dev-latest');
    }

    /**
     * Test that composer.json is properly formatted.
     */
    public function testComposerJsonIsProperlyFormatted(): void
    {
        $path = $this->projectRoot . '/composer.json';
        $content = file_get_contents($path);
        
        // Check for proper indentation (4 spaces is standard for JSON)
        $lines = explode("\n", $content);
        foreach ($lines as $lineNumber => $line) {
            if (empty(trim($line)) || trim($line) === '{' || trim($line) === '}') {
                continue;
            }
            
            // Skip lines that are just closing brackets/braces
            if (preg_match('/^\s*[\]},]/', $line)) {
                continue;
            }
            
            if (preg_match('/^( +)/', $line, $matches)) {
                $spaces = strlen($matches[1]);
                $this->assertEquals(0, $spaces % 4, 
                    sprintf('Line %d in composer.json should use 4-space indentation', $lineNumber + 1));
            }
        }
    }

    /**
     * Test that composer.json ends with newline.
     */
    public function testComposerJsonEndsWithNewline(): void
    {
        $path = $this->projectRoot . '/composer.json';
        $content = file_get_contents($path);
        
        $this->assertStringEndsWith("\n", $content, 
            'composer.json should end with a newline');
    }

    /**
     * Test that composer.lock ends with newline.
     */
    public function testComposerLockEndsWithNewline(): void
    {
        $path = $this->projectRoot . '/composer.lock';
        $content = file_get_contents($path);
        
        $this->assertStringEndsWith("\n", $content, 
            'composer.lock should end with a newline');
    }

    /**
     * Test that no development dependencies are in production require.
     */
    public function testNoDevelopmentDependenciesInRequire(): void
    {
        $developmentPackages = ['phpunit', 'mockery', 'debug', 'profiler', 'maker'];
        
        foreach ($this->composerJson['require'] as $package) {
            foreach ($developmentPackages as $devKeyword) {
                $this->assertStringNotContainsString($devKeyword, strtolower($package), 
                    sprintf('Development package pattern "%s" should not be in require, found in %s', 
                        $devKeyword, $package));
            }
        }
    }

    /**
     * Helper method to find a package in composer.lock.
     */
    private function findPackageInLock(string $packageName): ?array
    {
        foreach ($this->composerLock['packages'] as $package) {
            if ($package['name'] === $packageName) {
                return $package;
            }
        }
        
        foreach ($this->composerLock['packages-dev'] as $package) {
            if ($package['name'] === $packageName) {
                return $package;
            }
        }
        
        return null;
    }

    /**
     * Test that Doctrine DBAL has expected time property in lock file.
     */
    public function testDbalPackageHasTimeProperty(): void
    {
        $dbalPackage = $this->findPackageInLock('doctrine/dbal');
        
        $this->assertNotNull($dbalPackage);
        $this->assertArrayHasKey('time', $dbalPackage, 
            'doctrine/dbal package should have time property');
        
        // Verify time format (ISO 8601)
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}$/', 
            $dbalPackage['time'], 
            'time property should be in ISO 8601 format');
    }

    /**
     * Test package count is reasonable.
     */
    public function testComposerLockHasReasonablePackageCount(): void
    {
        $totalPackages = count($this->composerLock['packages']) + 
                        count($this->composerLock['packages-dev']);
        
        $this->assertGreaterThan(10, $totalPackages, 
            'Total package count should be greater than 10 for a Symfony project');
        $this->assertLessThan(500, $totalPackages, 
            'Total package count should be reasonable (less than 500)');
    }
}