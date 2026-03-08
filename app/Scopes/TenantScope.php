<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        // If there is an authenticated user, filter by their tenant_id
        // Note: Super Admins will bypass this (handled in Model or Middleware)
        if (Auth::check()) {
            $user = Auth::user();
            
            // Assuming tenant_id is on the User model
            if ($user->tenant_id) {
                $builder->where($model->getTable() . '.tenant_id', $user->tenant_id);
            }
        }
    }
}
