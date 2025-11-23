<?php

namespace Gnireeg\LaravelTimezoned;

use Carbon\Carbon;
use Carbon\CarbonInterface;

trait HasTimezoneConversion
{
    /**
     * Get the database timezone, with fallback to app timezone.
     */
    protected function getDatabaseTimezone(): string
    {
        return config('timezoned.database_timezone') ?? config('app.timezone', 'UTC');
    }

    /**
     * Get the user's timezone from the configured resolver.
     */
    protected function getUserTimezone(): string
    {
        return call_user_func(config('timezoned.timezone_resolver'), $this);
    }

    /**
     * Get the list of attributes that should be converted to user timezone.
     *
     * @return array<int, string>
     */
    protected function getTimezonedAttributes(): array
    {
        return $this->timezonedAttributes ?? [];
    }

    /**
     * Check if the given key is a timezoned attribute.
     */
    protected function isTimezonedAttribute(string $key): bool
    {
        return in_array($key, $this->getTimezonedAttributes(), true);
    }

    /**
     * Convert datetime attributes to user's timezone.
     */
    protected function convertToUserTimezone(string $attribute, mixed $value): ?CarbonInterface
    {
        if ($value === null) {
            return null;
        }

        $datetime = $this->asDateTime($value);

        return $datetime->setTimezone($this->getUserTimezone());
    }

    /**
     * Get the raw datetime value from the database without timezone conversion.
     */
    public function getRawAttribute(string $attribute): mixed
    {
        $value = parent::getAttribute($attribute);

        if ($value === null) {
            return null;
        }

        // If it's a timezoned attribute, ensure it's in database timezone
        if ($this->isTimezonedAttribute($attribute)) {
            $datetime = $value instanceof CarbonInterface
                ? $value
                : Carbon::parse($value, $this->getDatabaseTimezone());

            return $datetime->setTimezone($this->getDatabaseTimezone());
        }

        return $value;
    }

    /**
     * Convert model datetime attributes FROM database timezone TO user timezone
     * whenever they are accessed.
     */
    public function getAttribute(mixed $key): mixed
    {
        $value = parent::getAttribute($key);

        if (!$this->isTimezonedAttribute($key)) {
            return $value;
        }

        if ($value === null) {
            return null;
        }

        // Normalize into a Carbon instance
        $datetime = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value, $this->getDatabaseTimezone());

        return $this->convertToUserTimezone($key, $datetime);
    }

    /**
     * Convert model datetime attributes FROM user timezone TO database timezone
     * whenever they are assigned.
     */
    public function setAttribute(mixed $key, mixed $value): mixed
    {
        if (!$this->isTimezonedAttribute($key)) {
            return parent::setAttribute($key, $value);
        }

        if ($value === null) {
            return parent::setAttribute($key, null);
        }

        $userTimezone = $this->getUserTimezone();
        $dbTimezone = $this->getDatabaseTimezone();

        // Normalize into a Carbon instance
        $datetime = $value instanceof CarbonInterface
            ? $value
            : Carbon::parse($value, $userTimezone);

        // Convert from user timezone → DB timezone
        $normalized = $datetime
            ->shiftTimezone($userTimezone)
            ->setTimezone($dbTimezone);

        return parent::setAttribute($key, $normalized);
    }
}
