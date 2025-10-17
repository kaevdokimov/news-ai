<?php

namespace App\DataFixtures;

use App\Entity\NewsSource;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $sources = [
            [
                'name' => 'Лента.ру',
                'url' => 'https://lenta.ru/rss/google-newsstand/main/',
                'description' => 'Главные новости от Лента.ру',
            ],
            [
                'name' => 'РИА Новости',
                'url' => 'https://ria.ru/export/rss2/index.xml?page_type=google_newsstand',
                'description' => 'Новости от РИА Новости',
            ],
            [
                'name' => 'РБК',
                'url' => 'https://rssexport.rbc.ru/rbcnews/news/30/full.rss',
                'description' => 'Новости от РБК',
            ],
            [
                'name' => 'ТАСС',
                'url' => 'https://tass.ru/rss/v2.xml',
                'description' => 'Новости от ТАСС',
            ],
            [
                'name' => 'Правительство РФ',
                'url' => 'http://government.ru/all/rss/',
                'description' => 'Новости от Правительства РФ',
            ],
        ];

        foreach ($sources as $sourceData) {
            $source = new NewsSource();
            $source->setName($sourceData['name']);
            $source->setUrl($sourceData['url']);
            $source->setDescription($sourceData['description']);
            $source->setIsActive(true);
            
            $manager->persist($source);
        }

        $manager->flush();
    }
}
