<?php

namespace App\Repositories\Interface;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface BaseRepositoryInterface
 *
 * @package App\Repositories\Interface
 */
interface IBaseRepository
{
    public function getAll(array $fields = null, array $condition = null, string $sort = 'id');

    public function create(array $data);

    public function insert(array $data);

    public function update(array $data, int|string $id): mixed;

    public function updateOrCreate(array $data, array $condition = []);

    public function updateCondition(array $data, array $condition = []);

    public function destroy(int|string $id);

    public function find(int|string $id, array $fields = null);

    public function findOrBad(int|string $id);

    public function filter(array $condition);
    public function filterOne(array $condition): ?Model;

    public function paginated($limit = 10, $condition = null, $sort = null, $direction = null);

    public function getAllAttributes(array $attributes): array;
}
