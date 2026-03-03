<?php

arch('controllers are thin and invokable-friendly')
    ->expect('App\Http\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB']);

arch('models do not depend on HTTP layer')
    ->expect('App\Models')
    ->not->toUse(['Illuminate\Http\Request']);

arch('jobs implement ShouldQueue')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('services stay decoupled from HTTP')
    ->expect('App\Services')
    ->not->toUse([
        'Illuminate\Http\Request',
        'Illuminate\Http\JsonResponse',
    ]);
