<?php

namespace WF\Parental\Tests\Features;

use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\Driver;
use WF\Parental\Tests\Models\GuardedChild;
use WF\Parental\Tests\Models\GuardedParent;
use WF\Parental\Tests\Models\InternationalTrip;
use WF\Parental\Tests\Models\Passenger;
use WF\Parental\Tests\Models\Plane;
use WF\Parental\Tests\Models\Trip;
use WF\Parental\Tests\Models\Vehicle;
use WF\Parental\Tests\TestCase;

class ParentsAreAwareOfChildrenTest extends TestCase
{
    /** @test */
    public function something()
    {
        $v = new GuardedParent;
        $this->assertInstanceOf(GuardedParent::class, $v->newFromBuilder(['id' => 123]));
        $this->assertInstanceOf(GuardedChild::class, $v->newFromBuilder(['id' => 123, 'type' => 'child']));
    }

    /** @test */
    public function it_correctly_sets_the_type_when_creating_a_child_instance_even_if_given_type_is_wrong()
    {
        $wrongType = Car::query()->create(['type' => "I Like Making Mistakes 🤪"])->fresh();
        $when_i_grow_up_ill_be_an_airplane = Car::create(['type' => Plane::class])->fresh();

        $inheritanceColumn = (new Trip)->getInheritanceColumn();
        $someKindOfTrip = InternationalTrip::query()->create([$inheritanceColumn => 'will be overridden'])->fresh();

        $this->assertInstanceOf(Car::class, $wrongType);
        $this->assertInstanceOf(Car::class, $when_i_grow_up_ill_be_an_airplane);
        $this->assertSame(InternationalTrip::class, $someKindOfTrip->{$inheritanceColumn});
        $this->assertInstanceOf(InternationalTrip::class, $someKindOfTrip);
    }

    /** @test */
    function vehicle_all_method_returns_child_models()
    {
        Car::create();
        Plane::create();

        $vehicles = Vehicle::all();

        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Plane::class, $vehicles[1]);
    }

    /** @test */
    function type_column_values_can_accept_type_aliases()
    {
        // Looks for "childTypes" property on Vehicle class.
        Car::create(['type' => 'car']);
        Plane::create(['type' => Plane::class]);

        $vehicles = Vehicle::all();

        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Plane::class, $vehicles[1]);
    }

    /** @test */
    function vehicle_query_builder_get_method_returns_child_models()
    {
        Car::create();
        Plane::create();
        Vehicle::create();

        $vehicles = Vehicle::query()->get();

        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Plane::class, $vehicles[1]);
        $this->assertInstanceOf(Vehicle::class, $vehicles[2]);
    }

    /** @test */
    function has_many_returns_child_models()
    {
        $driver = Driver::create(['name' => 'Joe']);
        Car::create(['driver_id' => $driver->id]);

        $vehicleA = $driver->vehicles()->first();
        $vehicleB = $driver->vehicles->first();

        $this->assertInstanceOf(Car::class, $vehicleA);
        $this->assertInstanceOf(Car::class, $vehicleB);
    }

    /** @test */
    function belongs_to_returns_child_models()
    {
        $car = Car::create();
        $passenger = Passenger::create([
            'name' => 'Joe',
            'vehicle_id' => $car->id,
        ]);

        $vehicle = $passenger->vehicle;

        $this->assertInstanceOf(Car::class, $vehicle);
    }

    /** @test */
    function many_to_many_returns_child_models()
    {
        $car = Car::create();
        $trip = $car->trips()->create([]);

        $vehicleA = $trip->vehicles()->first();
        $vehicleB = $trip->vehicles->first();

        $this->assertInstanceOf(Car::class, $vehicleA);
        $this->assertInstanceOf(Car::class, $vehicleB);
    }
}
