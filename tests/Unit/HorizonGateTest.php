<?php

it('allows Horizon dashboard access with valid credentials', function () {
    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode(
            config('services.horizon.username').':'.config('services.horizon.password')
        ),
    ])->get('/horizon')->assertSuccessful();
});

it('blocks Horizon dashboard access without credentials', function () {
    $this->get('/horizon')->assertStatus(401);
});

it('blocks Horizon dashboard access with wrong credentials', function () {
    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('wrong:wrong'),
    ])->get('/horizon')->assertStatus(401);
});

it('blocks Horizon access when credentials are not configured', function () {
    config(['services.horizon.username' => null]);
    config(['services.horizon.password' => null]);

    $this->withHeaders([
        'Authorization' => 'Basic '.base64_encode('admin:admin'),
    ])->get('/horizon')->assertStatus(401);
});
