<?php

declare(strict_types=1);

namespace App\Tests\Functional\Api;

use App\Tests\Functional\ApiTestCase;

final class SettingsApiTest extends ApiTestCase
{
    public function testEveryUserReadsTheDefaults(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $data = $this->json('GET', '/api/settings');

        $this->assertStatus(200);
        self::assertSame([
            'defaultCurrency' => 'COP',
            'teamLeadExpenseLimit' => '500000',
            'pettyCashLowBalancePercent' => 20,
            'budgetWarningPercents' => [80, 100],
        ], $data);
    }

    public function testAnAdminChangesSomeSettingsAndTheRestStay(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->json('PUT', '/api/settings', [
            'defaultCurrency' => 'USD',
            'teamLeadExpenseLimit' => '750000',
            'budgetWarningPercents' => [100, 75, 75],
        ]);

        $this->assertStatus(200);
        self::assertSame(['defaultCurrency' => 'USD', 'teamLeadExpenseLimit' => '750000', 'pettyCashLowBalancePercent' => 20, 'budgetWarningPercents' => [75, 100]], $data);
        self::assertSame($data, $this->json('GET', '/api/settings'));
    }

    public function testInvalidValuesAreRefusedFieldByField(): void
    {
        $this->loginAs($this->createUser('admin@example.com', admin: true));

        $data = $this->json('PUT', '/api/settings', [
            'defaultCurrency' => 'XXZ',
            'teamLeadExpenseLimit' => '-5',
            'pettyCashLowBalancePercent' => 0,
            'budgetWarningPercents' => [],
        ]);

        $this->assertStatus(422);
        self::assertEqualsCanonicalizing(['defaultCurrency', 'teamLeadExpenseLimit', 'pettyCashLowBalancePercent', 'budgetWarningPercents'], array_keys($data['violations']));
    }

    public function testOnlyAnAdminChangesThem(): void
    {
        $this->loginAs($this->createUser('pm@example.com'));

        $this->request('PUT', '/api/settings', ['teamLeadExpenseLimit' => '1']);

        $this->assertStatus(403);
    }
}
