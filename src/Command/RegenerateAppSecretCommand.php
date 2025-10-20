<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:regenerate-app-secret',
    description: 'Команда для генерации нового секретного ключа приложения APP_SECRET',
)]
class RegenerateAppSecretCommand extends Command
{
    public function __invoke(
        SymfonyStyle $symfonyStyle,
    ): int {
        $secret = bin2hex(random_bytes(16));
        $envPath = \dirname(__DIR__, 2) . '/.env';

        if (!is_file($envPath) || !is_writable($envPath)) {
            $symfonyStyle->error('Файл `.env` не найден или недоступен для записи');

            return Command::FAILURE;
        }

        $env = file_get_contents($envPath);

        if ($env === false) {
            $symfonyStyle->error('Не удалось прочитать файл `.env`');

            return Command::FAILURE;
        }

        $updated = preg_replace('/^APP_SECRET=.*/m', 'APP_SECRET=' . $secret, $env, 1);

        if ($updated === null) {
            throw new \RuntimeException('Ошибка при обновлении `APP_SECRET`.');
        }

        if ($updated === $env) {
            $updated .= \PHP_EOL . 'APP_SECRET=' . $secret . \PHP_EOL;
        }

        if (file_put_contents($envPath, $updated) === false) {
            $symfonyStyle->error('Не удалось записать в файл `.env`');

            return Command::FAILURE;
        }

        $symfonyStyle->success('Новый APP_SECRET был сгенерирован: ' . $secret);

        return Command::SUCCESS;
    }
}
