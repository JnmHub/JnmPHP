<?php

namespace Kernel\Database\Traits;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

trait HasCrud
{
    /**
     * 根据主键ID获取单个模型实例。
     */
    public static function getById(int|string $id, array $columns = ['*']): ?static
    {
        return static::find($id, $columns);
    }

    /**
     * 获取所有模型实例的列表。
     */
    public static function list(array $columns = ['*']): Collection
    {
        return static::all($columns);
    }

    /**
     * 获取分页后的模型实例列表。
     */
    public static function page(int $perPage = 15, array $columns = ['*']): LengthAwarePaginator
    {
        return static::paginate($perPage, $columns);
    }

    /**
     * 快速创建一条新记录。
     */
    public static function quickCreate(array $attributes): static
    {
        return static::create($attributes);
    }

    /**
     * 根据主键ID快速更新一条记录。
     */
    public static function quickUpdateById(int|string $id, array $values): bool
    {
        $model = static::find($id);
        return $model ? $model->update($values) : false;
    }

    /**
     * 根据主键ID快速删除一条或多条记录。
     */
    public static function deleteById(int|string|array $ids): int
    {
        return static::destroy($ids);
    }
}