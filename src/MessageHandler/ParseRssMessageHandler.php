<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Message\ParseRssMessage;
use App\Repository\NewsSourceRepository;
use App\Service\RssParserService;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class ParseRssMessageHandler
{
    public function __construct(
        private NewsSourceRepository $newsSourceRepository,
        private RssParserService $rssParserService,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {}

    public function __invoke(ParseRssMessage $message): void
    {
        $source = $this->newsSourceRepository->find($message->getSourceId());

        if (!$source) {
            $this->logger->error($this->translator->trans('rss_parser.source_not_found', [
                '%id%' => $message->getSourceId(),
            ]), [
                'source_id' => $message->getSourceId(),
            ]);

            return;
        }

        if (!$source->isActive()) {
            $this->logger->info($this->translator->trans('rss_parser.source_not_active', [
                '%name%' => $source->getName(),
            ]), [
                'source_id' => $source->getId(),
                'source_name' => $source->getName(),
            ]);

            return;
        }

        try {
            $itemsCount = $this->rssParserService->parseRssFeed($source);

            $this->logger->info($this->translator->trans('rss_parser.parsing_completed'), [
                'source_id' => $source->getId(),
                'source_name' => $source->getName(),
                'items_count' => $itemsCount,
            ]);
        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('rss_parser.parsing_failed'), [
                'source_id' => $source->getId(),
                'source_name' => $source->getName(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
