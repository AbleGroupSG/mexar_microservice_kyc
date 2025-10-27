<?php

namespace App\Http\Controllers;

use App\Http\Controllers\API\APIController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends APIController
{
    public function __construct()
    {
    }

    /**
     * Microservice manifest endpoint
     *
     * @return JsonResponse
     */
    public function index()
    {
        return response()->json([
            'name'  =>  'MEXAR MSA KYC',
            'description'   =>  'MEXAR KYC Microservice',
            'version'   =>  '1.0.0',
            'status'    =>  'running',

            // the mexar node will be checked by the integration service
            // if the node is not available, the integration service will be disabled
            'mexar' =>  [
                'integration' => [
                    'enabled'   =>  false,
                ],
                'kafka' => [
                    'enabled'   =>  false,
                ],
                'webhook' => [
                    'enabled'   =>  true,
                ],
            ],
        ]);
    }
}
