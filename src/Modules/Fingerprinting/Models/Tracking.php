<?php

namespace Ninja\DeviceTracker\Modules\Fingerprinting\Models;

use Cache;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Ninja\DeviceTracker\Contracts\StorableId;
use Ninja\DeviceTracker\DeviceManager;
use Ninja\DeviceTracker\DTO\Metadata;
use Ninja\DeviceTracker\Models\Device;
use Ninja\DeviceTracker\Modules\Fingerprinting\Dto\Pattern;
use Ninja\DeviceTracker\Modules\Fingerprinting\Enums\PointType;
use Ninja\DeviceTracker\Traits\PropertyProxy;

/**
 * Class Tracking
 *
 * @package Ninja\DeviceManager\Modules\Fingerprinting\Models
 *
 * @mixin \Illuminate\Database\Query\Builder
 * @mixin \Illuminate\Database\Eloquent\Builder
 *
 * @property int                          $id                     unsigned int
 * @property StorableId                   $device_uuid            string
 * @property integer                      $storage_size           unsigned int
 * @property Metadata                     $metadata               json
 * @property Carbon                       $created_at             datetime
 * @property Carbon                       $updated_at             datetime
 *
 * @property Device                       $device
 * @property Collection                   $points
 *
 */
class Tracking extends Model
{
    use PropertyProxy;

    public $table = 'device_tracking';

    protected $fillable = [
        'device_uuid',
        'storage_size',
    ];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function points(): BelongsToMany
    {
        return $this->belongsToMany(Point::class, 'device_tracking_points')
            ->withPivot('first_tracking_at', 'last_tracking_at', 'count', 'pattern', 'metadata')
            ->withTimestamps();
    }

    public function pages(): BelongsToMany
    {
        return $this->points()
            ->where('type', "=", PointType::Page)
            ->orderBy('index');
    }

    public function initialize(): void
    {
        $key = sprintf("tracking:points:{%s}", $this->device_uuid);
        if (!Cache::has($key)) {
            Cache::tags(["tracking", "points"])->remember($key, now()->addDay(), function () {
                for ($i = 0; $i < $this->storage_size; $i++) {
                    Point::firstOrCreate([
                        'index' => $i
                    ], [
                        'path' => hash('md5', sprintf("{%s}:{%d}", $this->id, $i)),
                        'type' => PointType::Favicon,
                        'route' => route('tracking.favicon', ['index' => $i])
                    ]);
                }
            });
        }
    }

    public function routes(): array
    {
        $key = sprintf('tracking:points:{%s}', $this->id);
        return Cache::remember($key, now()->addMinutes(5), function () {
            return $this->points()
                ->orderBy('index')
                ->where('type', PointType::Favicon)
                ->take($this->storage_size)
                ->get()
                ->map(fn($point) => [
                    'path' => $point->path,
                    'route' => route('tracking.favicon', ['path' => $point->path]),
                    'should_track' => $this->shouldTrack($point),
                    'index' => $point->index,
                ])
                ->toArray();
        });
    }

    public function tracked(): BelongsToMany
    {
        return $this->points()->wherePivotNull('last_tracking_at');
    }
    public function track(string $path, string $title = null, array $metadata = []): void
    {
        $page = Point::firstOrCreate(['path' => $path, 'title' => $title, 'type' => 'page']);
        $page->visit($this, $metadata);
    }

    public function reading(): bool
    {
        return is_null($this->device->fingerprint);
    }

    public function signature(): array
    {
        $points = $this->points()
            ->withPivot(["first_tracking_at", "last_tracking_at", "count", "pattern"])
            ->get();

        $signature = [];
        foreach ($points as $point) {
            /** @var Point $point */
            $pattern = Pattern::from($point->pattern);
            $signature[$point->index] = [
                'first_tracking_at' => $point->pivot->first_tracking_at->timestamp,
                'last_tracking_at'  => $point->pivot->last_tracking_at->timestamp,
                'count' => $point->pivot->count,
                'intervals' => $pattern->intervals,
                'density' => $pattern->density()
            ];
        }

        return $signature;
    }

    public function vector(): array
    {
        if (!$this->device->fingerprint) {
            return [];
        }

        return Point::orderBy('index')
            ->get()
            ->map(fn($page) => [
                'path' => $page->path,
                'tracked' => $this->shouldTrack($page)
            ])
            ->toArray();
    }

    public function shouldTrack(Point $point): bool
    {
        if ($this->reading()) {
            return !$this->points()
                ->where('id', $point->id)
                ->exists();
        }

        return ($this->device->fingerprint >> $point->index) & 1;
    }

    public function fingerprint(): int
    {
        $points = $this->points()
            ->orderBy('index')
            ->pluck('index')
            ->toArray();

        $binary = array_fill(0, 32, '0');
        foreach ($points as $point) {
            $binary[$point] = '1';
        }

        return bindec(implode('', $binary));
    }

    public static function current(): ?self
    {
        return DeviceManager::current()->tracking;
    }
}
