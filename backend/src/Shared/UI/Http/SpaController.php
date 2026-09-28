<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the React app (templates/spa.html.twig, mounted by Symfony UX React) for every non-API path, so client
 * routes survive a reload. The assets come from public/build (Webpack Encore).
 */
final class SpaController extends AbstractController
{
    #[Route('/{path}', name: 'spa', requirements: ['path' => '^(?!api/|_|build/).*'], defaults: ['path' => ''], methods: ['GET'], priority: -100)]
    public function __invoke(): Response
    {
        $response = $this->render('spa.html.twig');
        $response->headers->set('Cache-Control', 'no-cache');

        return $response;
    }
}
