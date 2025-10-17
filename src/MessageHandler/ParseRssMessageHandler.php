<?php

namespace App\MessageHandler;

use App\Entity\NewsSource;
use App\Message\ParseRssMessage;
use App\Repository\NewsSourceRepository;
use App\Service\RssParserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
class ParseRssMessageHandler
{
    public function __construct(
        private NewsSourceRepository $newsSourceRepository,
        private RssParserService $rssParserService,
        private LoggerInterface $logger
    ) {
    }

    public function __invoke(ParseRssMessage $message): void
    {
        $source = $this->newsSourceRepository->find($message->getSourceId());
        
        if (!$source) {
            $this->logger->error('News source not found', [
                'source_id' => $message->getSourceId(),
            ]);
            return;
        }

        if (!$source->isActive()) {
            $this->logger->info('Skipping inactive news source', [
                'source_id' => $source->getId(),
                'source_name' => $source->getName(),
            ]);
            return;
        }

        try {
            $itemsCount = $this->rssParserService->parseRssFeed($source);
            
            $this->logger->info('RSS parsing completed successfully', [
                'source_id' => $source->getId(),
                'source_name' => $source->getName(),
                'items_count' => $itemsCount,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('RSS parsing failed', [
                'source_id' => $source->getId(),
                'source_name' => $source->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
