<?php

namespace App\Models\Scopes;

use App\Support\WorkspaceContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class WorkspaceScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(WorkspaceContext::class);

        if ($context->bypass()) {
            return;
        }

        $column = $model->getTable().'.workspace_id';
        $id = $context->id();

        if ($id) {
            $builder->where($column, $id);
        } else {
            $builder->whereRaw('1 = 0');
        }
    }
}
