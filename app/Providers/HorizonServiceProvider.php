<?php

namespace App\Providers;

use App\Http\Middleware\BasicAuthAdmin;
use Illuminate\Http\Request;
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
        Horizon::auth(fn (Request $request) => BasicAuthAdmin::check($request));
    }
}
