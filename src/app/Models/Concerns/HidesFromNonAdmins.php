<?php

namespace App\Models\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

trait HidesFromNonAdmins
{
    /**
     * Add a global scope that only applies to visitors who are not admins.
     *
     * Who is logged in is checked each time a query runs, not once when the
     * model first boots, so it stays right when the logged-in user changes
     * (queue workers, tests, logging in mid-request).
     */
    protected static function addNonAdminScope(string $name, Closure $apply): void
    {
        static::addGlobalScope($name, function (Builder $builder) use ($apply) {
            $user = Auth::user();
            if ($user && $user->getAdmin()) {
                return;
            }
            $apply($builder);
        });
    }
}
