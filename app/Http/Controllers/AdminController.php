<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\User;
use Inertia\Inertia;

class AdminController extends Controller
{
    /** @spec-link [[ui_admin_dashboard]] */
    public function index()
    {
        return Inertia::render('Admin/Dashboard');
    }

    /** @spec-link [[uc_admin_user_management]] */
    public function users()
    {
        // Select only non-sensitive fields to protect UUIDs (though admins see some)
        // We use account_name as the primary handle
        return Inertia::render('Admin/UserManagement', [
            'users' => User::withTrashed()
                           ->select('id', 'account_name', 'email', 'role', 'deleted_at', 'created_at')
                           ->get()
        ]);
    }

    /** @spec-link [[uc_admin_user_management]] */
    public function anonymize(User $user)
    {
        $user->anonymize();
        return back()->with('message', "User {$user->account_name} has been anonymized and soft-deleted.");
    }

    /** @spec-link [[uc_admin_user_management]] */
    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('message', "User {$user->account_name} has been soft-deleted.");
    }
}
