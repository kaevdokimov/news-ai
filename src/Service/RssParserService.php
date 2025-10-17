<?php

namespace App\Service;

use App\Entity\NewsItem;
use App\Entity\NewsSource;
use App\Repository\NewsItemRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RssParserService
{
    private HttpClientInterface $httpClient;
    private EntityManagerInterface $entityManager;
    private NewsItemRepository $newsItemRepository;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $entityManager,
        NewsItemRepository $newsItemRepository,
        LoggerInterface $logger
    ) {
        $this->httpClient = HttpClient::create([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; NewsParser/1.0)',
            ],
        ]);
        $this->entityManager = $entityManager;
        $this->newsItemRepository = $newsItemRepository;
        $this->logger = $logger;
    }

    public function parseRssFeed(NewsSource $source): int
    {
        $this->logger->info('Starting RSS parsing for source: ' . $source->getName(), [
            'source_id' => $source->getId(),
            'url' => $source->getUrl(),
        ]);

        try {
            $response = $this->httpClient->request('GET', $source->getUrl());
            $content = $response->getContent();
            
            if (empty($content)) {
                throw new \Exception('Empty response from RSS feed');
            }

            $xml = simplexml_load_string($content);
            if ($xml === false) {
                throw new \Exception('Failed to parse XML content');
            }

            $itemsCount = 0;
            $items = $xml->xpath('//item');

            if (empty($items)) {
                // Попробуем альтернативный путь для некоторых RSS лент
                $items = $xml->xpath('//entry');
            }

            foreach ($items as $item) {
                try {
                    $newsItem = $this->parseRssItem($item, $source);
                    if ($newsItem) {
                        $itemsCount++;
                    }
                } catch (\Exception $e) {
                    $this->logger->warning('Failed to parse RSS item', [
                        'source_id' => $source->getId(),
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // Обновляем время последнего парсинга
            $source->setLastParsedAt(new \DateTime());
            $this->entityManager->flush();

            $this->logger->info('RSS parsing completed', [
                'source_id' => $source->getId(),
                'items_processed' => $itemsCount,
            ]);

            return $itemsCount;

        } catch (\Exception $e) {
            $this->logger->error('RSS parsing failed', [
                'source_id' => $source->getId(),
                'url' => $source->getUrl(),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    private function parseRssItem(\SimpleXMLElement $item, NewsSource $source): ?NewsItem
    {
        // Получаем GUID (уникальный идентификатор)
        $guid = $this->getItemValue($item, ['guid', 'id']);
        if (empty($guid)) {
            // Если нет GUID, используем ссылку
            $guid = $this->getItemValue($item, ['link', 'href']);
        }

        if (empty($guid)) {
            return null;
        }

        // Проверяем, не существует ли уже такая новость
        $existingItem = $this->newsItemRepository->findByGuidAndSource($guid, $source);
        if ($existingItem) {
            return null; // Новость уже существует
        }

        // Получаем заголовок
        $title = $this->getItemValue($item, ['title']);
        if (empty($title)) {
            return null;
        }

        // Получаем описание
        $description = $this->getItemValue($item, ['description', 'summary']);
        
        // Получаем контент
        $content = $this->getItemValue($item, ['content:encoded', 'content', 'description']);
        
        // Получаем ссылку
        $link = $this->getItemValue($item, ['link', 'href']);
        
        // Получаем изображение
        $imageUrl = $this->getItemImage($item);
        
        // Получаем дату публикации
        $publishedAt = $this->getItemDate($item);

        $newsItem = new NewsItem();
        $newsItem->setSource($source);
        $newsItem->setTitle($title);
        $newsItem->setDescription($description);
        $newsItem->setContent($content);
        $newsItem->setLink($link);
        $newsItem->setImageUrl($imageUrl);
        $newsItem->setGuid($guid);
        $newsItem->setPublishedAt($publishedAt);

        $this->entityManager->persist($newsItem);

        return $newsItem;
    }

    private function getItemValue(\SimpleXMLElement $item, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (isset($item->$field)) {
                $value = (string) $item->$field;
                if (!empty($value)) {
                    return trim($value);
                }
            }
        }

        // Попробуем получить значение через атрибуты
        foreach ($fields as $field) {
            if (isset($item[$field])) {
                $value = (string) $item[$field];
                if (!empty($value)) {
                    return trim($value);
                }
            }
        }

        return null;
    }

    private function getItemImage(\SimpleXMLElement $item): ?string
    {
        // Ищем изображение в различных полях
        $imageFields = [
            'enclosure' => 'url',
            'media:content' => 'url',
            'media:thumbnail' => 'url',
            'image' => 'url',
        ];

        foreach ($imageFields as $field => $attribute) {
            if (isset($item->$field)) {
                $url = (string) $item->$field[$attribute];
                if (!empty($url)) {
                    return $url;
                }
            }
        }

        // Ищем изображения в описании
        $description = $this->getItemValue($item, ['description', 'content:encoded', 'content']);
        if ($description) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $description, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function getItemDate(\SimpleXMLElement $item): \DateTime
    {
        $dateFields = ['pubDate', 'published', 'updated', 'dc:date'];
        
        foreach ($dateFields as $field) {
            if (isset($item->$field)) {
                $dateString = (string) $item->$field;
                if (!empty($dateString)) {
                    try {
                        $date = new \DateTime($dateString);
                        return $date;
                    } catch (\Exception $e) {
                        // Продолжаем поиск
                    }
                }
            }
        }

        // Если дата не найдена, используем текущее время
        return new \DateTime();
    }
}
