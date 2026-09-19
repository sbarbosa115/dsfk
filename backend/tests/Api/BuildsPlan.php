<?php

namespace App\Tests\Api;

/**
 * Builds a two-stage plan through the API. Expects $admin, $pm and $base on the test case.
 *
 * Budget: Cimentación 1,437,506.25 · Estructura 3,000,000 · contingency 500,000.
 */
trait BuildsPlan
{
    /**
     * Two stages, two categories, lines, milestones summing 100% and a contingency.
     *
     * @return array<string, mixed>
     */
    private function buildPlan(): array
    {
        $plan = $this->request('POST', $this->base.'/categories', ['name' => 'Materiales']);
        $plan = $this->request('POST', $this->base.'/categories', ['name' => 'Nómina']);
        $categories = array_column($plan['categories'], 'id', 'name');

        foreach (['Cimentación' => ['2026-10-01', '2026-12-15'], 'Estructura' => ['2026-12-16', '2027-04-30']] as $name => [$start, $end]) {
            $plan = $this->request('POST', $this->base.'/stages', ['name' => $name, 'plannedStart' => $start, 'plannedEnd' => $end]);
            $this->assertStatus(200);
        }
        [$foundation, $structure] = array_column($plan['stages'], 'id');

        $this->request('POST', "/api/stages/$foundation/lines", $this->line($categories['Materiales'], quantity: '12.5', unitPrice: '35000.50'));
        $this->assertStatus(200);
        $this->request('POST', "/api/stages/$foundation/lines", $this->line($categories['Nómina'], quantity: '1', unitPrice: '1000000'));
        $this->request('POST', "/api/stages/$structure/lines", $this->line($categories['Materiales'], quantity: '100', unitPrice: '30000'));

        $this->request('POST', "/api/stages/$foundation/milestones", ['name' => 'Excavación', 'weight' => '40', 'plannedDate' => '2026-10-20']);
        $this->assertStatus(200);
        $this->request('POST', "/api/stages/$foundation/milestones", ['name' => 'Vaciado de zapatas', 'weight' => '60']);
        $this->request('POST', "/api/stages/$structure/milestones", ['name' => 'Columnas y placas', 'weight' => '100']);

        $plan = $this->request('PUT', $this->base.'/budget/contingency', ['contingency' => '500000']);
        $this->assertStatus(200);

        return $plan;
    }

    private function approve(): void
    {
        $this->loginAs($this->pm);
        $this->request('POST', $this->base.'/budget/submit');
        $this->assertStatus(200);
        $this->loginAs($this->admin);
        $this->request('POST', $this->base.'/budget/approve');
        $this->assertStatus(200);
    }

    /**
     * @return array<string, mixed>
     */
    private function line(int $categoryId, string $quantity = '10', string $unitPrice = '35000'): array
    {
        return ['categoryId' => $categoryId, 'description' => 'Concreto 3000 PSI', 'unit' => 'm³', 'quantity' => $quantity, 'unitPrice' => $unitPrice];
    }
}
