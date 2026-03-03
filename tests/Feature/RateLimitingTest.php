<?php

use function Tests\apiHeaders;

it('returns 429 after exceeding the rate limit', function () {
    for ($i = 0; $i < 60; $i++) {
        $this->getJson('/api/flights/fake-id', apiHeaders());
    }

    $this->getJson('/api/flights/fake-id', apiHeaders())
        ->assertStatus(429);
});
