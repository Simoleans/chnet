<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TestApiController extends Controller
{
    public function testApi(Request $request)
    {
        $client = $request->client;

        $clientGUID = "5a31b188-0a0b-4d69-a8fc-463733f72b02"; // Reemplaza esto con el valor real

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post('https://servicios.bncenlinea.com:16500/api/Auth/LogOn', json_encode([
            'ClientGUID' => $clientGUID,
        ]));

        // Mostrar status y cuerpo de la respuesta
        dd($response->status(), $response->json());


        return response()->json([
            'message' => 'Hello World'
        ]);
    }
}
