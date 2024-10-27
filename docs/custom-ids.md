# Custom ID Implementation

## Overview

Laravel Devices allows you to customize how device and session identifiers are generated and stored by implementing the `StorableId` interface. This feature enables you to use your own ID formats, such as UUIDs, sequential IDs, or custom formats that meet your specific requirements.

## StorableId Interface

```php
interface StorableId extends Stringable
{
    public static function from(StorableId|string $id): StorableId;
    public static function build(): StorableId;
}
```

## Implementation Examples

### Basic UUID Implementation

```php
use Ninja\DeviceTracker\Contracts\StorableId;
use Ramsey\Uuid\Uuid;

class CustomUuid implements StorableId
{
    private string $uuid;

    private function __construct(string $uuid)
    {
        $this->uuid = $uuid;
    }

    public static function from(StorableId|string $id): StorableId
    {
        if ($id instanceof StorableId) {
            return $id;
        }

        return new self($id);
    }

    public static function build(): StorableId
    {
        return new self(Uuid::uuid4()->toString());
    }

    public function toString(): string
    {
        return $this->uuid;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
```

### Sequential ID with Prefix

```php
use Ninja\DeviceTracker\Contracts\StorableId;
use Illuminate\Support\Facades\Cache;

class PrefixedSequentialId implements StorableId
{
    private string $id;
    private const PREFIX = 'DEV';
    
    private function __construct(string $id)
    {
        $this->id = $id;
    }
    
    public static function from(StorableId|string $id): StorableId
    {
        if ($id instanceof StorableId) {
            return $id;
        }
        
        if (!str_starts_with($id, self::PREFIX)) {
            throw new InvalidArgumentException('Invalid ID format');
        }
        
        return new self($id);
    }
    
    public static function build(): StorableId
    {
        $sequence = Cache::increment('device_sequence', 1);
        $id = sprintf('%s%09d', self::PREFIX, $sequence);
        
        return new self($id);
    }
    
    public function toString(): string
    {
        return $this->id;
    }
    
    public function __toString(): string
    {
        return $this->toString();
    }
}
```

### Timestamp-Based ID

```php
use Ninja\DeviceTracker\Contracts\StorableId;

class TimestampId implements StorableId
{
    private string $id;
    
    private function __construct(string $id)
    {
        $this->id = $id;
    }
    
    public static function from(StorableId|string $id): StorableId
    {
        if ($id instanceof StorableId) {
            return $id;
        }
        
        return new self($id);
    }
    
    public static function build(): StorableId
    {
        $timestamp = now()->format('YmdHis');
        $random = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        
        return new self($timestamp . $random);
    }
    
    public function toString(): string
    {
        return $this->id;
    }
    
    public function __toString(): string
    {
        return $this->toString();
    }
    
    public function getTimestamp(): Carbon
    {
        $timestampPart = substr($this->id, 0, 14);
        return Carbon::createFromFormat('YmdHis', $timestampPart);
    }
}
```

## Configuration

### Device ID Configuration

```php
// config/devices.php
return [
    'device_id_storable_class' => CustomUuid::class,
    // ...
];
```

### Session ID Configuration

```php
// config/devices.php
return [
    'session_id_storable_class' => TimestampId::class,
    // ...
];
```

## Advanced Implementation Examples

### Encrypted ID

```php
use Ninja\DeviceTracker\Contracts\StorableId;
use Illuminate\Support\Facades\Crypt;

class EncryptedId implements StorableId
{
    private string $id;
    private string $encryptedId;
    
    private function __construct(string $id, bool $isEncrypted = false)
    {
        if ($isEncrypted) {
            $this->encryptedId = $id;
            $this->id = Crypt::decryptString($id);
        } else {
            $this->id = $id;
            $this->encryptedId = Crypt::encryptString($id);
        }
    }
    
    public static function from(StorableId|string $id): StorableId
    {
        if ($id instanceof StorableId) {
            return $id;
        }
        
        return new self($id, true);
    }
    
    public static function build(): StorableId
    {
        $id = sprintf(
            '%s-%s',
            now()->timestamp,
            bin2hex(random_bytes(8))
        );
        
        return new self($id);
    }
    
    public function toString(): string
    {
        return $this->encryptedId;
    }
    
    public function __toString(): string
    {
        return $this->toString();
    }
    
    public function getDecryptedId(): string
    {
        return $this->id;
    }
}
```

