<?php

namespace App\Models;

use Illuminate\Http\Response;

class CustomResponse {
    public $status = null;
    public $count = null;
    public $error = null;
    public $message = null;
    public $line = null;
    public $code = null;
    public $data = null;
    public $params = null;
    public $logged_user = null;
    public $extras = [];
    public $url = null;
    public $controller = null;
    public $funcion = null;
    public $sps = [];

    public function constructor(){

    }

    public function get_response() {
        $this->extras['api_software_version'] = config('site.software_version');
        return response()->json([
                'status' => $this->status,
                'count' => $this->count,
                'errors' => $this->error,
                'message' => $this->message,
                'line' => $this->line,
                'code' => $this->code,
                'data' => $this->data,
                'params' => $this->params,
                'logged_user' => $this->logged_user,
                'extras' => $this->extras,
        ]);
    }

}