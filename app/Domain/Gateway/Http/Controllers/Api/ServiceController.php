<?php

namespace App\Domain\Gateway\Http\Controllers\Api;

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    /**
     * Create the controller.
     */
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    /**
     * List the activated platform services, enriched with the registry
     * metadata. Disabled modules never appear in the response.
     */
    public function index(): JsonResponse
    {
        $enabled = Module::query()
            ->where('enabled', true)
            ->pluck('slug')
            ->all();

        $services = collect($this->registry->all())
            ->filter(fn (ModuleDefinition $module): bool => in_array($module->slug, $enabled, true))
            ->map(fn (ModuleDefinition $module): array => [
                'slug' => $module->slug,
                'label' => $module->label,
                'description' => $module->description,
                'enabled' => true,
            ])
            ->values()
            ->all();

        return response()->json(['data' => $services]);
    }
}
