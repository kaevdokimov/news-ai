<?php

declare(strict_types=1);

namespace App\Command;

use App\Message\ParseRssMessage;
use App\Repository\NewsSourceRepository;
use App\Service\RssParserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsCommand(
    name: 'app:parse-rss',
    description: 'command.parse_rss.description',
)]
readonly class ParseRssCommand
{
    public function __construct(
        private NewsSourceRepository $newsSourceRepository,
        private RssParserService $rssParserService,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator,
    ) {}

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     */
    public function __invoke(
        SymfonyStyle $io,
        #[Option(description: 'ID источника для парсинга', name: 'source-id')]
        ?int $sourceId = null,
        #[Option(description: 'Обрабатывать источники асинхронно с использованием очереди сообщений', name: 'async')]
        bool $async = false,
    ): int {
        if ($sourceId) {
            $source = $this->newsSourceRepository->find($sourceId);

            if (!$source) {
                $io->error($this->translator->trans('command.parse_rss.messages.source_not_found', ['%id%' => $sourceId]));

                return Command::FAILURE;
            }
            $sources = [$source];
        } else {
            $sources = $this->newsSourceRepository->findActiveSources();
        }

        if (empty($sources)) {
            $io->warning($this->translator->trans('command.parse_rss.messages.no_active_sources'));

            return Command::SUCCESS;
        }
        $io->title($this->translator->trans('command.parse_rss.messages.parsing_started'));
        $io->info($this->translator->trans('command.parse_rss.messages.found_sources', ['%count%' => \count($sources)]));

        if ($async) {
            return $this->processAsync($sources, $io);
        }

        return $this->processSync($sources, $io);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function processSync(array $sources, SymfonyStyle $io): int
    {
        $totalItems = 0;
        $successCount = 0;
        $errorCount = 0;

        $progressBar = $io->createProgressBar(\count($sources));
        $progressBar->start();

        foreach ($sources as $source) {
            try {
                $itemsCount = $this->rssParserService->parseRssFeed($source);
                $totalItems += $itemsCount;
                ++$successCount;

                $io->newLine();
                $io->success($this->translator->trans('command.parse_rss.messages.source_success', [
                    '%name%' => $source->getName(),
                    '%count%' => $itemsCount,
                ]));
            } catch (\Exception $e) {
                ++$errorCount;
                $io->newLine();
                $io->error($this->translator->trans('command.parse_rss.messages.source_error', [
                    '%name%' => $source->getName(),
                    '%error%' => $e->getMessage(),
                ]));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success($this->translator->trans('command.parse_rss.messages.processing_complete', [
            '%success%' => $successCount,
            '%errors%' => $errorCount,
        ]));

        $io->info($this->translator->trans('command.parse_rss.messages.items_processed', ['%total%' => $totalItems]));

        return Command::SUCCESS;
    }

    /**
     * @throws ExceptionInterface
     */
    private function processAsync(array $sources, SymfonyStyle $io): int
    {
        $io->info($this->translator->trans('command.parse_rss.messages.async_processing', ['%count%' => \count($sources)]));

        foreach ($sources as $source) {
            $this->messageBus->dispatch(new ParseRssMessage($source->id));
        }

        $io->success($this->translator->trans('command.parse_rss.messages.parsing_completed'));

        return Command::SUCCESS;
    }
}
