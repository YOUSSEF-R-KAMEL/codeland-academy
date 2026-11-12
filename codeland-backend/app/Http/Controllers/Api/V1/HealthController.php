<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class HealthController extends ApiController
{
    public function __invoke(): JsonResponse
    {
        return $this->success(
            'Codeland Academy API is running',
            [
                'status' => 'healthy',
                'version' => '1.0.0',
            ]
        );
    }
}
