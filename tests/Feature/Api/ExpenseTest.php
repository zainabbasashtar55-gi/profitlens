<?php

declare(strict_types=1);

use App\Models\Expense;
use App\Models\ExpenseCategory;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = createTenant();
    $this->owner  = createTenantUser($this->tenant, 'owner', 'owner@acme.test');
});

it('creates an expense and attaches a receipt file to tenant storage', function () {
    Storage::fake(config('filesystems.receipts_disk'));

    $category = $this->tenant->run(fn () => ExpenseCategory::firstOrCreate(['name' => 'Software'], ['color' => '#000']));
    $token = loginAsApi($this->tenant, $this->owner);

    $response = $this->withToken($token)
        ->post(tenantUrl('/api/v1/expenses'), [
            'expense_category_id' => $category->id,
            'description'  => 'Cursor subscription',
            'vendor'       => 'Anysphere',
            'amount'       => 20.00,
            'expense_date' => '2026-05-15',
            'receipt'      => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json']);

    $response->assertStatus(201)
        ->assertJsonPath('data.description', 'Cursor subscription');

    expect((float) $response->json('data.amount'))->toEqual(20.0);

    $this->tenant->run(function () {
        $expense = Expense::first();
        expect($expense->receipt_path)->not->toBeNull()
            ->and($expense->receipt_original_name)->toBe('receipt.pdf');
        Storage::disk(config('filesystems.receipts_disk'))->assertExists($expense->receipt_path);
    });
});

it('rejects an expense without amount or description', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->postJson(tenantUrl('/api/v1/expenses'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['description', 'amount', 'expense_date']);
});

it('rejects a receipt over 5MB or wrong mime', function () {
    $token = loginAsApi($this->tenant, $this->owner);

    $this->withToken($token)
        ->post(tenantUrl('/api/v1/expenses'), [
            'description'  => 'Test',
            'amount'       => 10,
            'expense_date' => '2026-05-15',
            'receipt'      => UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream'),
        ], ['Accept' => 'application/json'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('receipt');
});
