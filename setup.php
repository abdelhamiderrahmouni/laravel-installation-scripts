#!/usr/bin/env php
<?php

declare(strict_types=1);

class SetupScript
{
    private const COLOR_GREEN = "\033[32m";
    private const COLOR_YELLOW = "\033[33m";
    private const COLOR_CYAN = "\033[36m";
    private const COLOR_RESET = "\033[0m";

    // Bright colors
    private const COLOR_BRIGHT_RED = "\033[91m";
    private const COLOR_BRIGHT_YELLOW = "\033[93m";

    // Text styles
    private const STYLE_BOLD = "\033[1m";
    private const STYLE_UNDERLINE = "\033[4m";

    private array $skipCommands = [];
    private array $onlyCommands = [];

    public function __construct(array $argv, int $argc)
    {
        $this->parseArguments($argv, $argc);
        $this->validateEnvironment();
    }

    public function parseArguments(array $argv, int $argc): void
    {
        for ($i = 1; $i < $argc; $i++) {
            $i = match ($argv[$i]) {
                '--without', '--skip' => $this->handleSkipCommand($i, $argv),
                '--only' => $this->handleOnlyCommand($i, $argv),
                '-h', '--help' => $this->handleHelpCommand(),
                default => $this->handleUnknownArgument($argv[$i])
            };
        }
    }

    private function handleSkipCommand(int $i, array $argv): int
    {
        if (!isset($argv[$i + 1])) {
            return $i;
        }

        $this->skipCommands = explode(',', $argv[$i + 1]);
        return $i + 1;
    }

    private function handleOnlyCommand(int $i, array $argv): int
    {
        if (!isset($argv[$i + 1])) {
            return $i;
        }

        $this->onlyCommands = explode(',', $argv[$i + 1]);
        return $i + 1;
    }

    private function handleHelpCommand(): never
    {
        $this->printUsage();
        exit(0);
    }

    private function handleUnknownArgument(string $argument): never
    {
        $this->error("Unknown argument: {$argument}");
        $this->printUsage();
        exit(1);
    }

    private function validateEnvironment(): void
    {
        if (!file_exists('composer.json')) {
            $this->error('Please make sure to run this script from the root directory of this repo.');
            exit(1);
        }
    }

    private function shouldExecuteCommand(string $command): bool
    {
        return (empty($this->onlyCommands) || in_array($command, $this->onlyCommands)) &&
            !in_array($command, $this->skipCommands, true);
    }

    private function executeCommand(string $command, string $message): void
    {
        $this->info($message);
        passthru($command, $returnStatus);

        if ($returnStatus !== 0) {
            $this->error("Error occurred while executing: $command");
            exit(1);
        }
    }

    private function info(string $message): void
    {
        echo "\n". self::COLOR_GREEN . $message . self::COLOR_RESET . "\n";
    }

    private function error(string $message): void
    {
        echo self::COLOR_BRIGHT_RED . "🚨🚨🚨 $message" . self::COLOR_RESET . "\n";
    }

    private function setAppInstalled(bool $status): void
    {
        if (!empty($this->onlyCommands) && !in_array('app_installed', $this->onlyCommands, true)) {
            return;
        }

        $envFile = '.env';
        $envKey = 'APP_INSTALLED';

        if (!file_exists($envFile)) {
            $this->error('.env file not found.');
            exit(1);
        }

        $envContent = file_get_contents($envFile);
        if (str_contains($envContent, "$envKey=")) {
            $envContent = preg_replace("/^$envKey=.*/m", "$envKey=" . ($status ? 'true' : 'false'), $envContent);
        } else {
            $envContent .= "\n$envKey=" . ($status ? 'true' : 'false');
        }

        file_put_contents($envFile, $envContent);
        $this->info('✅ APP_INSTALLED set to ' . ($status ? 'true' : 'false') . ' in .env.');
    }

    // Individual command functions
    private function copyEnvFile(): void
    {
        if ($this->shouldExecuteCommand('cp_env')) {
            $this->info('📰 Copying .env.example to .env...');

            if (!copy('.env.example', '.env')) {
                $this->error("Error occurred while executing: cp .env.example .env");
                exit(1);
            }
        }
    }

    private function installComposerDependencies(): void
    {
        if ($this->shouldExecuteCommand('composer')) {
            $this->executeCommand('composer install', '⚗️ Running composer install...');
        }
    }

    private function generateApplicationKey(): void
    {
        if ($this->shouldExecuteCommand('key_generate')) {
            $this->executeCommand('php artisan key:generate', '🔑 Generating application key...');
        }
    }

