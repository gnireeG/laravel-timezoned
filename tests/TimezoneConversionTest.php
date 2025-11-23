<?php

namespace Gnireeg\LaravelTimezoned\Tests;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Gnireeg\LaravelTimezoned\HasTimezoneConversion;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;

class TimezoneConversionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Carbon::setTestNow('2024-06-15 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    #[Test]
    public function it_converts_datetime_to_user_timezone_on_get(): void
    {
        config(['timezoned.database_timezone' => 'UTC']);
        config(['timezoned.timezone_resolver' => fn () => 'America/New_York']); // UTC-4 in summer

        $model = new TestModel();
        $model->setRawAttributes(['starts_at' => '2024-06-15 16:00:00']);

        $result = $model->starts_at;

        $this->assertInstanceOf(Carbon::class, $result);
        $this->assertEquals('America/New_York', $result->timezoneName);
        $this->assertEquals('12:00:00', $result->format('H:i:s')); // 16:00 UTC = 12:00 NYC
    }

    #[Test]
    public function it_converts_datetime_to_database_timezone_on_set(): void
    {
        config(['timezoned.database_timezone' => 'UTC']);
        config(['timezoned.timezone_resolver' => fn () => 'America/New_York']);

        $model = new TestModel();
        $model->starts_at = '2024-06-15 12:00:00'; // User inputs 12:00 NYC time

        $raw = $model->getAttributes()['starts_at'];

        $this->assertInstanceOf(Carbon::class, $raw);
        $this->assertEquals('UTC', $raw->timezoneName);
        $this->assertEquals('16:00:00', $raw->format('H:i:s')); // 12:00 NYC = 16:00 UTC
    }

    #[Test]
    public function it_handles_null_values_on_get(): void
    {
        $model = new TestModel();
        $model->setRawAttributes(['starts_at' => null]);

        $this->assertNull($model->starts_at);
    }

    #[Test]
    public function it_handles_null_values_on_set(): void
    {
        $model = new TestModel();
        $model->starts_at = null;

        $this->assertNull($model->getAttributes()['starts_at']);
    }

    #[Test]
    public function it_does_not_convert_non_timezoned_attributes(): void
    {
        config(['timezoned.timezone_resolver' => fn () => 'America/New_York']);

        $model = new TestModel();
        $model->setRawAttributes(['name' => 'Test Event']);

        $this->assertEquals('Test Event', $model->name);
    }

    #[Test]
    public function it_accepts_carbon_instances_on_set(): void
    {
        config(['timezoned.database_timezone' => 'UTC']);
        config(['timezoned.timezone_resolver' => fn () => 'Europe/London']);

        $model = new TestModel();
        $model->starts_at = Carbon::parse('2024-06-15 14:00:00', 'Europe/London');

        $raw = $model->getAttributes()['starts_at'];

        $this->assertEquals('UTC', $raw->timezoneName);
        $this->assertEquals('13:00:00', $raw->format('H:i:s')); // 14:00 London (BST) = 13:00 UTC
    }

    #[Test]
    public function it_accepts_carbon_immutable_instances_on_set(): void
    {
        config(['timezoned.database_timezone' => 'UTC']);
        config(['timezoned.timezone_resolver' => fn () => 'Europe/London']);

        $model = new TestModel();
        $model->starts_at = CarbonImmutable::parse('2024-06-15 14:00:00', 'Europe/London');

        $raw = $model->getAttributes()['starts_at'];

        $this->assertEquals('UTC', $raw->timezoneName);
        $this->assertEquals('13:00:00', $raw->format('H:i:s')); // 14:00 London (BST) = 13:00 UTC
    }

    #[Test]
    public function it_returns_raw_attribute_in_database_timezone(): void
    {
        config(['timezoned.database_timezone' => 'UTC']);
        config(['timezoned.timezone_resolver' => fn () => 'Asia/Tokyo']); // UTC+9

        $model = new TestModel();
        $model->setRawAttributes(['starts_at' => '2024-06-15 12:00:00']);

        $raw = $model->getRawAttribute('starts_at');
        $converted = $model->starts_at;

        $this->assertEquals('UTC', $raw->timezoneName);
        $this->assertEquals('12:00:00', $raw->format('H:i:s'));

        $this->assertEquals('Asia/Tokyo', $converted->timezoneName);
        $this->assertEquals('21:00:00', $converted->format('H:i:s')); // 12:00 UTC = 21:00 Tokyo
    }

    #[Test]
    public function it_uses_app_timezone_when_database_timezone_is_null(): void
    {
        config(['app.timezone' => 'Europe/Paris']);
        config(['timezoned.database_timezone' => null]);
        config(['timezoned.timezone_resolver' => fn () => 'UTC']);

        $model = new TestModel();
        $model->setRawAttributes(['starts_at' => '2024-06-15 14:00:00']); // Paris time (CEST = UTC+2)

        $result = $model->starts_at;

        $this->assertEquals('UTC', $result->timezoneName);
        $this->assertEquals('12:00:00', $result->format('H:i:s')); // 14:00 Paris = 12:00 UTC
    }

    #[Test]
    public function it_handles_different_database_timezones(): void
    {
        config(['timezoned.database_timezone' => 'America/Los_Angeles']);
        config(['timezoned.timezone_resolver' => fn () => 'Europe/Berlin']);

        $model = new TestModel();
        $model->setRawAttributes(['starts_at' => '2024-06-15 09:00:00']); // LA time (PDT = UTC-7)

        $result = $model->starts_at;

        $this->assertEquals('Europe/Berlin', $result->timezoneName);
        $this->assertEquals('18:00:00', $result->format('H:i:s')); // 09:00 LA = 16:00 UTC = 18:00 Berlin
    }

    #[Test]
    public function it_passes_model_to_timezone_resolver(): void
    {
        $receivedModel = null;

        config(['timezoned.timezone_resolver' => function ($model) use (&$receivedModel) {
            $receivedModel = $model;
            return 'UTC';
        }]);

        $model = new TestModel();
        $model->setRawAttributes(['starts_at' => '2024-06-15 12:00:00']);
        $model->starts_at; // Trigger the resolver

        $this->assertSame($model, $receivedModel);
    }

    #[Test]
    public function it_handles_empty_timezoned_attributes_array(): void
    {
        $model = new EmptyTimezonedModel();
        $model->setRawAttributes(['created_at' => '2024-06-15 12:00:00']);

        // Should return the raw value without conversion
        $this->assertEquals('2024-06-15 12:00:00', $model->created_at);
    }

}

class TestModel extends Model
{
    use HasTimezoneConversion;

    protected $guarded = [];

    protected array $timezonedAttributes = ['starts_at', 'ends_at'];
}

class EmptyTimezonedModel extends Model
{
    use HasTimezoneConversion;

    protected $guarded = [];

    // Uses default empty array from trait
}
