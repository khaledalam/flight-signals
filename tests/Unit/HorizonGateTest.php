<?php

use Illuminate\Support\Facades\Gate;

it('allows Horizon access in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    expect(Gate::check('viewHorizon'))->toBeTrue();
});

it('denies Horizon access in non-local environment', function () {
    app()->detectEnvironment(fn () => 'production');

    expect(Gate::check('viewHorizon'))->toBeFalse();
});
