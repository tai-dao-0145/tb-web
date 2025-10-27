<?php

namespace App\Models\Scopes;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class SelectUserColumnsScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        /** @var User $model */
        $builder->addSelect(
            'users.id',
            'users.role_id',
            'users.full_name',
            'users.display_name',
            'users.email',
            'users.phone',
            'users.gender',
            'users.branch_id',
            $model->selectWorkHour(),
        );
    }
}
