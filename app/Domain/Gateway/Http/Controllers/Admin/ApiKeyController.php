<?php

namespace App\Domain\Gateway\Http\Controllers\Admin;

use App\Domain\Gateway\Actions\CreateApiKey;
use App\Domain\Gateway\Actions\RevokeApiKey;
use App\Domain\Gateway\Http\Requests\CreateApiKeyRequest;
use App\Domain\Gateway\Models\ApiKey;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Display the API keys list. Revoked keys stay listed for history;
     * secrets never are: only their displayable prefix is shown.
     */
    public function index(): Response
    {
        $keys = ApiKey::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (ApiKey $apiKey): array => [
                'id' => $apiKey->id,
                'name' => $apiKey->name,
                'prefix' => $apiKey->prefix,
                'abilities' => $apiKey->abilities,
                'lastUsedAt' => $apiKey->last_used_at?->toISOString(),
                'revokedAt' => $apiKey->revoked_at?->toISOString(),
                'expiresAt' => $apiKey->expires_at?->toISOString(),
                'createdAt' => $apiKey->created_at->toISOString(),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Gateway/Keys', ['keys' => $keys]);
    }

    /**
     * Create an API key. The plaintext secret is handed back once, as
     * flashed session data, and can never be displayed again.
     */
    public function store(CreateApiKeyRequest $request, CreateApiKey $action): RedirectResponse
    {
        $created = $action->handle(...$request->validated());

        return redirect()
            ->route('admin.gateway.keys.index')
            ->with('secret', $created->secret);
    }

    /**
     * Revoke an API key. Idempotent; the key stays in the list, flagged.
     */
    public function revoke(ApiKey $apiKey, RevokeApiKey $action): RedirectResponse
    {
        $action->handle($apiKey);

        return redirect()->route('admin.gateway.keys.index');
    }
}
