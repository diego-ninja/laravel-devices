<?php

namespace Ninja\DeviceTracker\Traits;

use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FA\Support\Constants;

/**
 * Class Session
 *
 * @package Ninja\DeviceManager\Traits
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property string                       $auth_secret            string
 * @property integer                      $auth_timestamp         unsigned int
 */
trait Has2FA
{
    public function enable2FA(string $secret): bool
    {
        if (!$this->is2FAEnabled()) {
            $this->set2FASecret($secret);
        }

        return $this->save();
    }

    public function get2FASecret(): ?string
    {
        return $this->two_factor_secret;
    }

    public function is2FAConfirmed(): ?Carbon
    {
        return $this->two_factor_confirmed_at;
    }

    public function is2FAEnabled(): bool
    {
        return $this->twoFactorSecret() !== null;
    }

    public function set2FASecret(string $secret): void
    {
        $this->two_factor_secret = $secret;
        $this->two_factor_confirmed_at = null;
    }

    public function confirm2FA(): void
    {
        $this->two_factor_confirmed_at = Carbon::now();
    }

    public function get2FAQRCode(string $company, string $email): string
    {
        $google2fa = app(Google2FA::class);

        $url = $google2fa->getQRCodeUrl(
            company: $company,
            holder: $email,
            secret: $this->get2FASecret()
        );

        $writer = new Writer(
            new ImageRenderer(
                new RendererStyle(400),
                new ImagickImageBackEnd()
            )
        );

        return base64_encode($writer->writeString($url));
    }
}
