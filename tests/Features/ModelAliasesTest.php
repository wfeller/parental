<?php

namespace WF\Parental\Tests\Features;

use PHPUnit\Framework\Attributes\Test;
use WF\Parental\DefaultsMissingAliasToParentClass;
use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\InternationalTrip;
use WF\Parental\Tests\Models\LocalTrip;
use WF\Parental\Tests\Models\Plane;
use WF\Parental\Tests\Models\Trip;
use WF\Parental\Tests\Models\Vehicle;
use WF\Parental\Tests\TestCase;

class ModelAliasesTest extends TestCase
{
    #[Test]
    public function parent_alias_is_null_if_not_defined()
    {
        $vehicle = Vehicle::create();
        $this->assertNull($vehicle->type);
    }

    #[Test]
    public function parent_alias_is_set_on_creating_if_defined()
    {
        /** @var Trip $trip */
        $trip = Trip::create();
        $this->assertEquals('trip', $trip->{$trip->getInheritanceColumn()});
    }

    #[Test]
    public function alias_doesnt_get_overridden_if_already_defined()
    {
        $car = Vehicle::create(['type' => 'car']);
        $this->assertInstanceOf(Car::class, $car);
        $this->assertEquals('car', $car->type);

        $carTwo = new Vehicle;
        $carTwo->type = 'car';
        $carTwo->save();
        $this->assertInstanceOf(Vehicle::class, $car);
        $this->assertEquals('car', $car->type);
    }

    #[Test]
    public function parent_alias_is_set_on_new_models_if_defined()
    {
        $vehicle = new Vehicle;
        $this->assertNull($vehicle->{$vehicle->getInheritanceColumn()});

        $trip = new Trip;
        $this->assertEquals('trip', $trip->{$trip->getInheritanceColumn()});
    }

    #[Test]
    public function aliases_are_set_on_new_child_models()
    {
        $this->assertEquals('car', (new Car)->type);
        $this->assertEquals(Plane::class, (new Plane)->type);
    }

    #[Test]
    public function it_defaults_to_the_parent_class_if_child_class_alias_is_not_defined()
    {
        $this->assertInstanceOf(DefaultsMissingAliasToParentClass::class, new Vehicle);

        $bike = Vehicle::query()->create(['type' => 'bike']);

        $this->assertSame('bike', $bike->type);

        $copy = $bike->fresh();

        $this->assertSame(Vehicle::class, get_class($bike));
        $this->assertSame(Vehicle::class, get_class($copy));
    }

    #[Test]
    public function it_throws_if_child_class_alias_not_defined_and_interface_not_implemented()
    {
        $this->assertNotInstanceOf(DefaultsMissingAliasToParentClass::class, new Trip);

        $this->expectException(\Throwable::class);
        $this->expectExceptionMessage('Class "invalid-trip-type" not found');

        Trip::query()->create([(new Trip)->getInheritanceColumn() => 'invalid-trip-type']);
    }

    #[Test]
    public function numeric_keyed_child_types_use_fqcn_as_alias()
    {
        $trip = InternationalTrip::create();
        $column = $trip->getInheritanceColumn();

        $this->assertSame(InternationalTrip::class, $trip->$column);

        $retrieved = Trip::first();
        $this->assertInstanceOf(InternationalTrip::class, $retrieved);

        $localTrip = LocalTrip::create();
        $this->assertSame(LocalTrip::class, $localTrip->$column);
    }
}
