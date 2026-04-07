<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index()
    {
        $users = User::with('roles')
            ->whereHas('roles', function ($query) {
                $query->whereIn('name', ['admin', 'cashier', 'supervisor', 'manager', 'superadmin']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.index', compact('users'));
    }

    /**
     * Store a newly created user.
     * Note: Supervisor PINs are now generated dynamically based on time, no need to store.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,cashier,supervisor,manager,superadmin'],
        ]);

        // Check if role exists, if not create it
        $roleExists = Role::where('name', $request->role)->first();
        if (!$roleExists) {
            Role::create(['name' => $request->role, 'guard_name' => 'web']);
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!');
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,cashier,supervisor,manager,superadmin'],
            'is_active' => ['boolean'],
        ]);

        $newRole = $request->role;

        // Check if role exists, if not create it
        $roleExists = Role::where('name', $newRole)->first();
        if (!$roleExists) {
            Role::create(['name' => $newRole, 'guard_name' => 'web']);
        }

        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update role
        $user->syncRoles([$newRole]);

        return redirect()->route('users.index')->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user)
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')
                ->with('error', 'You cannot delete your own account!');
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Get user data for editing (API endpoint).
     */
    public function show(User $user)
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'pin' => $user->dynamic_pin, // Use dynamic PIN
            'is_active' => $user->is_active,
            'role' => $user->roles->first()?->name,
        ]);
    }
}
