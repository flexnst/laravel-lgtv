<?php


namespace Flexnst\LgTv\Facades;


use Illuminate\Support\Facades\Facade;

class LgTvFacade extends Facade
{
    protected static function getFacadeAccessor() {
        return 'lgtv';
    }
}
