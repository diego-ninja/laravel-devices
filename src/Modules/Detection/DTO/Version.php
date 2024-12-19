<?php

namespace Ninja\DeviceTracker\Modules\Detection\DTO;

use JsonSerializable;
use Stringable;
use Zerotoprod\DataModel\DataModel;

final readonly class Version implements JsonSerializable, Stringable
{
    use DataModel;

    public function __construct(
        public string $major,
        public string $minor,
        public string $patch,
    ) {}

    /**
     * @param  array<string, mixed>|string|object  $context
     */
    public static function from(array|string|object $context): self
    {
        if (is_array($context)) {
            return new self(
                major: $context['major'] ?? '0',
                minor: $context['minor'] ?? '0',
                patch: $context['patch'] ?? '0',
            );
        }

        if (is_string($context)) {
            return self::fromString($context);
        }

        return new self(
            major: property_exists($context, 'major') ? $context->major : '0',
            minor: property_exists($context, 'minor') ? $context->minor : '0',
            patch: property_exists($context, 'patch') ? $context->patch : '0',
        );
    }

    public static function fromString(string $version): self
    {
        $parts = explode('.', $version);

        return new self(
            major: $parts[0] ?? '0',
            minor: $parts[1] ?? '0',
            patch: $parts[2] ?? '0',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function array(): array
    {
        return [
            'major' => $this->major,
            'minor' => $this->minor,
            'patch' => $this->patch,
            'label' => (string) $this,
        ];
    }

    public function equals(Version $version): bool
    {
        return $this->major === $version->major
            && $this->minor === $version->minor
            && $this->patch === $version->patch;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->array();
    }

    public function __toString(): string
    {
        return sprintf('%s.%s.%s', $this->major, $this->minor, $this->patch);
    }

    public function json(): string|false
    {
        return json_encode($this->array());
    }
}
