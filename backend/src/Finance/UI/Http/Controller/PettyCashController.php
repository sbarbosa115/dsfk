<?php

declare(strict_types=1);

namespace App\Finance\UI\Http\Controller;

use App\Finance\Application\Command\CloseCycle;
use App\Finance\Application\Command\SignOffCycle;
use App\Finance\Application\Query\FinanceQueries;
use App\Finance\UI\Http\Input\CloseCycleInput;
use App\Finance\UI\Http\Output\CycleOutput;
use App\Finance\UI\Http\Output\PettyCashOutput;
use App\Finance\UI\Http\Presenter\PettyCashPresenter;
use App\Shared\Application\Bus\CommandBus;
use App\Shared\Application\Bus\NewId;
use App\Shared\Application\Security\Actor;
use App\Shared\Domain\Error\NotFound;
use App\Shared\UI\Http\ProjectGuard;
use App\Shared\UI\Http\ProjectPermission;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

#[OA\Tag(name: 'Petty cash')]
#[OA\Response(response: 403, description: 'forbidden: Team Leads see no money; only Admins sign off', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
#[OA\Response(response: 404, description: 'cycle_not_found, or project_not_found outside the project', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
final class PettyCashController extends AbstractController
{
    public function __construct(
        private readonly CommandBus $bus,
        private readonly ProjectGuard $guard,
        private readonly PettyCashPresenter $presenter,
        private readonly FinanceQueries $queries,
    ) {
    }

    #[Route('/api/projects/{id}/petty-cash', name: 'api_petty_cash', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'Petty cash', content: new Model(type: PettyCashOutput::class))]
    public function show(int $id): JsonResponse
    {
        $this->guard->require(ProjectPermission::VIEW_FINANCIALS, $id);

        return $this->json($this->presenter->present($id));
    }

    /** A cycle with its movements (e.g. a closed one under review). */
    #[Route('/api/petty-cash-cycles/{id}', name: 'api_petty_cash_cycle', requirements: ['id' => '\d+'], methods: ['GET'])]
    #[OA\Response(response: 200, description: 'The cycle', content: new Model(type: CycleOutput::class))]
    public function cycle(int $id): JsonResponse
    {
        $this->guard->require(ProjectPermission::VIEW_FINANCIALS, $this->project($id));

        return $this->json($this->presenter->detail($id));
    }

    /** The PM (or an Admin) closes the current cycle, usually when the money runs out. */
    #[Route('/api/projects/{id}/petty-cash/close', name: 'api_petty_cash_close', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The closed cycle', content: new Model(type: CycleOutput::class))]
    public function close(int $id, #[MapRequestPayload] CloseCycleInput $input, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::PLAN, $id);
        $cycle = $this->bus->dispatch(new CloseCycle($id, $actor->getId(), $input->note));
        \assert($cycle instanceof NewId);

        return $this->json($this->presenter->detail($cycle->value()));
    }

    #[Route('/api/petty-cash-cycles/{id}/sign-off', name: 'api_petty_cash_sign_off', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[OA\Response(response: 200, description: 'The signed-off cycle', content: new Model(type: CycleOutput::class))]
    #[OA\Response(response: 409, description: 'cycle_not_closed', content: new OA\JsonContent(ref: '#/components/schemas/Error'))]
    public function signOff(int $id, #[CurrentUser] Actor $actor): JsonResponse
    {
        $this->guard->require(ProjectPermission::ADMINISTER, $this->project($id));
        $this->bus->dispatch(new SignOffCycle($id, $actor->getId()));

        return $this->json($this->presenter->detail($id));
    }

    private function project(int $cycleId): int
    {
        return $this->queries->projectOfCycle($cycleId) ?? throw new NotFound('cycle_not_found');
    }
}
