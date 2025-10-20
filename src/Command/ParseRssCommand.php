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
        SymfonyStyle $symfonyStyle,
        #[Option(description: 'ID источника для парсинга', name: 'source-id')]
        ?int $sourceId = null,
        #[Option(description: 'Обрабатывать источники асинхронно с использованием очереди сообщений', name: 'async')]
        bool $async = false,
    ): int {
        if ($sourceId) {
            $source = $this->newsSourceRepository->find($sourceId);

            if ($source === null) {
                $symfonyStyle->error($this->translator->trans('command.parse_rss.messages.source_not_found', ['%id%' => $sourceId]));

                return Command::FAILURE;
            }

            $sources = [$source];
        } else {
            $sources = $this->newsSourceRepository->findActiveSources();
        }

        if (empty($sources)) {
            $symfonyStyle->warning($this->translator->trans('command.parse_rss.messages.no_active_sources'));

            return Command::SUCCESS;
        }

        $symfonyStyle->title($this->translator->trans('command.parse_rss.messages.parsing_started'));
        $symfonyStyle->info($this->translator->trans('command.parse_rss.messages.found_sources', ['%count%' => \count($sources)]));

        if ($async) {
            return $this->processAsync($sources, $symfonyStyle);
        }

        return $this->processSync($sources, $symfonyStyle);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    private function processSync(array $sources, SymfonyStyle $symfonyStyle): int
    {
        $totalItems = 0;
        $successCount = 0;
        $errorCount = 0;

        $progressBar = $symfonyStyle->createProgressBar(\count($sources));
        $progressBar->start();

        foreach ($sources as $source) {
            try {
                $itemsCount = $this->rssParserService->parseRssFeed($source);
                $totalItems += $itemsCount;
                ++$successCount;

                $symfonyStyle->newLine();
                $symfonyStyle->success($this->translator->trans('command.parse_rss.messages.source_success', [
                    '%name%' => $source->getName(),
                    '%count%' => $itemsCount,
                ]));
            } catch (\Exception $e) {
                ++$errorCount;
                $symfonyStyle->newLine();
                $symfonyStyle->error($this->translator->trans('command.parse_rss.messages.source_error', [
                    '%name%' => $source->getName(),
                    '%error%' => $e->getMessage(),
                ]));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $symfonyStyle->newLine(2);

        $symfonyStyle->success($this->translator->trans('command.parse_rss.messages.processing_complete', [
            '%success%' => $successCount,
            '%errors%' => $errorCount,
        ]));

        $symfonyStyle->info($this->translator->trans('command.parse_rss.messages.items_processed', ['%total%' => $totalItems]));

        return Command::SUCCESS;
    }

    /**
     * @throws ExceptionInterface
     */
    private function processAsync(array $sources, SymfonyStyle $symfonyStyle): int
    {
        $symfonyStyle->info($this->translator->trans('command.parse_rss.messages.async_processing', ['%count%' => \count($sources)]));

        foreach ($sources as $source) {
            $this->messageBus->dispatch(new ParseRssMessage($source->id));
        }

        $symfonyStyle->success($this->translator->trans('command.parse_rss.messages.parsing_completed'));

        return Command::SUCCESS;
    }
}
