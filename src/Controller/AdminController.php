<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\NewsItemRepository;
use App\Repository\NewsSourceRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{
    public function __construct(private readonly NewsSourceRepository $newsSourceRepository, private readonly NewsItemRepository $newsItemRepository) {}

    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $sources = $this->newsSourceRepository->findAll();
        $newsCount = $this->newsItemRepository->count([]);
        $recentNews = $this->newsItemRepository->findLatestNews(10);

        return $this->render('admin/dashboard.html.twig', [
            'sources' => $sources,
            'newsCount' => $newsCount,
            'recentNews' => $recentNews,
        ]);
    }

    #[Route('/admin/sources', name: 'admin_sources')]
    public function sources(): Response
    {
        $sources = $this->newsSourceRepository->findAll();

        return $this->render('admin/sources.html.twig', [
            'sources' => $sources,
        ]);
    }

    #[Route('/admin/news', name: 'admin_news')]
    public function news(): Response
    {
        $news = $this->newsItemRepository->findLatestNews(50);

        return $this->render('admin/news.html.twig', [
            'news' => $news,
        ]);
    }
}
