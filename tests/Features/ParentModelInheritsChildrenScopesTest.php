<?php

namespace WF\Parental\Tests\Features;

use Illuminate\Foundation\Testing\WithFaker;
use WF\Parental\DefaultsMissingAliasToParentClass;
use WF\Parental\ParentScope;
use WF\Parental\Tests\Models\Car;
use WF\Parental\Tests\Models\InternationalTrip;
use WF\Parental\Tests\Models\LocalTrip;
use WF\Parental\Tests\Models\Plane;
use WF\Parental\Tests\Models\Train;
use WF\Parental\Tests\Models\Trip;
use WF\Parental\Tests\Models\Vehicle;
use WF\Parental\Tests\TestCase;

class ParentModelInheritsChildrenScopesTest extends TestCase
{
    use WithFaker;

    /** @test */
    public function simple_scope_inheritance_check()
    {
        $this->createTenTrips();

        $this->assertEquals(10, Trip::query()->count());
        $this->assertCount(2, (new Trip)->getGlobalScopes());
    }

    /** @test */
    public function global_scopes_can_be_added_on_the_fly()
    {
        $this->createTenTrips();

        LocalTrip::addGlobalScope(function ($query) {
            // this doesn't actually do anything to the query
            $query->whereNotNull((new LocalTrip)->getInheritanceColumn());
        });

        $this->assertEquals(10, Trip::query()->count());
        $this->assertCount(2, (new Trip)->getGlobalScopes());
        $this->assertCount(3, (new LocalTrip)->getGlobalScopes());
    }

    /** @test */
    public function child_scopes_apply_on_parent_queries()
    {
        $this->createTenTrips();

        LocalTrip::addGlobalScope(function ($q) {
            $q->whereKey(9);
        });

        $this->assertEquals(8, Trip::query()->count());
        $this->assertCount(2, (new Trip)->getGlobalScopes());
        $this->assertCount(3, (new LocalTrip)->getGlobalScopes());

        $localTrip = LocalTrip::query()->first();

        $this->assertEquals(9, $localTrip->getKey());
    }

    /** @test */
    public function can_modify_query()
    {
        $this->createTenTrips();

        LocalTrip::addGlobalScope(function ($q) {
            $q->whereKey(9);
        });

        $trips = Trip::query()->where('duration', '>=', 3)->get();

        $this->assertCount(3, $trips);
        $this->assertInstanceOf(Trip::class, $trips->shift());
        $this->assertInstanceOf(Trip::class, $trips->shift());
        $this->assertInstanceOf(InternationalTrip::class, $trips->shift());
    }

    /** @test */
    public function parent_scope_allows_all_types_if_types_default_to_parent_class()
    {
       $this->ensureVehicleHasParentScopeActive();

        $this->createTenVehicles();

        $this->assertInstanceOf(DefaultsMissingAliasToParentClass::class, new Vehicle);

        $this->assertSame(10, Vehicle::query()->count());
    }

    private function createTenTrips()
    {
        Trip::query()->create(['duration' => 1]);
        Trip::query()->create(['duration' => 2]);
        Trip::query()->create(['duration' => 3]);
        Trip::query()->create(['duration' => 4]);

        InternationalTrip::query()->create(['duration' => 1]);
        InternationalTrip::query()->create(['duration' => 2]);
        InternationalTrip::query()->create(['duration' => 3]);

        LocalTrip::query()->create(['duration' => 1]);
        LocalTrip::query()->create(['duration' => 2]);
        LocalTrip::query()->create(['duration' => 3]);
    }

    private function ensureVehicleHasParentScopeActive()
    {
        Car::addGlobalScope('test-scope', function ($q) {
            $q->whereKeyNot(0);
        });

        $scopes = array_filter((new Vehicle)->getGlobalScopes(), function ($scope) {
            return $scope instanceof ParentScope;
        });

        $this->assertCount(1, $scopes);
    }

    private function createTenVehicles()
    {
        Car::query()->create();
        Car::query()->create();

        Plane::query()->create();
        Plane::query()->create();

        Train::query()->create();

        Vehicle::query()->create();
        Vehicle::query()->create();

        $inheritanceColumn = (new Vehicle)->getInheritanceColumn();

        Vehicle::query()->create([$inheritanceColumn => $this->faker->jobTitle]);
        Vehicle::query()->create([$inheritanceColumn => $this->faker->jobTitle]);
        Vehicle::query()->create([$inheritanceColumn => $this->faker->jobTitle]);
    }
}
