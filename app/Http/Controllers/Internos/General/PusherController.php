<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\Conexion;

use DB;

use App\Services\PusherService;

class PusherController extends Controller
{
    private $pusherService;

    public function __construct(PusherService $pusherService)
    {
        $this->pusherService = $pusherService;
    }

    public function auth(Request $request)
    {
        $socketId = request('socketId');
        $channel = request('channel');

        $auth = $this->pusherService->autehnticate($socketId, $channel);

        return $auth;
    }

}