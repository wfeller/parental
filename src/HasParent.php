<?php

namespace WF\Parental;

use Illuminate\Support\Str;
use ReflectionClass;

trait HasParent
{
    protected static function bootHasParent() : void
    {
        static::creating(function ($model) {
            /** @var HasParent|HasChildren|\Illuminate\Database\Eloquent\Model $model */
            if ($model->parentHasHasChildrenTrait()) {
                $model->forceFill([$model->getInheritanceColumn() => $model->classToAlias(get_class($model))]);
            }
        });

        static::addGlobalScope('parental', function ($query) {
            $instance = new static;

            if ($instance->parentHasHasChildrenTrait()) {
                $query->where($instance->qualifyColumn($instance->getInheritanceColumn()), $instance->classToAlias(get_class($instance)));
            }
        });
    }

    public static function addGlobalScope($scope, \Closure $implementation = null)
    {
        $implementation = parent::addGlobalScope($scope, $implementation);
        $child = new static;

        if ($scope !== 'parental' && $child->parentHasHasChildrenTrait()) {
            $key = array_search($implementation, static::$globalScopes[static::class]);
            $key = $child->classToAlias(static::class).':'.$key;
            ParentScope::registerChild($child, $key, $implementation);
        }

        return $implementation;
    }

    public function parentHasHasChildrenTrait()
    {
        return $this->hasChildren ?? false;
    }

    public function getTable() : string
    {
        if (! isset($this->table)) {
            return str_replace('\\', '', Str::snake(Str::plural(class_basename($this->getParentClass()))));
        }

        return $this->table;
    }

    public function getForeignKey() : string
    {
        return Str::snake(class_basename($this->getParentClass())).'_'.$this->getKeyName();
    }

    public function belongsToMany($related, $table = null, $foreignPivotKey = null, $relatedPivotKey = null,
                                  $parentKey = null, $relatedKey = null, $relation = null)
    {
        $parentClass = $this->getParentClass();
        $method = $this->guessBelongsToManyRelation();

        if (! method_exists($parentClass, $method) || null !== $table) {
            return parent::belongsToMany($related, $table, $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relation);
        }

        return parent::belongsToMany($related, (new $parentClass)->joiningTable($related), $foreignPivotKey, $relatedPivotKey, $parentKey, $relatedKey, $relation);
    }

    public function getMorphClass()
    {
        if ($this->parentHasHasChildrenTrait()) {
            $parentClass = $this->getParentClass();
            return (new $parentClass)->getMorphClass();
        }

        return parent::getMorphClass();
    }

    protected function getParentClass() : string
    {
        static $parentClassName;

        return $parentClassName ?: $parentClassName = (new ReflectionClass($this))->getParentClass()->getName();
    }
}
