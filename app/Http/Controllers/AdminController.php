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
    public function users(Request $request)
    {
        $query = User::withTrashed()
                       ->select('id', 'account_name', 'email', 'role', 'deleted_at', 'created_at', 'updated_at');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('account_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('updated_at', 'desc')
                       ->limit(51)
                       ->get();

        $hasMore = $users->count() > 50;
        if ($hasMore) {
            $users->pop();
        }

        return Inertia::render('Admin/UserManagement', [
            'users' => $users,
            'initialHasMore' => $hasMore,
            'initialNextCursor' => $hasMore ? $users->last()->updated_at->toISOString() : null
        ]);
    }

    /** @spec-link [[uc_admin_history_management]] */
    public function history()
    {
        return Inertia::render('Admin/History');
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
