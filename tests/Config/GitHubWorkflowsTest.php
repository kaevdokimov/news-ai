<?php

namespace App\Tests\Config;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Comprehensive tests for GitHub Actions workflow configuration files.
 * 
 * These tests validate YAML syntax, required fields, security configurations,
 * and critical workflow settings to ensure CI/CD reliability.
 */
class GitHubWorkflowsTest extends TestCase
{
    private string $projectRoot;

    protected function setUp(): void
    {
        $this->projectRoot = dirname(__DIR__, 2);
    }

    /**
     * Test that docker-image.yml workflow file exists and is readable.
     */
    public function testDockerImageWorkflowExists(): void
    {
        $workflowPath = $this->projectRoot . '/.github/workflows/docker-image.yml';
        $this->assertFileExists($workflowPath, 'Docker Image workflow file should exist');
        $this->assertFileIsReadable($workflowPath, 'Docker Image workflow file should be readable');
    }

    /**
     * Test that symfony.yml workflow file exists and is readable.
     */
    public function testSymfonyWorkflowExists(): void
    {
        $workflowPath = $this->projectRoot . '/.github/workflows/symfony.yml';
        $this->assertFileExists($workflowPath, 'Symfony workflow file should exist');
        $this->assertFileIsReadable($workflowPath, 'Symfony workflow file should be readable');
    }

    /**
     * Test that docker-image.yml contains valid YAML syntax.
     */
    public function testDockerImageWorkflowHasValidYamlSyntax(): void
    {
        $workflowPath = $this->projectRoot . '/.github/workflows/docker-image.yml';
        $content = file_get_contents($workflowPath);
        
        try {
            $parsed = Yaml::parse($content);
            $this->assertIsArray($parsed, 'Parsed YAML should be an array');
        } catch (ParseException $e) {
            $this->fail('Docker Image workflow YAML is invalid: ' . $e->getMessage());
        }
    }

    /**
     * Test that symfony.yml contains valid YAML syntax.
     */
    public function testSymfonyWorkflowHasValidYamlSyntax(): void
    {
        $workflowPath = $this->projectRoot . '/.github/workflows/symfony.yml';
        $content = file_get_contents($workflowPath);
        
        try {
            $parsed = Yaml::parse($content);
            $this->assertIsArray($parsed, 'Parsed YAML should be an array');
        } catch (ParseException $e) {
            $this->fail('Symfony workflow YAML is invalid: ' . $e->getMessage());
        }
    }

    /**
     * Test that docker-image.yml has required top-level keys.
     */
    public function testDockerImageWorkflowHasRequiredKeys(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $this->assertArrayHasKey('name', $workflow, 'Workflow must have a name');
        $this->assertArrayHasKey('on', $workflow, 'Workflow must have trigger configuration');
        $this->assertArrayHasKey('jobs', $workflow, 'Workflow must have jobs');
        $this->assertArrayHasKey('permissions', $workflow, 'Workflow must have permissions configuration');
    }

    /**
     * Test that symfony.yml has required top-level keys.
     */
    public function testSymfonyWorkflowHasRequiredKeys(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $this->assertArrayHasKey('name', $workflow, 'Workflow must have a name');
        $this->assertArrayHasKey('on', $workflow, 'Workflow must have trigger configuration');
        $this->assertArrayHasKey('jobs', $workflow, 'Workflow must have jobs');
        $this->assertArrayHasKey('permissions', $workflow, 'Workflow must have permissions configuration');
    }

    /**
     * Test that docker-image.yml has proper workflow name.
     */
    public function testDockerImageWorkflowNameIsSet(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $this->assertIsString($workflow['name'], 'Workflow name must be a string');
        $this->assertNotEmpty($workflow['name'], 'Workflow name must not be empty');
        $this->assertEquals('Docker Image CI', $workflow['name'], 'Workflow name should be "Docker Image CI"');
    }

    /**
     * Test that symfony.yml has proper workflow name.
     */
    public function testSymfonyWorkflowNameIsSet(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $this->assertIsString($workflow['name'], 'Workflow name must be a string');
        $this->assertNotEmpty($workflow['name'], 'Workflow name must not be empty');
        $this->assertEquals('Symfony', $workflow['name'], 'Workflow name should be "Symfony"');
    }

    /**
     * Test that docker-image.yml has proper permissions configuration.
     */
    public function testDockerImageWorkflowHasReadOnlyPermissions(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $this->assertArrayHasKey('permissions', $workflow, 'Workflow must have permissions');
        $this->assertArrayHasKey('contents', $workflow['permissions'], 'Permissions must specify contents access');
        $this->assertEquals('read', $workflow['permissions']['contents'], 
            'Contents permission should be read-only for security');
    }

