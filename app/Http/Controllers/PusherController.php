<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response as FacadeResponse;

use App\Http\Controllers\ConexionSpController;
use App\Models\User;
use App\Exports\InformeExport;

use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;
use File;
use Storage;

class PusherController extends ConexionSpController
{
    public function auth(Request $request){
        return true;
    }
}