<?php

it('allows Horizon dashboard access in local environment', function () {
    app()->detectEnvironment(fn () => 'local');

    $this->get('/horizon')
        ->assertSuccessful();
});

it('blocks Horizon dashboard access in production without credentials', function () {
    app()->detectEnvironment(fn () => 'production');

    $this->get('/horizon')
        ->assertStatus(403);
});
