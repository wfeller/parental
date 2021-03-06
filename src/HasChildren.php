<?php

namespace WF\Parental;

use Illuminate\Support\Arr;

trait HasChildren
{
    private static $parentBootMethods;
    private static $discoveredChildren;

    protected $hasChildren = true;

    protected function initializeHasChildren() : void
    {
        if ($alias = $this->classToAlias(static::class)) {
            $this->setAttribute($this->getInheritanceColumn(), $alias);
        }

        if (! empty($this->getGuarded())) {
            $this->fillable(array_merge([$this->getInheritanceColumn()], $this->getFillable()));
        }
    }

    protected static function bootHasChildren() : void
    {
        if (static::class === self::class) {
            static::creating(function ($model) {
                /** @var HasChildren $model */
                if ($model->{$model->getInheritanceColumn()} === null && $parentAlias = $model->getParentAlias()) {
                    $model->{$model->getInheritanceColumn()} = $parentAlias;
                }
            });

            foreach ((new self)->getChildTypes() as $childClass) {
                // Just booting all the child classes to make sure their base global scopes get registered
                new $childClass;
            }

            static::addGlobalScope(new ParentScope);
        }
    }

    protected static function registerModelEvent($event, $callback) : void
    {
        parent::registerModelEvent($event, $callback);

        // We don't want to register the callbacks that happen in the boot method of the parent, as they'll be called
        // from the child's boot method as well.
        if (static::class === self::class && ! self::parentIsBooting()) {
            foreach ((new self)->getChildTypes() as $childClass) {
                if ($childClass !== self::class) {
                    /** @var \Illuminate\Database\Eloquent\Model $childClass */
                    $childClass::registerModelEvent($event, $callback);
                }
            }
        }
    }

    private static function parentIsBooting() : bool
    {
        if (! isset(self::$parentBootMethods)) {
            self::$parentBootMethods[] = 'boot';

            foreach (class_uses_recursive(self::class) as $trait) {
                self::$parentBootMethods[] = 'boot'.class_basename($trait);
            }

            self::$parentBootMethods = array_flip(self::$parentBootMethods);
        }

        // Limit to 32 as I don't think we need to go any deeper (even 10 is probably enough)
        foreach (debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 32) as $trace) {
            $class = isset($trace['class']) ? $trace['class'] : null;
            $function = isset($trace['function']) ? $trace['function'] : '';

            if ($class === self::class && isset(self::$parentBootMethods[$function])) {
                return true;
            }
        }

        return false;
    }

    public function newInstance($attributes = [], $exists = false)
    {
        $attributes = (array) $attributes;

        $model = isset($attributes[$this->getInheritanceColumn()])
            ? $this->getChildModel($attributes)
            : new static($attributes);

        $model->exists = $exists;

        $model->setConnection($this->getConnectionName());

        return $model;
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;

        $model = $this->newInstance(Arr::only($attributes, $this->getInheritanceColumn()), true);

        $model->setRawAttributes($attributes, true);

        $model->setConnection($connection ?: $this->getConnectionName());

        $model->fireModelEvent('retrieved', false);

        return $model;
    }

    public function getInheritanceColumn() : string
    {
        return property_exists($this, 'childColumn') ? $this->childColumn : 'type';
    }

    protected function getChildModel(array $attributes)
    {
        $className = $this->classFromAlias($attributes[$this->getInheritanceColumn()]);

        return new $className($attributes);
    }

    public function classFromAlias($aliasOrClass) : string
    {
        if ($aliasOrClass === $this->getParentAlias()) {
            return self::class;
        }

        $types = $this->getChildTypes();
        if (isset($types[$aliasOrClass])) {
            return $types[$aliasOrClass];
        }

        return $this instanceof DefaultsMissingAliasToParentClass ? self::class : $aliasOrClass;
    }

    public function classToAlias($className)
    {
        if ($className === self::class) {
            return $this->getParentAlias();
        }

        if (in_array($className, $this->getChildTypes())) {
            return array_search($className, $this->getChildTypes());
        }

        return $className;
    }

    public function getParentAlias()
    {
        return property_exists($this, 'parentType') ? $this->parentType : null;
    }

    public function getChildTypes() : array
    {
        return array_flip(array_merge(
            $this->getDiscoveredChildren(),
            array_flip(property_exists($this, 'childTypes') ? $this->childTypes : [])
        ));
    }

    private function getDiscoveredChildren() : array
    {
        if (! isset(self::$discoveredChildren)) {
            self::$discoveredChildren = file_exists(config('parental.discovered_children_path'))
                ? Arr::get(require config('parental.discovered_children_path'), self::class, [])
                : [];
        }

        return self::$discoveredChildren;
    }
}
