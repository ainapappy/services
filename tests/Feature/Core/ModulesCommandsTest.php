<?php

use App\Core\Modules\ModuleDefinition;
use App\Core\Modules\ModuleRegistry;
use App\Models\Module;
use Database\Seeders\ModuleSeeder;
use Illuminate\Support\Facades\Artisan;

test('the registry declares the six platform modules', function () {
    $slugs = collect((new ModuleRegistry)->all())
        ->map(fn (ModuleDefinition $definition): string => $definition->slug)
        ->all();

    expect($slugs)->toBe(['landing', 'pricing', 'gateway', 'connectors', 'webhooks', 'monitoring']);
});

test('modules:enable persists the enabled state in the database', function () {
    $this->artisan('modules:enable', ['slug' => 'gateway'])->assertSuccessful();

    expect(Module::query()->where('slug', 'gateway')->value('enabled'))->toBeTrue();
});

test('modules:disable persists the disabled state in the database', function () {
    Module::query()->create(['slug' => 'gateway', 'enabled' => true]);

    $this->artisan('modules:disable', ['slug' => 'gateway'])->assertSuccessful();

    expect(Module::query()->where('slug', 'gateway')->value('enabled'))->toBeFalse();
});

test('modules:enable and modules:disable reject slugs missing from the registry', function () {
    $this->artisan('modules:enable', ['slug' => 'unknown'])->assertFailed();
    $this->artisan('modules:disable', ['slug' => 'unknown'])->assertFailed();

    expect(Module::query()->count())->toBe(0);
});

test('modules:list renders every registry module with its state', function () {
    $this->seed(ModuleSeeder::class);
    $this->artisan('modules:enable', ['slug' => 'pricing'])->assertSuccessful();

    Artisan::call('modules:list');
    $output = Artisan::output();

    expect($output)
        ->toContain('landing')
        ->toContain('pricing')
        ->toContain('gateway')
        ->toContain('connectors')
        ->toContain('webhooks')
        ->toContain('monitoring')
        ->toContain('enabled')
        ->toContain('disabled');
});
