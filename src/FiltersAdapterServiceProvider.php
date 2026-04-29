<?php

namespace Reno\FiltersAdapter;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Filters\Services\FiltersPrecacheService;

class FiltersAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Event::listen([
            // event cmscache clear
        ], function () {
            /** @var FiltersPrecacheService $service */
            $service = resolve(FiltersPrecacheService::class);
            $service->dropCache();
        });
    }
}
