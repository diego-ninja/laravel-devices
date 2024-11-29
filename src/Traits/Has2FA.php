<?php

namespace Ninja\DeviceTracker\Traits;

use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\Fill;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Config;
use PragmaRX\Google2FA\Google2FA;

/**
 * @phpstan-require-implements Authenticatable
 */
trait Has2FA
{
    public function google2fa(): HasOne
    {
        return $this->hasOne(\Ninja\DeviceTracker\Models\Google2FA::class, 'user_id');
    }

    public function google2faQrCode(string $format = 'SVG'): string
    {
        return $format === 'SVG'
            ? $this->createSvgQrCode($this->google2faQrCodeUrl())
            : $this->createPngQrCode($this->google2faQrCodeUrl());
    }

    public function google2faEnabled(): bool
    {
        if (! Config::get('devices.google_2fa_enabled')) {
            return false;
        }

        return
            $this->google2fa &&
            $this->google2fa->enabled();
    }

    public function enable2fa(string $secret): void
    {
        $google2fa = new \Ninja\DeviceTracker\Models\Google2FA;
        $google2fa->user_id = $this->id;
        $google2fa->enable($secret);

        $this->save();
    }

    public function google2faQrCodeUrl(): string
    {
        $google2fa = app(Google2FA::class);

        return $google2fa->getQRCodeUrl(
            company: config('app.name'),
            holder: $this->email,
            secret: $this->google2fa->secret()
        );
    }

    private function createSvgQrCode(string $url): string
    {
        $svg = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new SvgImageBackEnd
            )
        ))->writeString($url);

        return trim(substr($svg, strpos($svg, "\n") + 1));
    }

    private function createPngQrCode(string $url): string
    {
        $png = (new Writer(
            new ImageRenderer(
                new RendererStyle(192, 0, null, null, Fill::uniformColor(new Rgb(255, 255, 255), new Rgb(45, 55, 72))),
                new ImagickImageBackEnd
            )
        ))->writeString($url);

        return base64_encode($png);
    }
}
