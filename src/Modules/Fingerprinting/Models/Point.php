<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Models;

use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Modules\Fingerprinting\Dto\Pattern;
use Ninja\DeviceTracker\Modules\Fingerprinting\Enums\PointType;
use Ninja\DeviceTracker\Traits\PropertyProxy;

/**
 * Class Point
 *
 * @package Ninja\DeviceManager\Modules\Fingerprinting\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property int                          $id                     unsigned int
 * @property string                       $route                  string
 * @property string                       $path                   string
 * @property string                       $title                  string
 * @property PointType                    $type                   string
 * @property integer                      $index                  unsigned int
 * @property Metadata                     $metadata               json
 * @property Carbon                       $created_at             datetime
 * @property Carbon                       $updated_at             datetime
 *
 */
class Point extends Model
{
    use PropertyProxy;

    protected $fillable = [
        'route',
        'path',
        'title',
    ];

    public function trackings(): BelongsToMany
    {
        return $this->belongsToMany(Tracking::class, 'device_tracking_points')
            ->withPivot('first_tracking_at', 'last_tracking_at', 'count', 'pattern', 'metadata')
            ->withTimestamps();
    }

    public function type(): Attribute
    {
        return Attribute::make(
            get: fn(?string $value) => $value ? PointType::from($value) : PointType::Page,
            set: fn(PointType $value) => $value->value
        );
    }

    public static function byPath(string $path): ?self
    {
        $key = sprintf("tracking:route:{%s}", $path);
        return Cache::remember($key, now()->addDay(), fn() => self::where('path', $path)->first());
    }

    public static function create(string $path, int $index): self
    {
        return self::firstOrCreate([
            'path' => $path,
            'index' => $index,
        ], [
            'type' => 'favicon',
            'route' => route('tracking.favicon', ['path' => $path]),
        ]);
    }

    public function track(Tracking $tracking, array $metadata = []): void
    {
        $existing = $this->trackings()->wherePivot("device_tracking_id", $tracking->id)->first();
        if ($existing) {
            $pattern = Pattern::from($existing->pivot->pattern);
            $pattern->add(now());

            $this->trackings()->updateExistingPivot($tracking->id, [
                'last_tracking_at' => now(),
                'count' => $existing->pivot->visit_count + 1,
                'metadata' => array_merge($existing->pivot->metadata ?? [], $metadata),
                'pattern' => $pattern,
            ]);
        } else {
            $this->trackings()->attach($tracking, [
                'first_tracking_at' => now(),
                'last_tracking_at' => now(),
                'count' => 1,
                'metadata' => $metadata,
                'pattern' => new Pattern([now()]),
            ]);
        }
    }

    public function in(int $fingerprint): bool
    {
        return (($fingerprint >> $this->index) & 1) === 1;
    }

    public static function next(int $current): ?self
    {
        return self::where('index', '>', $current)
            ->orderBy('index')
            ->first();
    }
}
