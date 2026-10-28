<?php

namespace App\Http\Controllers\Admin;

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Core\Modules\SetModuleState;
use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ModuleController extends Controller
{
    /**
     * Create the controller.
     */
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    /**
     * Display the module management screen.
     */
    public function index(): Response
    {
        $states = Module::query()->pluck('enabled', 'slug');

        $modules = collect($this->registry->all())
            ->map(fn (ModuleDefinition $definition): array => [
                'slug' => $definition->slug,
                'label' => $definition->label,
                'description' => $definition->description,
                'enabled' => (bool) $states->get($definition->slug, false),
            ])
            ->values()
            ->all();

        return Inertia::render('back/Modules', ['modules' => $modules]);
    }

    /**
     * Toggle the activation state of a module.
     */
    public function toggle(string $slug, SetModuleState $action): RedirectResponse
    {
        $definition = $this->registry->find($slug);

        if ($definition === null) {
            abort(404);
        }

        $action->handle($slug, ! Module::isEnabled($slug));

        return redirect()->route('admin.modules.index');
    }
}
