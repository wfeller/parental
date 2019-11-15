<?php

namespace WF\Parental\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WF\Parental\HasParent;

class InternationalTrip extends Trip
{
    use HasParent;

    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope(function ($query) {
            $query->whereNotNull('id');
        });
    }
}
