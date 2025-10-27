<?php

namespace App\Repositories;

use App\Helpers\LogHelperService;
use Exception;
use App\Repositories\Interface\IBaseRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Class BaseRepository
 *
 * @package App\Repositories
 */
abstract class BaseRepository implements IBaseRepository
{
    /**
     * @var LogHelperService
     */
    protected LogHelperService $logger;

    /** @var array $relations */
    public static array $relations = [];
    /**
     * model property on class instances
     */
    public Model $model;

    /**
     * Constructor to bind model to repo
     *
     * @param Model $model
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        $this->logger = app(LogHelperService::class);
    }

    /**
     * Get the associated model
     */
    public function getModel(): Model
    {
        return $this->model;
    }

    /**
     * Set the associated model
     *
     * @param $model
     *
     * @return BaseRepository
     */
    public function setModel($model): BaseRepository
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @return array
     */
    public function getGuarded(): array
    {
        return $this->model->getGuarded();
    }

    /**
     * Get all instances of model
     *
     * @param null|array $fields
     * @param array|null $condition
     * @param string $sort
     *
     * @return Collection
     */
    public function getAll(array $fields = null, array $condition = null, string $sort = 'id'): Collection
    {
        if (!empty($condition)) {
            return $this->model->when(
                !empty($condition),
                function ($q) use ($condition) {
                    return $q->where($condition);
                }
            )
                ->orderByDesc($sort)->get();
        }
        return $this->model->all($fields ?? ['*'])->sortByDesc($sort);
    }

    /**
     * get multiple records in the database
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function getMany(array $ids): mixed
    {
        return $this->model->whereIn('id', $ids)->get();
    }

    /**
     * delete multiple records in the database
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function deleteMany(array $ids): mixed
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * get multiple records withTrashed in the database
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function getManyWithTrashed(array $ids): mixed
    {
        return $this->model->withTrashed()->whereIn('id', $ids)->get();
    }

    /**
     * forceDelete multiple records in the database
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function forceDeleteMany(array $ids): mixed
    {
        return $this->model->whereIn('id', $ids)->onlyTrashed()->forceDelete();
    }

    /**
     * restore multiple records in the database
     *
     * @param array $ids
     *
     * @return mixed
     */
    public function restoreMany(array $ids): mixed
    {
        return $this->model->whereIn('id', $ids)->onlyTrashed()->restore();
    }

    /**
     * Count all instances of model
     *
     * @param array|null $condition
     *
     * @return mixed
     */
    public function countAll(array $condition = null): mixed
    {
        return $this->model->when(
            !empty($condition),
            function ($q) use ($condition) {
                return $q->where($condition);
            }
        )->count();
    }

    /**
     * create a new record in the database
     *
     * @param array $data
     *
     * @return mixed
     * @throws \Exception
     */
    public function create(array $data): mixed
    {
        return tap(
            $this->model->create($data),
            function ($instance) {
                if (!$instance) {
                    throw new Exception(__('message.actions.create_failed'));
                }
            }
        )->fresh(static::$relations);
    }

    /**
     * @param $attributes
     *
     * @return bool
     */
    public function insertMany($attributes): bool
    {
        return DB::table($this->model->getTable())->insert($attributes);
    }

    /**
     * create a new record in the database
     *
     * @param array $data
     *
     * @return mixed
     */
    public function insert(array $data): mixed
    {
        return $this->model->insert($data);
    }

    /**
     * update or create a new record in the database
     *
     * @param array $data
     * @param array $condition
     *
     * @return mixed
     */
    public function updateOrCreate(array $data, array $condition = []): mixed
    {
        return $this->model->updateOrCreate($condition, $data);
    }

    /**
     * first or create a new record in the database
     *
     * @param array $data
     * @param array $condition
     *
     * @return mixed
     */
    public function firstOrCreate(array $data, array $condition = []): mixed
    {
        return $this->model->firstOrCreate($condition, $data);
    }

    /**
     * update record in the database
     *
     * @param array $data
     * @param array $condition
     *
     * @return mixed
     */
    public function updateCondition(array $data, array $condition = []): mixed
    {
        return $this->model->where($condition)->update($data);
    }

    /**
     * update record in the database
     *
     * @param array $data
     * @param int|string|null $id
     *
     * @return mixed
     * @throws Exception
     */
    public function update(array $data, int|string $id = null): mixed
    {
        $model = tap(
            $this->find($id),
            function ($instance) use ($data) {
                if (!$instance->update($data)) {
                    throw new Exception(__('message.actions.update_failed'));
                }
            }
        );
        return $model->load(static::$relations);
    }

