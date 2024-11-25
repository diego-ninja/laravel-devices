<?php

namespace Ninja\DeviceTracker\UI\Livewire;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorConfiguration extends Component
{
    public bool $enabled = false;

    public ?string $qrCode = null;

    public ?string $code = null;

    public string $message = '';

    public function mount(): void
    {
        $this->enabled = Auth::user()->google2faEnabled();
        if ($this->enabled) {
            $this->qrCode = Auth::user()->google2faQrCode();
        }
    }

    public function enable(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        Auth::user()->enable2fa($secret);
        $this->enabled = true;
        $this->qrCode = Auth::user()->google2faQrCode();

        $this->message = 'Two-factor authentication has been enabled. Please scan the QR code.';
    }

    public function disable(): void
    {
        Auth::user()->google2fa->disable();
        $this->enabled = false;
        $this->qrCode = null;
        $this->message = 'Two-factor authentication has been disabled.';
    }

    public function verifyCode(): void
    {
        $valid = app(Google2FA::class)->verifyKey(
            Auth::user()->google2fa->secret(),
            $this->code
        );

        if ($valid) {
            Auth::user()->google2fa->success();
            $this->message = 'Code verified successfully.';
        } else {
            $this->message = 'Invalid verification code.';
        }

        $this->code = null;
    }

    public function render()
    {
        return view('livewire.two-factor-config');
    }
}
