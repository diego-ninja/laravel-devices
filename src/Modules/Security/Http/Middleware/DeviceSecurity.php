<?php

namespace Ninja\DeviceTracker\Modules\Security\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Enums\EventType;
use Ninja\DeviceTracker\Enums\SessionStatus;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Models\Event;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DeviceSecurityManager;
use Ninja\DeviceTracker\Modules\Security\Jobs\CalculateDeviceRiskJob;

final readonly class DeviceSecurity
{
    public function __construct(private DeviceSecurityManager $manager)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        if (!config('devices.security.enabled')) {
            return $next($request);
        }

        if ($this->recalculate(device())) {
            CalculateDeviceRiskJob::dispatch(
                $this->manager,
                SecurityContext::current()
            );
        }

        if (\device()->risk->high()) {
            return $this->handleHighRisk(\device());
        }

        if (\device()->risk->medium()) {
            return $this->handleMediumRisk(\device(), $request);
        }

        return $next($request);
    }

    private function recalculate(Device $device): bool
    {
        $threshold = config('devices.security.risk.recalculation_threshold', 60);
        return
            !$device->risk_assessed_at ||
            $device->risk_assessed_at->diffInMinutes(now()) > $threshold;
    }

    private function handleHighRisk(Device $device): RedirectResponse|JsonResponse
    {
        $device->sessions()->active()->each(fn($session) => $session->end(true));

        if (config('devices.security.risk.auto_hijack_on_high_risk', true)) {
            $device->hijack();
        }

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Access denied due to high security risk',
                'risk' => $device->risk->array(),
            ], 403);
        }

        return redirect()->route(config('devices.security.high_risk_redirect_route'))
            ->with('security_alert', 'Your device has been flagged for suspicious activity');
    }

    private function handleMediumRisk(Device $device, Request $request): RedirectResponse|JsonResponse|null
    {
        if (!$device->verified() && config('devices.security.risk.require_2fa_on_medium_risk', true)) {
            $device->device->sessions()->active()->each(fn(Session $session) => $session->status = SessionStatus::Locked);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => '2FA verification required',
                    'risk' => $device->risk->array(),
                ], 428);
            }

            return redirect()->route(config('devices.google_2fa_route_name'));
        }

        Event::log(EventType::SecurityWarning, session(), new Metadata([
            'risk' => $device->risk->array(),
        ]));

        return null;
    }
}
