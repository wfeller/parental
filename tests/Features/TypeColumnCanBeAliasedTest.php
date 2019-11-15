<?php

namespace WF\Parental\Tests\Features;

use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\Plane;
use WF\Parental\Tests\Models\Vehicle;
use WF\Parental\Tests\TestCase;

class TypeColumnCanBeAliasedTest extends TestCase
{
    /** @test */
    function type_column_values_can_accept_type_aliases()
    {
        Car::create(['type' => 'car']);
        Plane::create(['type' => Plane::class]);

        $vehicles = Vehicle::all();

        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Plane::class, $vehicles[1]);
    }

    /** @test */
    function type_aliases_are_set_on_creation()
    {
        $car = Car::create();

        $this->assertEquals('car', $car->fresh()->type);
    }
}