    /**
     * Test that symfony.yml has proper permissions configuration.
     */
    public function testSymfonyWorkflowHasReadOnlyPermissions(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $this->assertArrayHasKey('permissions', $workflow, 'Workflow must have permissions');
        $this->assertArrayHasKey('contents', $workflow['permissions'], 'Permissions must specify contents access');
        $this->assertEquals('read', $workflow['permissions']['contents'], 
            'Contents permission should be read-only for security');
    }

    /**
     * Test that docker-image.yml triggers on correct branches.
     */
    public function testDockerImageWorkflowTriggersOnMainBranch(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $this->assertArrayHasKey('on', $workflow, 'Workflow must have trigger configuration');
        
        // Test push triggers
        $this->assertArrayHasKey('push', $workflow['on'], 'Workflow should trigger on push');
        $this->assertArrayHasKey('branches', $workflow['on']['push'], 'Push trigger must specify branches');
        $this->assertContains('main', $workflow['on']['push']['branches'], 
            'Workflow should trigger on push to main branch');
        
        // Test pull request triggers
        $this->assertArrayHasKey('pull_request', $workflow['on'], 'Workflow should trigger on pull requests');
        $this->assertArrayHasKey('branches', $workflow['on']['pull_request'], 
            'Pull request trigger must specify branches');
        $this->assertContains('main', $workflow['on']['pull_request']['branches'], 
            'Workflow should trigger on pull requests to main branch');
    }

    /**
     * Test that symfony.yml triggers on correct branches.
     */
    public function testSymfonyWorkflowTriggersOnMainBranch(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $this->assertArrayHasKey('on', $workflow, 'Workflow must have trigger configuration');
        
        // Test push triggers
        $this->assertArrayHasKey('push', $workflow['on'], 'Workflow should trigger on push');
        $this->assertArrayHasKey('branches', $workflow['on']['push'], 'Push trigger must specify branches');
        $this->assertContains('main', $workflow['on']['push']['branches'], 
            'Workflow should trigger on push to main branch');
        
        // Test pull request triggers
        $this->assertArrayHasKey('pull_request', $workflow['on'], 'Workflow should trigger on pull requests');
        $this->assertArrayHasKey('branches', $workflow['on']['pull_request'], 
            'Pull request trigger must specify branches');
        $this->assertContains('main', $workflow['on']['pull_request']['branches'], 
            'Workflow should trigger on pull requests to main branch');
    }

    /**
     * Test that docker-image.yml has valid job configuration.
     */
    public function testDockerImageWorkflowHasValidBuildJob(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $this->assertArrayHasKey('jobs', $workflow, 'Workflow must have jobs');
        $this->assertArrayHasKey('build', $workflow['jobs'], 'Workflow must have a build job');
        
        $buildJob = $workflow['jobs']['build'];
        $this->assertArrayHasKey('runs-on', $buildJob, 'Build job must specify runner');
        $this->assertEquals('ubuntu-latest', $buildJob['runs-on'], 
            'Build job should run on ubuntu-latest');
        
        $this->assertArrayHasKey('steps', $buildJob, 'Build job must have steps');
        $this->assertIsArray($buildJob['steps'], 'Build job steps must be an array');
        $this->assertNotEmpty($buildJob['steps'], 'Build job must have at least one step');
    }

    /**
     * Test that symfony.yml has valid job configuration with matrix strategy.
     */
    public function testSymfonyWorkflowHasValidTestJob(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $this->assertArrayHasKey('jobs', $workflow, 'Workflow must have jobs');
        $this->assertArrayHasKey('symfony-tests', $workflow['jobs'], 'Workflow must have symfony-tests job');
        
        $testJob = $workflow['jobs']['symfony-tests'];
        $this->assertArrayHasKey('runs-on', $testJob, 'Test job must specify runner');
        $this->assertEquals('ubuntu-latest', $testJob['runs-on'], 
            'Test job should run on ubuntu-latest');
        
        $this->assertArrayHasKey('strategy', $testJob, 'Test job must have a strategy');
        $this->assertArrayHasKey('matrix', $testJob['strategy'], 'Strategy must include matrix');
        
        $this->assertArrayHasKey('steps', $testJob, 'Test job must have steps');
        $this->assertIsArray($testJob['steps'], 'Test job steps must be an array');
        $this->assertNotEmpty($testJob['steps'], 'Test job must have at least one step');
    }

