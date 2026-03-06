<?php

namespace App\Http\Controllers\Admin\Concerns;

use App\Models\Admin;
use Illuminate\Http\Request;

trait ResolvesCurrentAdmin
{
    /**
     * Resolve authenticated admin from middleware-bound request attribute.
     */
    protected function currentAdmin(?Request $request = null): Admin
    {
        $request ??= request();
        $admin = $request->attributes->get('admin');

        if (!$admin instanceof Admin) {
            abort(401, 'Admin tidak terautentikasi.');
        }

        return $admin;
    }

    /**
     * Resolve authenticated admin ID.
     */
    protected function currentAdminId(?Request $request = null): int
    {
        return (int) $this->currentAdmin($request)->id;
    }
}
