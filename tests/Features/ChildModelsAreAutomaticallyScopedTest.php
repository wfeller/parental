<?php

namespace WF\Parental\Tests\Features;

use WF\Parental\Tests\Models\Admin;
use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\Driver;
use WF\Parental\Tests\Models\Passenger;
use WF\Parental\Tests\Models\User;
use WF\Parental\Tests\Models\Vehicle;
use WF\Parental\Tests\TestCase;

class ChildModelsAreAutomaticallyScopedTest extends TestCase
{
    /** @test */
    function child_is_scoped_based_on_type_column()
    {
        Car::create();
        Vehicle::create();

        $this->assertCount(2, Vehicle::all());
        $this->assertCount(1, Car::all());
    }

    /** @test */
    function child_without_type_column_isnt_scoped()
    {
        Admin::create();
        User::create();

        $this->assertCount(2, User::all());
        $this->assertCount(2, Admin::all());
    }

    /** @test */
    function child_is_scoped_when_accessed_from_belongs_to()
    {
        $car = Car::create();
        $vehicle = Vehicle::create();
        $passenger = Passenger::create(['name' => 'joe', 'vehicle_id' => $vehicle->id]);

        $this->assertNull($passenger->car);
        $this->assertNotNull($passenger->vehicle);

        $passenger->update(['vehicle_id' => $car->id]);

        $this->assertNull($passenger->fresh()->car);
        $this->assertNotNull($passenger->fresh()->vehicle);
        $this->assertTrue($passenger->fresh()->vehicle->is($car));
    }

    /** @test */
    function child_is_scoped_when_accessed_from_has_many()
    {
        $driver = Driver::create(['name' => 'joe']);
        Car::create(['driver_id' => $driver->id]);
        Vehicle::create(['driver_id' => $driver->id]);

        $this->assertCount(2, $driver->vehicles);
        $this->assertCount(1, $driver->cars);
    }
}
