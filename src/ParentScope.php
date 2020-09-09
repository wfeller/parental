<?php

namespace WF\Parental;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Arr;

class ParentScope implements Scope
{
    protected static $registered = [];

    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model|\WF\Parental\HasChildren $parent
     * @return void
     */
    public function apply(Builder $builder, Model $parent) : void
    {
        if (! isset(static::$registered[get_class($parent)])) {
            return;
        }

        $builder->where(function (Builder $builder) use ($parent) {
            $inheritanceColumn = $parent->qualifyColumn($parent->getInheritanceColumn());

            $existingImplementations = $parent->getGlobalScopes();

            foreach (static::$registered[get_class($parent)] as $alias => $implementations) {
                $builder->orWhere(function (Builder $builder) use ($alias, $inheritanceColumn, $implementations, $existingImplementations) {
                    $builder->where($inheritanceColumn, $alias);

                    foreach ($implementations as $key => $implementation) {
                        if (Arr::has($existingImplementations, str_replace($alias.':', '', $key))) {
                            continue;
                        }

                        $this->applyImplementation($builder, $implementation);
                    }
                });
            }

            if ($parent instanceof DefaultsMissingAliasToParentClass) {
                $builder->orWhere(function (Builder $builder) use ($parent, $inheritanceColumn) {
                    $builder
                        ->orWhereNotIn($inheritanceColumn, array_keys(static::$registered[get_class($parent)]))
                        ->orWhereNull($inheritanceColumn);
                });
            } else {
                $builder->orWhere(function (Builder $builder) use ($parent, $inheritanceColumn) {
                    $missingChildren = array_diff_key($parent->getChildTypes(), static::$registered[get_class($parent)]);
                    $builder
                        ->orWhereIn($inheritanceColumn, array_keys($missingChildren))
                        ->orWhere($inheritanceColumn, $parent->getParentAlias())
                        ->orWhereNull($inheritanceColumn);
                });
            }
        });
    }

    private function applyImplementation(Builder $builder, $implementation) : void
    {
        $builder->where(function (Builder $builder) use ($implementation) {
            if ($implementation instanceof Closure) {
                ($implementation)($builder);
            } elseif ($implementation instanceof Scope) {
                $implementation->apply($builder, $builder->getModel());
            }
        });
    }

    /**
     * @param \Illuminate\Database\Eloquent\Model|\WF\Parental\HasParent|\WF\Parental\HasChildren $child
     * @param string $key
     * @param $implementation
     */
    public static function registerChild(Model $child, string $key, $implementation) : void
    {
        static::$registered[get_parent_class($child)][$child->classToAlias(get_class($child))][$key] = $implementation;
    }
}
