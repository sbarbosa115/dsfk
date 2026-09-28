<?php

declare(strict_types=1);

namespace App\Shared\UI\Http;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the built React app (public/app/index.html) for every non-API path, so client-side routes survive a
 * page reload.
 */
final readonly class SpaController
{
    public function __construct(#[Autowire('%kernel.project_dir%')] private string $projectDir)
    {
    }

    #[Route('/{path}', name: 'spa', requirements: ['path' => '^(?!api/|_).*'], defaults: ['path' => ''], methods: ['GET'], priority: -100)]
    public function __invoke(): Response
    {
        $index = $this->projectDir.'/public/app/index.html';
        if (!is_file($index)) {
            return new Response('Frontend not built. Run the frontend build first.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response((string) file_get_contents($index), headers: ['Cache-Control' => 'no-cache']);
    }
}
