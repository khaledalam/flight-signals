<?php

use Illuminate\Support\Str;

use function Tests\apiHeaders;

it('returns 429 after exceeding the rate limit', function () {
    $url = '/api/flights/'.Str::uuid();

    for ($i = 0; $i < 60; $i++) {
        $this->getJson($url, apiHeaders());
    }

    $this->getJson($url, apiHeaders())
        ->assertStatus(429);
});
