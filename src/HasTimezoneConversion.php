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
     * Dynamically create timezone conversion accessors (UTC -> User timezone)
     * ONLY for display - NO automatic saving conversion
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);
        
        if (in_array($key, $this->getTimezoneConvertedAttributes()) && $value instanceof Carbon) {
            return $this->convertToUserTimezone($key, $value);
        }
        
        return $value;
    }

    public function setAttribute($key, $value)
    {
        $userTimezone = call_user_func(config('timezoned.timezone_resolver'), $this);
        if (in_array($key, $this->getTimezoneConvertedAttributes()) && $value instanceof Carbon) {
            $newDate = Carbon::parse($value)->shiftTimezone($userTimezone)->setTimezone(config('timezoned.database_timezone'));
            return parent::setAttribute($key, $newDate);
        }
        
        return parent::setAttribute($key, $value);
    }
}