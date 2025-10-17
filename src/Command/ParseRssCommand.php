<?php

namespace App\Command;

use App\Message\ParseRssMessage;
use App\Repository\NewsSourceRepository;
use App\Service\RssParserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
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
class ParseRssCommand extends Command
{
    public function __construct(
        private NewsSourceRepository $newsSourceRepository,
        private RssParserService $rssParserService,
        private MessageBusInterface $messageBus,
        private TranslatorInterface $translator
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source-id', null, InputOption::VALUE_OPTIONAL, $this->translator->trans('command.parse_rss.options.source_id'))
            ->addOption('async', null, InputOption::VALUE_NONE, $this->translator->trans('command.parse_rss.options.async'))
            ->setHelp($this->translator->trans('command.parse_rss.help'));
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws ExceptionInterface
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sourceId = $input->getOption('source-id');
        $async = $input->getOption('async');

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
        $io->info($this->translator->trans('command.parse_rss.messages.found_sources', ['%count%' => count($sources)]));

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

        $progressBar = $io->createProgressBar(count($sources));
        $progressBar->start();

        foreach ($sources as $source) {
            try {
                $itemsCount = $this->rssParserService->parseRssFeed($source);
                $totalItems += $itemsCount;
                $successCount++;

                $io->newLine();
                $io->success($this->translator->trans('command.parse_rss.messages.source_success', [
                    '%name%' => $source->getName(),
                    '%count%' => $itemsCount
                ]));
            } catch (\Exception $e) {
                $errorCount++;
                $io->newLine();
                $io->error($this->translator->trans('command.parse_rss.messages.source_error', [
                    '%name%' => $source->getName(),
                    '%error%' => $e->getMessage()
                ]));
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->success($this->translator->trans('command.parse_rss.messages.processing_complete', [
            '%success%' => $successCount,
            '%errors%' => $errorCount
        ]));

        $io->info($this->translator->trans('command.parse_rss.messages.items_processed', ['%total%' => $totalItems]));

        return Command::SUCCESS;
    }

    /**
     * @throws ExceptionInterface
     */
    private function processAsync(array $sources, SymfonyStyle $io): int
    {
        $io->info($this->translator->trans('command.parse_rss.messages.async_processing', ['%count%' => count($sources)]));

        foreach ($sources as $source) {
            $this->messageBus->dispatch(new ParseRssMessage($source->getId()));
        }

        $io->success($this->translator->trans('command.parse_rss.messages.parsing_completed'));
        return Command::SUCCESS;
    }
}
