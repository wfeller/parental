<?php

namespace WF\Parental\Tests\Features;

use PHPUnit\Framework\Attributes\Test;
use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\Driver;
use WF\Parental\Tests\Models\InternationalTrip;
use WF\Parental\Tests\Models\Trip;
use WF\Parental\Tests\TestCase;

class TypeColumnGetsSetAutomaticallyTest extends TestCase
{
    #[Test]
    function type_column_gets_set_on_creation()
    {
        $car = Car::create();

        $this->assertNotNull($car->fresh()->type);
    }

    #[Test]
    function type_column_gets_set_on_creation_from_many_to_many_relationship()
    {
        $trip = Trip::create();
        $car = $trip->cars()->create([]);

        $this->assertNotNull($car->fresh()->type);
    }

    #[Test]
    function type_column_gets_set_on_creation_from_has_many_relationship()
    {
        $driver = Driver::create(['name' => 'Joe']);
        $car = $driver->cars()->create([]);

        $this->assertNotNull($car->fresh()->type);
    }

    #[Test]
    function type_column_gets_set_on_saving_from_has_many_relationship()
    {
        $driver = Driver::create(['name' => 'Joe']);
        $car = $driver->cars()->save(new Car);

        $this->assertNotNull($car->fresh()->type);
    }

    #[Test]
    function type_column_gets_set_on_creation_from_a_model_factory()
    {
        $car = Car::factory()->create();

        $this->assertNotNull($car->type);
    }

    #[Test]
    function custom_type_column_gets_used()
    {
        $internationalTrip = InternationalTrip::create();

        $this->assertNotNull($internationalTrip->fresh()->trip_type);
    }
}
