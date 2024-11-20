<?php

namespace Ninja\DeviceTracker\Modules\Security\Rule;

use Illuminate\Http\Request;
use Ninja\DeviceTracker\Models\Session;
use Ninja\DeviceTracker\Modules\Security\Context\SecurityContext;
use Ninja\DeviceTracker\Modules\Security\DTO\Factor;

final class ProxyDetectionRule extends AbstractSecurityRule
{
    public function evaluate(SecurityContext $context): Factor
    {
        if (! $context->session) {
            return new Factor($this->factor, 0.0);
        }

        $score = 0.0;

        if ($this->isKnownProxy($context->session->ip)) {
            $score += 0.4;
        }

        if ($this->hasProxyHeaders($context->request)) {
            $score += 0.3;
        }

        if ($this->hasGeoDiscrepancies($context->session)) {
            $score += 0.3;
        }

        $score = min($score, 1.0);

        return new Factor($this->factor, $score);
    }

    private function isKnownProxy(string $ip): bool
    {
        // Implementar verificación contra una lista de IPs de proxy conocidas
        // Podría usar servicios como IPQualityScore, IPHub, etc.
        return false;
    }

    private function hasProxyHeaders(Request $request): bool
    {
        $proxyHeaders = [
            'HTTP_VIA',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_X_FORWARDED_HOST',
            'HTTP_X_FORWARDED_PORT',
            'HTTP_PROXY_CONNECTION',
        ];

        foreach ($proxyHeaders as $header) {
            if ($request->hasHeader($header)) {
                return true;
            }
        }

        return false;
    }

    private function hasGeoDiscrepancies(Session $session): bool
    {
        $timezone = request()->header('X-Timezone');
        if ($timezone && $timezone !== $session->location->timezone) {
            return true;
        }

        return false;
    }
}
