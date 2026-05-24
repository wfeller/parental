<?php

namespace WF\Parental\Tests\Features;

use PHPUnit\Framework\Attributes\Test;
use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\InternationalTrip;
use WF\Parental\Tests\Models\LocalTrip;
use WF\Parental\Tests\Models\Plane;
use WF\Parental\Tests\Models\Trip;
use WF\Parental\Tests\Models\Vehicle;
use WF\Parental\Tests\TestCase;

class EdgeCasesTest extends TestCase
{
    #[Test]
    public function multiple_sti_parents_dont_contaminate_each_other()
    {
        Car::create();
        Plane::create();
        InternationalTrip::query()->create(['duration' => 5]);
        LocalTrip::query()->create(['duration' => 10]);

        $vehicles = Vehicle::all();
        $this->assertCount(2, $vehicles);
        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Plane::class, $vehicles[1]);

        $trips = Trip::all();
        $this->assertCount(2, $trips);
        $this->assertInstanceOf(InternationalTrip::class, $trips->first(fn ($t) => $t instanceof InternationalTrip));
        $this->assertInstanceOf(LocalTrip::class, $trips->first(fn ($t) => $t instanceof LocalTrip));
    }

    #[Test]
    public function parent_query_before_any_child_instantiation_returns_empty()
    {
        $vehicles = Vehicle::all();
        $this->assertCount(0, $vehicles);

        $trips = Trip::all();
        $this->assertCount(0, $trips);
    }

    #[Test]
    public function parent_query_after_child_creation_returns_correct_types()
    {
        Vehicle::all();

        Car::create();
        Plane::create();

        $vehicles = Vehicle::all();
        $this->assertCount(2, $vehicles);
        $this->assertInstanceOf(Car::class, $vehicles[0]);
        $this->assertInstanceOf(Plane::class, $vehicles[1]);
    }

    #[Test]
    public function runtime_scope_added_after_initial_query_takes_effect()
    {
        InternationalTrip::query()->create(['duration' => 1]);
        LocalTrip::query()->create(['duration' => 2]);
        Trip::query()->create(['duration' => 3]);

        $this->assertCount(3, Trip::all());

        LocalTrip::addGlobalScope('exclude', function ($q) {
            $q->whereKeyNot(2);
        });

        $this->assertCount(2, Trip::all());
    }

    #[Test]
    public function classFromAlias_returns_parent_class_for_null()
    {
        $vehicle = new Vehicle;
        $this->assertEquals(Vehicle::class, $vehicle->classFromAlias(null));

        $trip = new Trip;
        $this->assertEquals(Trip::class, $trip->classFromAlias(null));
    }

    #[Test]
    public function child_scopes_read_fresh_after_runtime_addition()
    {
        Car::create();
        Plane::create();
        Vehicle::create();

        $this->assertCount(3, Vehicle::all());

        Car::addGlobalScope('active', function ($q) {
            $q->whereNotNull('seats');
        });

        $this->assertCount(2, Vehicle::all());
    }

    #[Test]
    public function parent_event_forwarding_does_not_cause_double_registration()
    {
        $callCount = 0;
        Vehicle::created(function () use (&$callCount) {
            $callCount++;
        });

        Car::create();

        $this->assertEquals(1, $callCount);
    }
}