    /**
     * show the record with the given id
     *
     * @param int|string $id
     * @param array|null $fields
     *
     * @return mixed
     */
    public function find(int|string $id, array $fields = null): mixed
    {
        if (!empty($fields)) {
            return $this->model->find($id, $fields);
        }
        return $this->model->find($id);
    }

    /**
     * @param int|string $id
     *
     * @return mixed
     */
    public function findWithTrashed(int|string $id): mixed
    {
        return $this->model->withTrashed()->find($id);
    }

    /**
     * update record in the database
     *
     * @param array $condition
     * @param       $column
     * @param       $num
     *
     * @return mixed
     */
    public function increment(array $condition, $column, $num): mixed
    {
        return $this->model->where($condition)->increment($column, $num);
    }

    /**
     * update record in the database
     *
     * @param array $condition
     * @param       $column
     * @param       $num
     *
     * @return mixed
     */
    public function decrement(array $condition, $column, $num): mixed
    {
        return $this->model->where($condition)->decrement($column, $num);
    }

    /**
     * remove record from the database
     *
     * @param int|string|array $id
     *
     * @return bool|null
     */
    public function destroy(int|string|array $id): ?bool
    {
        return $this->model->destroy($id);
    }

    /**
     * restore record softDelete from the database
     *
     * @param int|string $id
     *
     * @return bool|null
     */
    public function restore(int|string $id): ?bool
    {
        $data = $this->model->onlyTrashed()->find($id);
        if (empty($data)) {
            return $data;
        }

        return $data->restore();
    }

    /**
     * forceDelete record from the database
     *
     * @param int|string $id
     *
     * @return bool|null
     * @throws Exception
     */
    public function forceDelete(int|string $id): ?bool
    {
        $data = $this->model->onlyTrashed()->find($id);
        if (empty($data)) {
            return $data;
        }

        return $data->forceDelete();
    }

    /**
     * Check if model exists
     *
     * @param int|string $id
     *
     * @return mixed
     */
    public function exists(int|string $id): mixed
    {
        return $this->model->exists($id);
    }

    /**
     * Get one
     *
     * @param int|string $id
     *
     * @return mixed
     * @throws Exception
     */
    public function findOrBad(int|string $id): mixed
    {
        try {
            $result = $this->model->findOrFail($id);
        } catch (Exception $exception) {
            throw new Exception($exception->getMessage());
        }
        return $result;
    }

    /**
     * @param array $condition
     *
     * @return mixed
     */
    public function filter(array $condition): mixed
    {
        return $this->model->where($condition)->get();
    }

    /**
     * @param array $condition
     *
     * @return Model|null
     */
    public function filterOne(array $condition): ?Model
    {
        return $this->model->where($condition)->first();
    }

    /**
     * Eager load database relationships
     *
     * @param array $relations
     *
     * @return Builder
     */
    public function with(array $relations): Builder
    {
        return $this->model->with($relations);
    }

    /**
     * The pluck method retrieves all of the values for a given key
     *
     * @param      $value
     * @param      $key
     * @param null $condition
     *
     * @return mixed
     */
    public function pluck($value, $key, $condition = null): mixed
    {
        return $this->model
            ->when(
                !empty($condition),
                function ($q) use ($condition) {
                    return $q->where($condition);
                }
            )
            ->pluck($value, $key);
    }

    /**
     *  Get all instances of model with paginate
     *
     * @param int $limit
     * @param null $condition
     * @param null $sort
     * @param null $direction
     *
     * @return mixed
     */
    public function paginated($limit = 10, $condition = null, $sort = null, $direction = null): mixed
    {
        return $this->model
            ->when(
                !empty($condition),
                function ($q) use ($condition) {
                    return $q->where($condition);
                }
            )
            ->when(
                !empty($sort),
                function ($query) use ($sort, $direction) {
                    return $query->orderBy($sort, $direction);
                }
            ) // sort
            ->paginate($limit);
    }

    /**
     * @param array $attributes
     *
     * @return array
     */
    public function getAllAttributes(array $attributes): array
    {
        $columns = [];
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->getFillable())) {
                $columns[$key] = $value;
            }
        }
        return $columns;
    }

    /**
     * @return array
     */
    public function getFillable(): array
    {
        return $this->model->getFillable();
    }

    /**
     * beginTransaction
     *
     * @return void
     */
    public function beginTransaction(): void
    {
        DB::beginTransaction();
    }

    /**
     * commit
     *
     * @return void
     */
    public function commit(): void
    {
        DB::commit();
    }

    /**
     * rollBack
     *
     * @return void
     */
    public function rollBack(): void
    {
        DB::rollBack();
    }
}
