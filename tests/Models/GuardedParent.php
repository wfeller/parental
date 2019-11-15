<?php

namespace WF\Parental\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use WF\Parental\HasChildren;

class GuardedParent extends Model
{
    use HasChildren;

    protected $fillable = [];

    protected $childTypes = ['child' => GuardedChild::class];
}
