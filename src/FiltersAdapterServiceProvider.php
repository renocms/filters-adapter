<?php

namespace Reno\FiltersAdapter;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Filters\Services\FiltersPrecacheService;
use Reno\Cms\Events\Resources\CmsCacheFlushed;

class FiltersAdapterServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        Event::listen(CmsCacheFlushed::class, function () {
            /** @var FiltersPrecacheService $service */
            $service = resolve(FiltersPrecacheService::class);
            $service->dropCache();
        });
    }
}
