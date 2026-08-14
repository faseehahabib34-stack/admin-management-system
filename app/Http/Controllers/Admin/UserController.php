<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserStoreRequest;
use App\Http\Requests\UserUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->input('search', ''));
        $roleFilter = (string) $request->input('role', '');
        $statusFilter = (string) $request->input('status', '');

        $query = User::query()->with('roles');

        // Search by name or email
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by role
        if ($roleFilter !== '') {
            $query->whereHas('roles', function ($q) use ($roleFilter) {
                $q->where('name', $roleFilter);
            });
        }

        // Filter by status
        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $users = $query
            ->orderBy('name')
            ->paginate(15)
            ->appends($request->query());

        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.index', [
            'users' => $users,
            'roles' => $roles,
            'search' => $search,
            'roleFilter' => $roleFilter,
            'statusFilter' => $statusFilter,
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): View
    {
        $this->authorize('create', User::class);

        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(UserStoreRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'status' => $validated['status'] ?? 'active',
        ]);

        $requestedRole = $this->resolveRoleForAssignment(
            $request->user(),
            $validated['role']
        );

        $user->syncRoles($requestedRole);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        $this->authorize('view', $user);

        $user->load('roles');

        return view('admin.users.show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): View
    {
        $this->authorize('update', $user);

        $user->load('roles');

        $roles = Role::orderBy('name')->pluck('name');

        return view('admin.users.edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(
        UserUpdateRequest $request,
        User $user
    ): RedirectResponse {
        $this->authorize('update', $user);

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update status if provided
        if (isset($validated['status'])) {
            $user->status = $validated['status'];
        }

        // Update password only if provided
        if (! empty($validated['password'])) {
            $user->password = bcrypt($validated['password']);
        }

        $user->save();

        // Do not allow a user to change their own role
        if ($request->user()->id !== $user->id) {
            $requestedRole = $this->resolveRoleForAssignment(
                $request->user(),
                $validated['role']
                    ?? $user->getRoleNames()->first()
            );

            $user->syncRoles($requestedRole);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Delete the specified user.
     */
    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        // Prevent deleting own account
        if (auth()->id() === $user->id) {
            abort(403, 'You cannot delete your own account.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'User deleted successfully.');
    }

    /**
     * Resolve the role that may be assigned based on
     * the currently authenticated user's role.
     */
    protected function resolveRoleForAssignment(
        User $actor,
        ?string $role
    ): string {
        // Super Admin can assign any role
        if ($actor->hasRole('Super Admin')) {
            return $role ?? 'User';
        }

        // Admin can assign Admin or User
        if ($actor->hasRole('Admin')) {
            return in_array(
                $role,
                ['Admin', 'User'],
                true
            ) ? $role : 'User';
        }

        // Everyone else gets User role
        return 'User';
    }
}