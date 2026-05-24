<?php

namespace WF\Parental;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Arr;

class ParentScope implements Scope
{
    /**
     * @param \Illuminate\Database\Eloquent\Builder $builder
     * @param \Illuminate\Database\Eloquent\Model|\WF\Parental\HasChildren $parent
     * @return void
     */
    public function apply(Builder $builder, Model $parent) : void
    {
        $allScopes = Model::getAllGlobalScopes();

        foreach ($parent->getChildTypes() as $childClass) {
            if (! isset($allScopes[$childClass])) {
                new $childClass;
            }
        }

        $allScopes = Model::getAllGlobalScopes();

        $applicableChildScopes = [];

        foreach ($parent->getChildTypes() as $childClass) {
            foreach ($allScopes[$childClass] ?? [] as $key => $scope) {
                if ($key === 'parental') {
                    continue;
                }
                $applicableChildScopes[(new $childClass)->classToAlias($childClass)][$key] = $scope;
            }
        }

        if (empty($applicableChildScopes)) {
            return;
        }

        $builder->where(function (Builder $builder) use ($parent, $applicableChildScopes) {
            $inheritanceColumn = $parent->qualifyColumn($parent->getInheritanceColumn());

            $existingImplementations = $parent->getGlobalScopes();

            foreach ($applicableChildScopes as $alias => $implementations) {
                $builder->orWhere(function (Builder $builder) use ($alias, $inheritanceColumn, $implementations, $existingImplementations) {
                    $builder->where($inheritanceColumn, $alias);

                    foreach ($implementations as $key => $implementation) {
                        if (Arr::has($existingImplementations, $key)) {
                            continue;
                        }

                        $this->applyImplementation($builder, $implementation);
                    }
                });
            }

            if ($parent instanceof DefaultsMissingAliasToParentClass) {
                $builder->orWhere(function (Builder $builder) use ($inheritanceColumn, $applicableChildScopes) {
                    $builder
                        ->orWhereNotIn($inheritanceColumn, array_keys($applicableChildScopes))
                        ->orWhereNull($inheritanceColumn);
                });
            } else {
                $builder->orWhere(function (Builder $builder) use ($parent, $inheritanceColumn, $applicableChildScopes) {
                    $missingChildren = array_diff_key($parent->getChildTypes(), $applicableChildScopes);
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
}