### Composite ID

```php
use Ninja\DeviceTracker\Contracts\StorableId;

class CompositeId implements StorableId
{
    private string $id;
    private array $components;
    
    private function __construct(string $id)
    {
        $this->id = $id;
        $this->components = explode('-', $id);
    }
    
    public static function from(StorableId|string $id): StorableId
    {
        if ($id instanceof StorableId) {
            return $id;
        }
        
        return new self($id);
    }
    
    public static function build(): StorableId
    {
        $components = [
            'env' => app()->environment(),
            'timestamp' => now()->timestamp,
            'random' => bin2hex(random_bytes(4))
        ];
        
        $id = implode('-', $components);
        
        return new self($id);
    }
    
    public function toString(): string
    {
        return $this->id;
    }
    
    public function __toString(): string
    {
        return $this->toString();
    }
    
    public function getComponent(string $name): ?string
    {
        $index = match($name) {
            'env' => 0,
            'timestamp' => 1,
            'random' => 2,
            default => null
        };
        
        return $index !== null ? ($this->components[$index] ?? null) : null;
    }
}
```

## Validation and Security

### ID Validator

```php
class StorableIdValidator
{
    public function validate(StorableId $id): bool
    {
        // Implement validation logic
        return match (get_class($id)) {
            CustomUuid::class => $this->validateUuid($id),
            PrefixedSequentialId::class => $this->validatePrefixed($id),
            TimestampId::class => $this->validateTimestamp($id),
            EncryptedId::class => $this->validateEncrypted($id),
            CompositeId::class => $this->validateComposite($id),
            default => throw new InvalidArgumentException('Unsupported ID type')
        };
    }
    
    private function validateUuid(StorableId $id): bool
    {
        return Uuid::isValid($id->toString());
    }
    
    private function validatePrefixed(StorableId $id): bool
    {
        return preg_match('/^DEV\d{9}$/', $id->toString()) === 1;
    }
    
    // Additional validation methods...
}
```

## Testing Custom IDs

```php
use Tests\TestCase;

class CustomIdTest extends TestCase
{
    /** @test */
    public function it_generates_valid_ids()
    {
        $id = CustomUuid::build();
        
        $this->assertInstanceOf(StorableId::class, $id);
        $this->assertTrue(Uuid::isValid($id->toString()));
    }
    
    /** @test */
    public function it_creates_from_string()
    {
        $uuid = Uuid::uuid4()->toString();
        $id = CustomUuid::from($uuid);
        
        $this->assertInstanceOf(StorableId::class, $id);
        $this->assertEquals($uuid, $id->toString());
    }
    
    /** @test */
    public function it_handles_invalid_formats()
    {
        $this->expectException(InvalidArgumentException::class);
        
        CustomUuid::from('invalid-uuid');
    }
}
```

## Best Practices

1. **ID Format Selection**
    - Choose formats that are:
        - Unique across your system
        - Suitable for your database indexes
        - Appropriate for your security requirements
        - Easy to track and debug

2. **Performance Considerations**
    - Use efficient generation methods
    - Consider caching for sequential IDs
    - Optimize database index usage
    - Minimize ID length while maintaining uniqueness

3. **Security Guidelines**
    - Avoid predictable sequences
    - Consider encryption for sensitive IDs
    - Validate input thoroughly
    - Handle collisions gracefully

## Next Steps

- Review [Device Management](device-management.md)
- Explore [Session Management](session-management.md)
- Learn about [API Integration](api-reference.md)