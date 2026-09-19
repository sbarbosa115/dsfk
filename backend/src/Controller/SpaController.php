<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Serves the built React app (public/app/index.html) for every non-API path,
 * so client-side routes survive a page reload.
 */
class SpaController
{
    public function __construct(private readonly string $projectDir)
    {
    }

    #[Route('/{path}', name: 'spa', requirements: ['path' => '^(?!api/).*'], defaults: ['path' => ''], methods: ['GET'], priority: -100)]
    public function __invoke(): Response
    {
        $index = $this->projectDir.'/public/app/index.html';
        if (!is_file($index)) {
            return new Response('Frontend not built. Run the frontend build first.', Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return new Response(file_get_contents($index), headers: ['Cache-Control' => 'no-cache']);
    }
}
