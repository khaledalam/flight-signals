<?php

it('allows Horizon dashboard access', function () {
    $this->get('/horizon')
        ->assertSuccessful();
});
