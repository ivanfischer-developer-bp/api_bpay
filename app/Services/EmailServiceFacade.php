<?php

namespace App\Services;

use Illuminate\Support\Facades\Facade;

class EmailServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'email.service';
    }
}
