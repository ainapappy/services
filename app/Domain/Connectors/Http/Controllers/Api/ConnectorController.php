<?php

namespace App\Domain\Connectors\Http\Controllers\Api;

use App\Domain\Connectors\Models\Connector;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ConnectorController extends Controller
{
    /**
     * List the configured connectors for API consumers. The base URL is
     * not a secret and is exposed; the credentials never are: the payload
     * is mapped field by field and the model hides the encrypted attribute
     * from every serialization.
     */
    public function index(): JsonResponse
    {
        $connectors = Connector::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Connector $connector): array => [
                'name' => $connector->name,
                'type' => $connector->type,
                'status' => $connector->status,
                'baseUrl' => $connector->base_url,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $connectors]);
    }
}
