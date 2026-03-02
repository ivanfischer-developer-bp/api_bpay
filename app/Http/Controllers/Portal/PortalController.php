<?php

namespace App\Http\Controllers\Portal;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

use App\Models\User;
use App\Models\ProfileDoctor;
use App\Models\ProfileSecretary;

use Carbon\Carbon;
use DB;

use App\Http\Controllers\ConexionSpController;

class PortalController extends ConexionSpController
{
    
}