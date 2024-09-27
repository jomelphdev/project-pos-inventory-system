<?php

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;

function create($class, $attributes = [], $times = 1)
{
    $factory = Factory::factoryForModel($class)->count($times)->create($attributes);

    if ($times == 1) 
    {
        return $factory->first();
    }

    return $factory;
}

function make($class, $attributes = [], $times = 1)
{
    $factory = Factory::factoryForModel($class)->count($times)->make($attributes);

    if ($times == 1) 
    {
        return $factory->first();
    }

    return $factory;
}

function getRandomRow($model, $relationColumn='organization_id')
{
    $all = $model->where($relationColumn, 1)->get();
    $ids = $all->pluck('id');

    if (count($ids) == 0)
    {
        return $model->factory();
    }
    
    return $ids[rand(0, count($ids) - 1)];
}

function getFirstOrNew($model)
{
    $first = $model->first();

    if (!$first)
    {
        return $model->factory();
    }

    return $first;
}