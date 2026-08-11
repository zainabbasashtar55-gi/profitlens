<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ImportedTransaction;
use App\Models\IntegrationConnection;
use App\Services\Integrations\TransactionMatcher;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PlaidController extends Controller
{
    public function linkToken(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        if (! config('services.plaid.client_id') || ! config('services.plaid.secret')) {
            return response()->json([
                'configured' => false,
                'message' => 'Set PLAID_CLIENT_ID and PLAID_SECRET to enable live Plaid link tokens.',
            ], 503);
        }

        $response = Http::post($this->plaidUrl('/link/token/create'), [
            'client_id' => config('services.plaid.client_id'),
            'secret' => config('services.plaid.secret'),
            'client_name' => 'ProfitLens',
            'country_codes' => config('services.plaid.country_codes'),
            'language' => 'en',
            'user' => ['client_user_id' => (string) $request->user()->id],
            'products' => config('services.plaid.products'),
        ]);

        return response()->json($response->json(), $response->status());
    }

    public function exchangeToken(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $data = $request->validate([
            'public_token' => ['required', 'string'],
            'institution_name' => ['nullable', 'string', 'max:120'],
        ]);

        if (! config('services.plaid.client_id') || ! config('services.plaid.secret')) {
            return response()->json(['message' => 'Plaid is not configured.'], 503);
        }

        $response = Http::post($this->plaidUrl('/item/public_token/exchange'), [
            'client_id' => config('services.plaid.client_id'),
            'secret' => config('services.plaid.secret'),
            'public_token' => $data['public_token'],
        ]);

        if ($response->failed()) {
            return response()->json($response->json(), $response->status());
        }

        $body = $response->json();
        $connection = IntegrationConnection::firstOrNew([
            'provider' => 'plaid',
            'external_id' => $body['item_id'],
        ]);
        $connection->fill([
            'name' => $data['institution_name'] ?? 'Plaid bank account',
            'settings' => ['item_id' => $body['item_id']],
        ]);
        $connection->setPlainAccessToken($body['access_token']);
        $connection->save();

        return response()->json(['data' => $connection->only(['id', 'provider', 'name', 'external_id'])], 201);
    }

    public function import(Request $request, TransactionMatcher $matcher): JsonResponse
    {
        abort_unless($request->user()->hasAnyRole(['owner', 'admin']), 403);

        $data = $request->validate([
            'connection_id' => ['nullable', 'exists:integration_connections,id'],
            'transactions' => ['required', 'array', 'min:1'],
            'transactions.*.id' => ['required', 'string', 'max:120'],
            'transactions.*.amount' => ['required', 'numeric'],
            'transactions.*.date' => ['required', 'date'],
            'transactions.*.name' => ['required', 'string', 'max:255'],
            'transactions.*.merchant_name' => ['nullable', 'string', 'max:255'],
        ]);

        $imported = collect($data['transactions'])->map(function (array $row) use ($data, $matcher) {
            $amount = (float) $row['amount'];
            $transaction = ImportedTransaction::updateOrCreate(
                ['provider' => 'plaid', 'external_id' => $row['id']],
                [
                    'integration_connection_id' => $data['connection_id'] ?? null,
                    'kind' => $amount < 0 ? 'income' : 'expense',
                    'amount' => abs($amount),
                    'transaction_date' => $row['date'],
                    'name' => $row['name'],
                    'merchant_name' => $row['merchant_name'] ?? null,
                    'raw_payload' => $row,
                ]
            );

            return $matcher->match($transaction)->fresh();
        });

        return response()->json(['data' => $imported]);
    }

    private function plaidUrl(string $path): string
    {
        $env = config('services.plaid.environment', 'sandbox');
        $host = $env === 'production' ? 'https://production.plaid.com' : 'https://sandbox.plaid.com';

        return $host . $path;
    }
}
