<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Http\Controllers;

use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Ninja\DeviceTracker\Modules\Fingerprinting\Services\FingerprintingService;
use Throwable;

final class FingerprintController extends Controller
{
    public function __construct(private readonly FingerprintingService $service)
    {
    }

    public function pixel(string $ref): Response
    {
        $key = sprintf('tracking:rate:%s:%s', request()->ip(), $ref);
        if (!Cache::add($key, 1, 60)) {
            return $this->pixelResponse();
        }

        try {
            $this->service->track($ref);
        } catch (Throwable $e) {
            report($e);
        }

        return $this->pixelResponse();
    }

    /**
     * @throws BindingResolutionException
     */
    private function pixelResponse(): Response
    {
        return response()->make(
            base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7'),
            200,
            ['Content-Type' => 'image/gif']
        )->setCache(['public' => true, 'max_age' => 600]);
    }
}