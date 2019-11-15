<?php

namespace WF\Parental\Tests\Observers;

use WF\Parental\Tests\Models\Car;

class CarObserver
{
    public function creating(Car $car)
    {
        $car->driver_id = 2;
    }
}
