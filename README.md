# Laravel Timezoned

A lightweight Laravel package that automatically converts Eloquent model date attributes between the database timezone and the user's timezone.  
It provides a simple trait that handles both directions:

- DB → User timezone when reading attributes  
- User → DB timezone when writing attributes  

This ensures consistent and intuitive date handling across different user timezones.

---

## Installation

    composer require gnireeg/laravel-timezoned

    php artisan vendor:publish --tag=config

---

## Configuration

The package includes a configurable timezone resolver in `config/timezoned.php`:

``
    'timezone_resolver' => function () {
        return auth()->user()?->timezone ?? 'UTC';
    },
``

You can modify this as needed:

``    'timezone_resolver' => fn() => session('timezone', 'UTC'),``

``    'timezone_resolver' => fn() => request()->header('X-Timezone'),``

``    'timezone_resolver' => fn() => config('app.timezone'),``

---

## Usage

``

    use Gnireeg\LaravelTimezoned\HasTimezoneConversion;

    class Event extends Model
    {
        use HasTimezoneConversion;

        protected function getTimezonedAttributes(): array
        {
            return [
                'starts_at',
                'ends_at',
            ];
        }
    }

``

### Reading Attributes

    $event->starts_at;

### Writing Attributes

    $event->starts_at = '2025-03-10 15:00';
    $event->save();

---

## How It Works

The trait overrides two internal Eloquent methods:

- getAttribute() converts Carbon dates to the user’s timezone  
- setAttribute() converts incoming dates from the user's timezone back to UTC  

Everything happens automatically.

---

## Requirements

- PHP >= 8.1  
- Laravel 10, 11, or 12  
- Carbon >= 2  

---

## License

MIT
