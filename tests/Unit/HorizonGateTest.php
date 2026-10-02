<?php

it('requires admin credentials for the Horizon dashboard', function () {
    $this->get('/horizon')
        ->assertStatus(401)
        ->assertHeader('WWW-Authenticate');
});

it('rejects wrong credentials for the Horizon dashboard', function () {
    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('admin:admin'),
    ])->get('/horizon')
        ->assertStatus(401);
});

it('allows Horizon dashboard access with admin credentials', function () {
    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('test-admin:test-admin-password'),
    ])->get('/horizon')
        ->assertSuccessful();
});

it('denies the Horizon gate without admin credentials even if the middleware is bypassed', function () {
    expect(\Laravel\Horizon\Horizon::check(request()))->toBeFalse();
});
