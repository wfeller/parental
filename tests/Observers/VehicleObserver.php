<?php

namespace WF\Parental\Tests\Observers;

use WF\Parental\Tests\Models\Vehicle;

class VehicleObserver
{
    public function creating(Vehicle $vehicle)
    {
        $vehicle->driver_id = 1;
    }
}
