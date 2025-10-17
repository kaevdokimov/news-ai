<?php

namespace App\Command;

use App\Entity\NewsSource;
use App\Repository\NewsSourceRepository;
use App\Service\RssParserService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsCommand(
    name: 'app:parse-rss',
    description: 'Parse RSS feeds from news sources',
)]
class ParseRssCommand extends Command
{
    public function __construct(
        private NewsSourceRepository $newsSourceRepository,
        private RssParserService $rssParserService,
        private MessageBusInterface $messageBus
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('source-id', null, InputOption::VALUE_OPTIONAL, 'Parse specific source by ID')
            ->addOption('async', null, InputOption::VALUE_NONE, 'Process sources asynchronously using message queue')
            ->setHelp('This command parses RSS feeds from configured news sources.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $sourceId = $input->getOption('source-id');
        $async = $input->getOption('async');

        if ($sourceId) {
            $source = $this->newsSourceRepository->find($sourceId);
            if (!$source) {
                $io->error(sprintf('Source with ID %d not found', $sourceId));
                return Command::FAILURE;
            }
            $sources = [$source];
        } else {
            $sources = $this->newsSourceRepository->findActiveSources();
        }

        if (empty($sources)) {
            $io->warning('No active news sources found');
            return Command::SUCCESS;
        }

        $io->title('RSS Feed Parser');
        $io->info(sprintf('Found %d active news source(s)', count($sources)));

        if ($async) {
            return $this->processAsync($sources, $io);
        } else {
            return $this->processSync($sources, $io);
        }
    }

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
                $io->success(sprintf(
                    'Source "%s": %d new items parsed',
                    $source->getName(),
                    $itemsCount
                ));
            } catch (\Exception $e) {
                $errorCount++;
                $io->newLine();
                $io->error(sprintf(
                    'Source "%s": %s',
                    $source->getName(),
                    $e->getMessage()
                ));
            }
            
            $progressBar->advance();
        }

        $progressBar->finish();
        $io->newLine(2);

        $io->section('Summary');
        $io->table(
            ['Metric', 'Count'],
            [
                ['Total Sources', count($sources)],
                ['Successful', $successCount],
                ['Failed', $errorCount],
                ['New Items', $totalItems],
            ]
        );

        return $errorCount > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private function processAsync(array $sources, SymfonyStyle $io): int
    {
        $io->info('Processing sources asynchronously...');

        foreach ($sources as $source) {
            $this->messageBus->dispatch(new \App\Message\ParseRssMessage($source->getId()));
            $io->text(sprintf('Queued source: %s', $source->getName()));
        }

        $io->success(sprintf('Queued %d sources for processing', count($sources)));
        $io->note('Run "php bin/console messenger:consume async" to process the queue');

        return Command::SUCCESS;
    }
}