    /**
     * Test that symfony.yml has proper PHP version matrix.
     */
    public function testSymfonyWorkflowHasCorrectPhpVersions(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $matrix = $workflow['jobs']['symfony-tests']['strategy']['matrix'];
        $this->assertArrayHasKey('php-version', $matrix, 'Matrix must specify PHP versions');
        
        $phpVersions = $matrix['php-version'];
        $this->assertIsArray($phpVersions, 'PHP versions must be an array');
        $this->assertNotEmpty($phpVersions, 'PHP versions array must not be empty');
        
        // Verify supported PHP versions (8.3 and 8.4 as per composer.json requirement)
        $this->assertContains('8.3', $phpVersions, 'Matrix should include PHP 8.3');
        $this->assertContains('8.4', $phpVersions, 'Matrix should include PHP 8.4');
    }

    /**
     * Test that docker-image.yml uses actions/checkout@v4.
     */
    public function testDockerImageWorkflowUsesCheckoutV4(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $steps = $workflow['jobs']['build']['steps'];
        $checkoutStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['uses']) && str_contains($step['uses'], 'actions/checkout')) {
                $checkoutStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($checkoutStep, 'Workflow must have a checkout step');
        $this->assertEquals('actions/checkout@v4', $checkoutStep['uses'], 
            'Checkout action should use v4');
    }

    /**
     * Test that symfony.yml uses actions/checkout@v4.
     */
    public function testSymfonyWorkflowUsesCheckoutV4(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $checkoutStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['uses']) && str_contains($step['uses'], 'actions/checkout')) {
                $checkoutStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($checkoutStep, 'Workflow must have a checkout step');
        $this->assertEquals('actions/checkout@v4', $checkoutStep['uses'], 
            'Checkout action should use v4');
    }

    /**
     * Test that docker-image.yml has docker build steps.
     */
    public function testDockerImageWorkflowHasDockerBuildSteps(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $steps = $workflow['jobs']['build']['steps'];
        $dockerSteps = array_filter($steps, function($step) {
            return isset($step['name']) && 
                   (str_contains(strtolower($step['name']), 'docker') && 
                    str_contains(strtolower($step['name']), 'build'));
        });
        
        $this->assertGreaterThanOrEqual(1, count($dockerSteps), 
            'Workflow must have at least one Docker build step');
        
        // Verify build commands contain 'docker build'
        foreach ($dockerSteps as $step) {
            $this->assertArrayHasKey('run', $step, 'Docker build step must have run command');
            $this->assertStringContainsString('docker build', $step['run'], 
                'Step should contain docker build command');
        }
    }

    /**
     * Test that symfony.yml has PHPUnit test execution step.
     */
    public function testSymfonyWorkflowHasPhpUnitStep(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $phpunitStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['name']) && str_contains(strtolower($step['name']), 'test')) {
                $phpunitStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($phpunitStep, 'Workflow must have a test execution step');
        $this->assertArrayHasKey('run', $phpunitStep, 'Test step must have run command');
        $this->assertStringContainsString('phpunit', strtolower($phpunitStep['run']), 
            'Test step should execute PHPUnit');
    }

    /**
     * Test that symfony.yml has PHP setup step with matrix variable.
     */
    public function testSymfonyWorkflowHasPhpSetupWithMatrix(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $phpSetupStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['uses']) && str_contains($step['uses'], 'shivammathur/setup-php')) {
                $phpSetupStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($phpSetupStep, 'Workflow must have PHP setup step');
        $this->assertArrayHasKey('with', $phpSetupStep, 'PHP setup must have with configuration');
        $this->assertArrayHasKey('php-version', $phpSetupStep['with'], 
            'PHP setup must specify PHP version');
        
        // Verify it uses matrix variable
        $phpVersion = $phpSetupStep['with']['php-version'];
        $this->assertStringContainsString('matrix.php-version', $phpVersion, 
            'PHP version should use matrix variable');
    }

    /**
     * Test that symfony.yml has database setup step.
     */
    public function testSymfonyWorkflowHasDatabaseSetup(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $postgresStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['name']) && str_contains(strtolower($step['name']), 'postgresql')) {
                $postgresStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($postgresStep, 'Workflow must have PostgreSQL setup step');
        $this->assertArrayHasKey('uses', $postgresStep, 'PostgreSQL step must use an action');
        $this->assertArrayHasKey('with', $postgresStep, 'PostgreSQL step must have configuration');
    }

    /**
     * Test that symfony.yml has composer install step.
     */
    public function testSymfonyWorkflowHasComposerInstallStep(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $composerStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['name']) && str_contains(strtolower($step['name']), 'dependencies')) {
                $composerStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($composerStep, 'Workflow must have composer install step');
        $this->assertArrayHasKey('run', $composerStep, 'Composer step must have run command');
        $this->assertStringContainsString('composer install', $composerStep['run'], 
            'Step should run composer install');
    }

    /**
     * Test that symfony.yml uses composer cache action.
     */
    public function testSymfonyWorkflowHasComposerCache(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $cacheStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['uses']) && str_contains($step['uses'], 'actions/cache')) {
                $cacheStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($cacheStep, 'Workflow should have composer cache step for performance');
        $this->assertArrayHasKey('with', $cacheStep, 'Cache step must have configuration');
        $this->assertArrayHasKey('path', $cacheStep['with'], 'Cache must specify path');
        $this->assertArrayHasKey('key', $cacheStep['with'], 'Cache must specify key');
    }

    /**
     * Test workflow files have no tabs (YAML best practice).
     */
    public function testWorkflowFilesDoNotContainTabs(): void
    {
        $workflows = ['docker-image.yml', 'symfony.yml'];
        
        foreach ($workflows as $workflow) {
            $path = $this->projectRoot . '/.github/workflows/' . $workflow;
            $content = file_get_contents($path);
            
            $this->assertStringNotContainsString("\t", $content, 
                sprintf('%s should not contain tabs, use spaces for indentation', $workflow));
        }
    }

    /**
     * Test workflow files use consistent indentation.
     */
    public function testWorkflowFilesHaveConsistentIndentation(): void
    {
        $workflows = ['docker-image.yml', 'symfony.yml'];
        
        foreach ($workflows as $workflow) {
            $path = $this->projectRoot . '/.github/workflows/' . $workflow;
            $content = file_get_contents($path);
            $lines = explode("\n", $content);
            
            foreach ($lines as $lineNumber => $line) {
                if (empty(trim($line)) || str_starts_with(trim($line), '#')) {
                    continue; // Skip empty lines and comments
                }
                
                // Check if line has leading spaces
                if (preg_match('/^( +)/', $line, $matches)) {
                    $spaces = strlen($matches[1]);
                    // YAML typically uses 2 or 4 space indentation
                    $this->assertEquals(0, $spaces % 2, 
                        sprintf('%s line %d has inconsistent indentation (not a multiple of 2)', 
                            $workflow, $lineNumber + 1));
                }
            }
        }
    }

    /**
     * Test docker-image.yml has all expected docker build steps.
     */
    public function testDockerImageWorkflowBuildsAllRequiredImages(): void
    {
        $workflow = $this->parseWorkflow('docker-image.yml');
        
        $steps = $workflow['jobs']['build']['steps'];
        $dockerBuildSteps = array_filter($steps, function($step) {
            return isset($step['run']) && str_contains($step['run'], 'docker build');
        });
        
        $this->assertGreaterThanOrEqual(2, count($dockerBuildSteps), 
            'Workflow should build at least 2 Docker images (local and production)');
    }

    /**
     * Test symfony.yml environment variables are properly set.
     */
    public function testSymfonyWorkflowHasDatabaseEnvironmentVariables(): void
    {
        $workflow = $this->parseWorkflow('symfony.yml');
        
        $steps = $workflow['jobs']['symfony-tests']['steps'];
        $testStep = null;
        
        foreach ($steps as $step) {
            if (isset($step['name']) && str_contains(strtolower($step['name']), 'test')) {
                $testStep = $step;
                break;
            }
        }
        
        $this->assertNotNull($testStep, 'Test step must exist');
        
        if (isset($testStep['env'])) {
            $this->assertArrayHasKey('DATABASE_URL', $testStep['env'], 
                'Test step should have DATABASE_URL environment variable');
            $this->assertStringContainsString('postgresql', $testStep['env']['DATABASE_URL'], 
                'DATABASE_URL should be a PostgreSQL connection string');
        }
    }

    /**
     * Test that workflow files end with newline.
     */
    public function testWorkflowFilesEndWithNewline(): void
    {
        $workflows = ['docker-image.yml', 'symfony.yml'];
        
        foreach ($workflows as $workflow) {
            $path = $this->projectRoot . '/.github/workflows/' . $workflow;
            $content = file_get_contents($path);
            
            $this->assertStringEndsWith("\n", $content, 
                sprintf('%s should end with a newline character', $workflow));
        }
    }

    /**
     * Helper method to parse a workflow file.
     */
    private function parseWorkflow(string $filename): array
    {
        $workflowPath = $this->projectRoot . '/.github/workflows/' . $filename;
        $content = file_get_contents($workflowPath);
        
        try {
            return Yaml::parse($content);
        } catch (ParseException $e) {
            $this->fail(sprintf('Failed to parse %s: %s', $filename, $e->getMessage()));
        }
    }
}