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
                $query->whereIn('name', ['admin', 'cashier', 'supervisor']);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        return view('users.index', compact('users'));
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'in:admin,cashier,supervisor'],
        ]);

        // Generate PIN for Supervisor
        $pin = null;
        if ($request->role === 'supervisor') {
            $pin = $this->generateUniquePin();
        }

        // Check if supervisor role exists, if not create it
        if ($request->role === 'supervisor') {
            $supervisorRole = Role::where('name', 'supervisor')->first();
            if (!$supervisorRole) {
                $supervisorRole = Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
            }
        }

        $user = User::create([
            'name' => $request->name,
            'username' => $request->username,
            'password' => Hash::make($request->password),
            'pin' => $pin,
            'is_active' => true,
        ]);

        $user->assignRole($request->role);

        return redirect()->route('users.index')
            ->with('success', 'User created successfully!' . ($pin ? ' Supervisor PIN: ' . $pin : ''));
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
            'role' => ['required', 'string', 'in:admin,cashier,supervisor'],
            'is_active' => ['boolean'],
        ]);

        $oldRole = $user->roles->first()?->name;
        $newRole = $request->role;

        // Generate PIN if changing to supervisor and no pin exists
        $pin = $user->pin;
        if ($newRole === 'supervisor' && !$pin) {
            $pin = $this->generateUniquePin();
        } elseif ($newRole !== 'supervisor') {
            $pin = null;
        }

        // Check if supervisor role exists, if not create it
        if ($newRole === 'supervisor') {
            $supervisorRole = Role::where('name', 'supervisor')->first();
            if (!$supervisorRole) {
                $supervisorRole = Role::create(['name' => 'supervisor', 'guard_name' => 'web']);
            }
        }

        $updateData = [
            'name' => $request->name,
            'username' => $request->username,
            'pin' => $pin,
            'is_active' => $request->has('is_active') ? $request->is_active : true,
        ];

        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Update role
        $user->syncRoles([$newRole]);

        $message = 'User updated successfully!';
        if ($newRole === 'supervisor' && $oldRole !== 'supervisor') {
            $message .= ' Supervisor PIN: ' . $pin;
        }

        return redirect()->route('users.index')->with('success', $message);
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
            'pin' => $user->pin,
            'is_active' => $user->is_active,
            'role' => $user->roles->first()?->name,
        ]);
    }

    /**
     * Regenerate PIN for a supervisor.
     */
    public function regeneratePin(User $user)
    {
        if (!$user->hasRole('supervisor')) {
            return redirect()->route('users.index')
                ->with('error', 'PIN can only be regenerated for supervisors!');
        }

        $newPin = $this->generateUniquePin();
        $user->update(['pin' => $newPin]);

        return redirect()->route('users.index')
            ->with('success', 'PIN regenerated successfully! New PIN: ' . $newPin);
    }

    /**
     * Generate a unique 4-digit PIN.
     */
    private function generateUniquePin(): string
    {
        do {
            $pin = str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
        } while (User::where('pin', $pin)->exists());

        return $pin;
    }
}
