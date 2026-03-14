<?php

namespace App\Providers;

use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();
    }

    protected function authorization(): void
    {
        Horizon::auth(function ($request) {
            if (app()->environment('local')) {
                return true;
            }

            $user = $request->getUser();
            $pass = (string) $request->getPassword();
            $expectedUser = config('services.horizon.username');
            $expectedPass = config('services.horizon.password');

            return $expectedUser && $expectedPass
                && $user === $expectedUser
                && hash_equals($expectedPass, $pass);
        });
    }
}
