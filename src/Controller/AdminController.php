<?php

namespace App\Controller;

use App\Entity\NewsSource;
use App\Entity\NewsItem;
use App\Repository\NewsSourceRepository;
use App\Repository\NewsItemRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(
        NewsSourceRepository $newsSourceRepository,
        NewsItemRepository $newsItemRepository
    ): Response {
        $sources = $newsSourceRepository->findAll();
        $newsCount = $newsItemRepository->count([]);
        $recentNews = $newsItemRepository->findLatestNews(10);

        return $this->render('admin/dashboard.html.twig', [
            'sources' => $sources,
            'newsCount' => $newsCount,
            'recentNews' => $recentNews,
        ]);
    }

    #[Route('/admin/sources', name: 'admin_sources')]
    public function sources(NewsSourceRepository $newsSourceRepository): Response
    {
        $sources = $newsSourceRepository->findAll();

        return $this->render('admin/sources.html.twig', [
            'sources' => $sources,
        ]);
    }

    #[Route('/admin/news', name: 'admin_news')]
    public function news(NewsItemRepository $newsItemRepository): Response
    {
        $news = $newsItemRepository->findLatestNews(50);

        return $this->render('admin/news.html.twig', [
            'news' => $news,
        ]);
    }
}
