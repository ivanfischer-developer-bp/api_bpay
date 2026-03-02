<?php

namespace App\Services;

use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Log;
use Pusher\ApiErrorException;
use Pusher\Pusher;
use Pusher\PusherException;

class PusherService
{
    private $client;

    public function __construct(){

        try {
            $this->client = new Pusher(env('PUSHER_APP_KEY'), env('PUSHER_APP_SECRET'), env('PUSHER_APP_ID'), array('cluster' => env('PUSHER_APP_CLUSTER', 'us2')));
        } catch (PusherException $e) {
            Log::warning("Error al instanciar pusher: " . $e->getMessage());
        }
    }

    public function triggerNotification($channel, $event, $msg) {

        try {
            $this->client->trigger($channel, $event, ['data' => $msg]);
        } catch (PusherException $e) {
            Log::warning("Error al desencadenar evento pusher: " . $e->getMessage());
        } catch (GuzzleException $e) {
            Log::warning("Error al desencadenar evento guzzle: " . $e->getMessage());
        }
    }
}