    private function createStorageLink(): void
    {
        if ($this->shouldExecuteCommand('storage_link')) {
            $this->executeCommand('php artisan storage:link --force', '🔗 Linking storage...');
        }
    }

    private function installNpmPackages(): void
    {
        if ($this->shouldExecuteCommand('npm_install')) {
            $this->executeCommand('npm install', '⚗️ Installing npm packages...');
        }
    }

    private function buildNpmAssets(): void
    {
        if ($this->shouldExecuteCommand('npm_build')) {
            $this->executeCommand('npm run build', '🏗️ Running npm build...');
        }
    }

    private function runMigrations(): void
    {
        if ($this->shouldExecuteCommand('migrate')) {
            $this->executeCommand('php artisan migrate', '🗄️ Running migrations...');
        }
    }

    private function seedDatabase(): void
    {
        if ($this->shouldExecuteCommand('seed')) {
            $this->executeCommand('php artisan db:seed', '🌱 Seeding database...');
        }
    }

    private function clearCache(): void
    {
        if ($this->shouldExecuteCommand('optimize')) {
            $this->executeCommand('php artisan optimize:clear', '🧹 Clearing cache...');
        }
    }

    private function generateIdeHelperDocs(): void
    {
        if ($this->shouldExecuteCommand('ide_helper')) {
            $this->executeCommand(
                'php artisan ide-helper:generate && php artisan ide-helper:meta',
                '📝 Generating IDE helper docs...'
            );
        }
    }

    private function printUsage(): void
    {
        $logo = <<<ASCII

 ███████╗ ███████╗████████╗██╗   ██╗██████╗
 ██╔════╝ ██╔════╝╚══██╔══╝██║   ██║██╔══██╗
 ███████╗ █████╗     ██║   ██║   ██║██████╔╝
 ╚════██║ ██╔══╝     ██║   ██║   ██║██╔═══╝
 ███████║ ███████╗   ██║   ╚██████╔╝██║
 ╚══════╝ ╚══════╝   ╚═╝    ╚═════╝ ╚═╝

 Laravel Project Setup Assistant v1.0
 -------------------------------------
ASCII;

        $commandDescriptions = [
            'cp_env' =>  'Copy .env.example to .env',
            'composer' =>  'Install Composer Dependencies',
            'key_generate' =>  'Generate Application Key',
            'storage_link' =>  'Create Storage Symlink',
            'npm_install' =>  'Install NPM Packages',
            'npm_build' =>  'Build Frontend Assets',
            'migrate' =>  'Run Database Migrations',
            'seed' =>  'Seed Database',
            'optimize' =>  'Clear Application Cache',
            'ide_helper' =>  'Generate IDE Helper Files',
        ];

        // Print logo
        echo self::COLOR_YELLOW  . $logo . self::COLOR_RESET . "\n";

        // Print usage instructions
        echo "Usage:\n";
        echo self::COLOR_GREEN . "  php bin/setup.php [options]\n\n" . self::COLOR_RESET;

        echo "Options:\n";
        echo self::COLOR_GREEN . "  --without COMMAND1,COMMAND2 .... ". self::COLOR_RESET ."Skip specified commands\n";
        echo self::COLOR_GREEN . "  --skip COMMAND1,COMMAND2 ....... ". self::COLOR_RESET ."Skip specified commands (alias for --without)\n";
        echo self::COLOR_GREEN . "  --only COMMAND1,COMMAND2 ....... ". self::COLOR_RESET ."Run only specified commands\n";
        echo self::COLOR_GREEN . "  -h, --help ..................... ". self::COLOR_RESET ."Display this help message\n\n";

        echo "Available Commands:\n";

        // Print detailed command descriptions
        foreach ($commandDescriptions as $command => $info) {
            $command = str_pad($command . ' ', 30, '.', STR_PAD_RIGHT);
            echo self::COLOR_GREEN . "  ▶ {$command} " . self::COLOR_RESET . $info . "\n";
        }
    }

    public function run(): void
    {
        $this->setAppInstalled(false);

        // Execute all commands in sequence
        $this->copyEnvFile();
        $this->installComposerDependencies();
        $this->generateApplicationKey();
        $this->createStorageLink();
        $this->installNpmPackages();
        $this->buildNpmAssets();
        $this->runMigrations();
        $this->seedDatabase();
        $this->clearCache();
        $this->generateIdeHelperDocs();

        $this->setAppInstalled(true);

        if (empty($this->onlyCommands)) {
            $this->info('🥳 All tasks completed successfully.');
        }
    }
}

// Bootstrap the script
$setup = new SetupScript($_SERVER['argv'], $_SERVER['argc']);
$setup->run();
