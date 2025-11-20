<?php

namespace Gnireeg\LaravelTimezoned;

use Carbon\Carbon;
use Carbon\CarbonTimeZone;

trait HasTimezoneConversion
{
    /**
     * Convert datetime attributes to user's timezone
     * 
     * @param string $attribute
     * @param mixed $value
     * @return \Carbon\Carbon|null
     */
    protected function convertToUserTimezone($attribute, $value)
    {
        if (!$value) return null;
        
        $datetime = $this->asDateTime($value);
        $userTimezone = call_user_func(config('timezoned.timezone_resolver'), $this);
        
        return $datetime->setTimezone($userTimezone);
    }
    
    /**
     * Get the list of attributes that should be converted to user timezone
     * Override this method in your model to specify which datetime fields to convert
     * 
     * @return array
     */
    protected function getTimezonedAttributes(): array
    {
        return [];
    }
    
    /**
     * Get the raw datetime value from the database without timezone conversion
     * 
     * @param string $attribute
     * @return \Carbon\Carbon|mixed|null
     */
    public function getRawAttribute($attribute)
    {
        $value = parent::getAttribute($attribute);
        
        if (!$value) {
            return null;
        }
        
        // If it's a timezoned attribute, ensure it's in database timezone
        if (in_array($attribute, $this->getTimezonedAttributes())) {
            $datetime = $value instanceof Carbon
                ? $value
                : Carbon::parse($value, config('timezoned.database_timezone'));
            return $datetime->setTimezone(config('timezoned.database_timezone'));
        }
        
        return $value;
    }
    
    /**
     * Convert model datetime attributes FROM database timezone TO user timezone
     * whenever they are accessed.
     *
     * This ensures that all listed attributes in getTimezonedAttributes()
     * are always returned in the user's timezone, regardless of whether they were
     * stored as strings or Carbon instances.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        if (!in_array($key, $this->getTimezonedAttributes())) {
            return $value;
        }

        if (!$value) {
            return null;
        }

        // Normalize into a Carbon instance
        $datetime = $value instanceof Carbon
            ? $value
            : Carbon::parse($value, config('timezoned.database_timezone'));

        return $this->convertToUserTimezone($key, $datetime);
    }



    /**
     * Convert model datetime attributes FROM user timezone TO database timezone
     * whenever they are assigned.
     *
     * This ensures that user input (strings or Carbon instances) is always
     * stored in a consistent, database-defined timezone.
     */
    public function setAttribute($key, $value)
    {
        if (!in_array($key, $this->getTimezonedAttributes())) {
            return parent::setAttribute($key, $value);
        }

        if (!$value) {
            return parent::setAttribute($key, null);
        }

        $userTimezone = call_user_func(config('timezoned.timezone_resolver'), $this);
        $dbTimezone = config('timezoned.database_timezone');

        // Normalize into a Carbon instance
        $datetime = $value instanceof Carbon
            ? $value
            : Carbon::parse($value, $userTimezone);

        // Convert from user timezone → DB timezone
        $normalized = $datetime
            ->shiftTimezone($userTimezone)
            ->setTimezone($dbTimezone);

        return parent::setAttribute($key, $normalized);
    }
}