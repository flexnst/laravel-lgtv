<?php

namespace Flexnst\LgTv;

use Flexnst\LgTv\Service\LgTv;
use Illuminate\Support\ServiceProvider;

class LgTvServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('lgtv', function(){
            return new LgTv();
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([ Commands\LgTv::class ]);
        }

        $this->publishes([
            __DIR__.'/../config/lgtv.php' => config_path('lgtv.php'),
        ], 'config');
    }
}
