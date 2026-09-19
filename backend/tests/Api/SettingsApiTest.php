<?php

namespace App\Tests\Api;

class SettingsApiTest extends ApiTestCase
{
    public function testDefaults(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $data = $this->request('GET', '/api/settings');

        $this->assertStatus(200);
        self::assertSame([
            'defaultCurrency' => 'COP',
            'teamLeadExpenseLimit' => '500000',
            'pettyCashLowBalancePercent' => 20,
            'budgetWarningPercents' => [80, 100],
        ], $data);
    }

    public function testAdminUpdatesSettingsAndNewProjectsUseTheCurrency(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->request('PUT', '/api/settings', [
            'defaultCurrency' => 'USD',
            'teamLeadExpenseLimit' => '750000',
            'budgetWarningPercents' => [100, 75, 75],
        ]);

        $this->assertStatus(200);
        self::assertSame('USD', $data['defaultCurrency']);
        self::assertSame('750000', $data['teamLeadExpenseLimit']);
        self::assertSame([75, 100], $data['budgetWarningPercents']);
        self::assertSame(20, $data['pettyCashLowBalancePercent']);

        $project = $this->request('POST', '/api/projects', ['name' => 'Nuevo']);
        self::assertSame('USD', $project['currency']);
    }

    public function testInvalidValuesAreRejected(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->request('PUT', '/api/settings', [
            'defaultCurrency' => 'XXZ',
            'teamLeadExpenseLimit' => '-5',
            'pettyCashLowBalancePercent' => 0,
        ]);

        $this->assertStatus(422);
        self::assertEqualsCanonicalizing(
            ['defaultCurrency', 'teamLeadExpenseLimit', 'pettyCashLowBalancePercent'],
            array_keys($data['violations']),
        );
    }

    public function testNonAdminCannotUpdate(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $this->request('PUT', '/api/settings', ['teamLeadExpenseLimit' => '1']);

        $this->assertStatus(403);
    }
}
