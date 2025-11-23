# Laravel Timezoned

A lightweight Laravel package that automatically converts Eloquent model date attributes between the database timezone and the user's timezone.

- **DB → User timezone** when reading attributes
- **User → DB timezone** when writing attributes

This ensures consistent and intuitive date handling across different user timezones.

---

## Installation

```bash
composer require gnireeg/laravel-timezoned
```

Optionally publish the config:

```bash
php artisan vendor:publish --tag=config
```

---

## Usage

```php
use Gnireeg\LaravelTimezoned\HasTimezoneConversion;

class Event extends Model
{
    use HasTimezoneConversion;

    protected array $timezonedAttributes = ['starts_at', 'ends_at'];
}
```

### Reading Attributes

```php
// Automatically converted to user's timezone
$event->starts_at; // Returns Carbon in user's timezone
```

### Writing Attributes

```php
// Automatically converted to database timezone
$event->starts_at = '2025-03-10 15:00';
$event->save();
```

### Raw Attribute Access

```php
// Get the value in database timezone (bypass conversion)
$event->getRawAttribute('starts_at');
```

---

## Configuration

The package config (`config/timezoned.php`) has two options:

### Timezone Resolver

Determines the user's timezone. Receives the model instance as a parameter.

```php
// Default: uses authenticated user's timezone
'timezone_resolver' => function ($model) {
    return auth()->user()?->timezone ?? 'UTC';
},

// Or use session
'timezone_resolver' => fn() => session('timezone', 'UTC'),

// Or request header
'timezone_resolver' => fn() => request()->header('X-Timezone', 'UTC'),
```

### Database Timezone

The timezone your database stores dates in. Set to `null` to use `config('app.timezone')`.

```php
'database_timezone' => null, // Uses app.timezone
'database_timezone' => 'UTC', // Explicit UTC
```

---

## How It Works

The trait overrides two Eloquent methods:

- `getAttribute()` - converts dates from database timezone to user's timezone
- `setAttribute()` - converts dates from user's timezone to database timezone

Both `Carbon` and `CarbonImmutable` instances are supported.

---

## Requirements

- PHP >= 8.1
- Laravel 10, 11, or 12
- Carbon >= 2

---

## Testing

```bash
composer test
```

---

## License

MIT
