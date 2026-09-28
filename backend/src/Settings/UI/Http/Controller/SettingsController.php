<?php

declare(strict_types=1);

namespace App\Settings\UI\Http\Controller;

use App\Settings\Application\Command\UpdateSettings;
use App\Settings\Application\Query\SettingsQueries;
use App\Settings\UI\Http\Input\SettingsInput;
use App\Settings\UI\Http\Output\SettingsOutput;
use App\Shared\Application\Bus\CommandBus;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/settings')]
#[OA\Tag(name: 'Settings')]
final class SettingsController extends AbstractController
{
    public function __construct(private readonly SettingsQueries $settings)
    {
    }

    #[Route('', name: 'api_settings_show', methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The global settings', content: new Model(type: SettingsOutput::class))]
    public function show(): JsonResponse
    {
        return $this->json(SettingsOutput::from($this->settings->current()));
    }

    /** Change some settings; the ones not sent stay. Admin only. */
    #[Route('', name: 'api_settings_update', methods: ['PUT'])]
    #[IsGranted('ROLE_ADMIN')]
    #[OA\Response(response: 200, description: 'The global settings', content: new Model(type: SettingsOutput::class))]
    #[OA\Response(response: 403, description: 'Not an admin', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    #[OA\Response(response: 422, description: 'validation_failed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function update(#[MapRequestPayload] SettingsInput $input, CommandBus $bus): JsonResponse
    {
        $bus->dispatch(new UpdateSettings($input->defaultCurrency, $input->teamLeadExpenseLimit, $input->pettyCashLowBalancePercent, $input->budgetWarningPercents));

        return $this->show();
    }
}
