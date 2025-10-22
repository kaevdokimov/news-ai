<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\NewsItem;
use App\Entity\NewsSource;
use App\Repository\NewsItemRepository;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

readonly class RssParserService
{
    private HttpClientInterface $httpClient;

    public function __construct(
        private EntityManagerInterface $entityManager,
        private NewsItemRepository $newsItemRepository,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
        $this->httpClient = HttpClient::create([
            'timeout' => 30,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (compatible; NewsParser/1.0)',
            ],
        ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws \Exception
     */
    public function parseRssFeed(NewsSource $newsSource): int
    {
        $this->logger->info($this->translator->trans('rss_parser.starting', [
            '%name%' => $newsSource->getName(),
        ]), [
            'source_id' => $newsSource->id,
            'url' => $newsSource->getUrl(),
        ]);

        try {
            if (!\is_string($newsSource->getUrl()) || empty($newsSource->getUrl())) {
                throw new \InvalidArgumentException($this->translator->trans('rss_parser.invalid_url'));
            }

            $response = $this->httpClient->request('GET', $newsSource->getUrl());
            $content = $response->getContent();

            if (empty($content)) {
                throw new \RuntimeException($this->translator->trans('rss_parser.empty_response'));
            }

            $xml = simplexml_load_string($content);

            if ($xml === false) {
                throw new \RuntimeException($this->translator->trans('rss_parser.xml_parse_error'));
            }

            $itemsCount = 0;
            $items = $xml->xpath('//item');

            if (empty($items)) {
                // Попробуем альтернативный путь для некоторых RSS лент
                $items = $xml->xpath('//entry');
            }

            if (is_iterable($items)) {
                foreach ($items as $item) {
                    try {
                        $newsItem = $this->parseRssItem($item, $newsSource);

                        if ($newsItem instanceof NewsItem) {
                            ++$itemsCount;
                        }
                    } catch (\Exception $e) {
                        $this->logger->warning($this->translator->trans('rss_parser.item_parse_error'), [
                            'source_id' => $newsSource->id,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Обновляем время последнего парсинга
            $newsSource->setLastParsedAt(new \DateTime());
            $this->entityManager->flush();

            $this->logger->info($this->translator->trans('rss_parser.parsing_completed'), [
                'source_id' => $newsSource->id,
                'items_processed' => $itemsCount,
                'message' => $this->translator->trans('rss_parser.items_processed', [
                    '%count%' => $itemsCount,
                ]),
            ]);

            return $itemsCount;
        } catch (\Exception $exception) {
            $this->logger->error($this->translator->trans('rss_parser.parsing_failed'), [
                'source_id' => $newsSource->id,
                'url' => $newsSource->getUrl(),
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    private function parseRssItem(\SimpleXMLElement $item, NewsSource $newsSource): ?NewsItem
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
        $existingItem = $this->newsItemRepository->findByGuidAndSource($guid, $newsSource);

        if ($existingItem instanceof NewsItem) {
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
        $newsItem->setNewsSource($newsSource);
        $newsItem->setTitle($title);
        $newsItem->setDescription($description);
        $newsItem->setContent($content);
        $newsItem->setLink($link);
        $newsItem->setImageUrl($imageUrl);
        $newsItem->setGuid($guid);
        $newsItem->setPublishedAt($publishedAt);

        try {
            $this->entityManager->persist($newsItem);
            $this->entityManager->flush();

            return $newsItem;
        } catch (UniqueConstraintViolationException) {
            // Игнорируем ошибку дубликата
            $this->logger->info($this->translator->trans('rss_parser.missing_duplicate_news'), [
                'guid' => $guid,
                'source_id' => $newsSource->id,
                'source_name' => $newsSource->getName(),
            ]);

            return null;
        } catch (\Exception $e) {
            $this->logger->error($this->translator->trans('rss_parser.error_saving_news'), [
                'guid' => $guid,
                'source_id' => $newsSource->id,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    private function getItemValue(\SimpleXMLElement $item, array $fields): ?string
    {
        foreach ($fields as $field) {
            if (isset($item->{$field})) {
                $value = (string) $item->{$field};

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
            if (isset($item->{$field})) {
                $url = (string) $item->{$field}[$attribute];

                if (!empty($url)) {
                    return $url;
                }
            }
        }

        // Ищем изображения в описании
        $description = $this->getItemValue($item, ['description', 'content:encoded', 'content']);

        if ($description && preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $description, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function getItemDate(\SimpleXMLElement $item): \DateTime
    {
        $dateFields = ['pubDate', 'published', 'updated', 'dc:date'];

        foreach ($dateFields as $dateField) {
            if (isset($item->{$dateField})) {
                $dateString = (string) $item->{$dateField};

                if (!empty($dateString)) {
                    try {
                        return new \DateTime($dateString);
                    } catch (\Exception) {
                        // Продолжаем поиск
                    }
                }
            }
        }

        // Если дата не найдена, используем текущее время
        return new \DateTime();
    }
}
