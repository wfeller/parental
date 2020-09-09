<?php

namespace WF\Parental\Tests\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use WF\Parental\HasParent;

class Car extends Vehicle
{
    use HasParent;
    use HasFactory;
}
