<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\DeferredPackagesServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\NotificationServiceProvider;
use App\Providers\RouteServiceProvider;
use App\Providers\SettingsServiceProvider;
use App\Providers\ViewServiceProvider;

return [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    EventServiceProvider::class,
    RouteServiceProvider::class,
    SettingsServiceProvider::class,
    HorizonServiceProvider::class,
    NotificationServiceProvider::class,
    ViewServiceProvider::class,
    DeferredPackagesServiceProvider::class,
];
