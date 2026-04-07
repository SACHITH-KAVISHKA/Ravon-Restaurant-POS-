@extends('layouts.app')

@section('title', 'User Management - Ravon POS')

@push('styles')
<style>
    /* Modal Transitions */
    .modal-overlay {
        transition: opacity 0.3s ease;
    }

    .modal-content {
        transition: transform 0.3s ease, opacity 0.3s ease;
    }

    .modal-overlay.hidden .modal-content {
        transform: scale(0.95);
        opacity: 0;
    }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-gray-50 flex">
    <!-- Sidebar Component -->
    <x-sidebar />

    <!-- Main Content -->
    <div class="flex-1 p-8">
        <!-- Header -->
        <div class="mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-3xl font-bold text-transparent bg-clip-text bg-gradient-to-r from-[#667eea] to-[#764ba2]">User Management</h1>
                    <p class="text-gray-600 mt-1">Manage your restaurant staff accounts and permissions</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="openCreateModal()" class="bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold px-6 py-2.5 rounded-lg transition duration-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                        </svg>
                        Add New User
                    </button>
                </div>
            </div>
        </div>

        <!-- Success/Error Messages -->
        @if(session('success'))
        <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            {{ session('success') }}
        </div>
        @endif

        @if(session('error'))
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg flex items-center">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            {{ session('error') }}
        </div>
        @endif

        @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden border border-gray-200">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-[#667eea] to-[#764ba2]">
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">User</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Username</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Role</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">PIN</th>
                            <th class="px-6 py-4 text-left text-xs font-semibold text-white uppercase tracking-wider">Status</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold text-white uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($users as $user)
                        <tr class="user-row hover:bg-purple-50 transition" data-role="{{ $user->roles->first()?->name }}">
                            <!-- User Info -->
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 mr-3">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-r from-[#667eea] to-[#764ba2] flex items-center justify-center">
                                            <span class="text-white font-semibold text-sm">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-sm font-semibold text-gray-800">{{ $user->name }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Username -->
                            <td class="px-6 py-4">
                                <span class="text-sm text-gray-700">{{ $user->username }}</span>
                            </td>

                            <!-- Role -->
                            <td class="px-6 py-4">
                                @php
                                $role = $user->roles->first()?->name;
                                $roleColors = [
                                'superadmin' => 'bg-red-100 text-red-800',
                                'admin' => 'bg-purple-100 text-purple-800',
                                'manager' => 'bg-green-100 text-green-800',
                                'cashier' => 'bg-blue-100 text-blue-800',
                                'supervisor' => 'bg-orange-100 text-orange-800',
                                ];
                                @endphp
                                <span class="inline-flex px-3 py-1 text-xs font-semibold rounded-full {{ $roleColors[$role] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($role ?? 'N/A') }}
                                </span>
                            </td>

                            <!-- PIN (only for supervisors) - Auto-changes every 5 minutes -->
                            <td class="px-6 py-4">
                                @if($user->hasRole('supervisor'))
                                <div class="flex flex-col gap-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-lg font-bold bg-gradient-to-r from-purple-100 to-orange-100 px-3 py-1 rounded-lg text-purple-800 pin-display" data-user-id="{{ $user->id }}">{{ $user->dynamic_pin }}</span>
                                    </div>
                                    <span class="text-xs text-orange-600 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Next refresh: <span class="pin-countdown font-medium"></span>
                                    </span>
                                </div>
                                @else
                                <span class="text-gray-400">-</span>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4">
                                @if($user->is_active)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-green-500"></span>
                                    Active
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-red-500"></span>
                                    Inactive
                                </span>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4">
                                <div class="flex items-center justify-center gap-2">
                                    <button onclick="openEditModal({{ $user->id }})" class="inline-flex items-center px-3 py-2 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white rounded-lg hover:shadow-md transition" title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                    </button>
                                    @if($user->id !== auth()->id())
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                                </svg>
                                <p class="text-gray-600 text-lg">No users found</p>
                                <button onclick="openCreateModal()" class="inline-block mt-4 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white font-semibold px-6 py-2.5 rounded-lg">
                                    Add Your First User
                                </button>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Create/Edit User Modal -->
<div id="userModal" class="modal-overlay fixed inset-0 z-50 hidden">
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" onclick="closeModal()"></div>
    <div class="fixed inset-0 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
            <!-- Modal Header -->
            <div class="bg-gradient-to-r from-[#667eea] to-[#764ba2] p-6 rounded-t-xl">
                <div class="flex items-center justify-between">
                    <h3 id="modalTitle" class="text-xl font-bold text-white">Add New User</h3>
                    <button onclick="closeModal()" class="text-white/80 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Modal Body -->
            <form id="userForm" method="POST" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="formMethod" name="_method" value="POST">

                <!-- Name -->
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                    <input type="text" id="name" name="name" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Enter full name">
                </div>

                <!-- Username -->
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-1">Username *</label>
                    <input type="text" id="username" name="username" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Enter username">
                </div>

                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
                        Password <span id="passwordRequired">*</span>
                    </label>
                    <input type="password" id="password" name="password"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition"
                        placeholder="Enter password (min 6 characters)">
                    <p id="passwordHint" class="text-xs text-gray-500 mt-1 hidden">Leave blank to keep current password</p>
                </div>

                <!-- Role -->
                <div>
                    <label for="role" class="block text-sm font-medium text-gray-700 mb-1">Role *</label>
                    <select id="role" name="role" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition bg-white"
                        onchange="handleRoleChange()">
                        <option value="">Select Role</option>
                        <option value="superadmin">Super Admin</option>
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="cashier">Cashier</option>
                        <option value="supervisor">Supervisor</option>
                    </select>
                </div>

                <!-- PIN Notice (for Supervisor) -->
                <div id="pinNotice" class="hidden p-4 bg-orange-50 border border-orange-200 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-orange-600 mr-2 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="text-sm font-medium text-orange-800">Supervisor PIN</p>
                            <p class="text-xs text-orange-700 mt-1">A 4-digit PIN will be automatically generated for this supervisor.</p>
                        </div>
                    </div>
                </div>

                <!-- Current PIN (for editing Supervisor) -->
                <div id="currentPinDisplay" class="hidden p-4 bg-purple-50 border border-purple-200 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-purple-800">Current PIN</p>
                            <p id="currentPinValue" class="text-2xl font-mono font-bold text-purple-600 mt-1">----</p>
                        </div>
                    </div>
                </div>

                <!-- Status (for editing) -->
                <div id="statusField" class="hidden">
                    <label class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1" class="w-5 h-5 text-purple-600 border-gray-300 rounded focus:ring-purple-500">
                        <span class="ml-3 text-sm font-medium text-gray-700">Active User</span>
                    </label>
                </div>

                <!-- Submit Button -->
                <div class="pt-4">
                    <button type="submit" id="submitBtn"
                        class="w-full bg-gradient-to-r from-[#667eea] to-[#764ba2] hover:shadow-lg hover:shadow-purple-500/50 text-white font-semibold py-3 px-6 rounded-lg transition duration-200">
                        Create User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let isEditMode = false;

    function openCreateModal() {
        isEditMode = false;
        document.getElementById('modalTitle').textContent = 'Add New User';
        document.getElementById('submitBtn').textContent = 'Create User';
        document.getElementById('userForm').action = '{{ route("users.store") }}';
        document.getElementById('formMethod').value = 'POST';
        document.getElementById('passwordRequired').classList.remove('hidden');
        document.getElementById('passwordHint').classList.add('hidden');
        document.getElementById('password').required = true;
        document.getElementById('statusField').classList.add('hidden');
        document.getElementById('currentPinDisplay').classList.add('hidden');

        // Reset form
        document.getElementById('userForm').reset();
        document.getElementById('pinNotice').classList.add('hidden');

        // Show modal
        document.getElementById('userModal').classList.remove('hidden');
    }

    async function openEditModal(userId) {
        isEditMode = true;

        try {
            const response = await fetch(`/users/${userId}`);
            const user = await response.json();

            document.getElementById('modalTitle').textContent = 'Edit User';
            document.getElementById('submitBtn').textContent = 'Update User';
            document.getElementById('userForm').action = `/users/${userId}`;
            document.getElementById('formMethod').value = 'PUT';
            document.getElementById('passwordRequired').classList.add('hidden');
            document.getElementById('passwordHint').classList.remove('hidden');
            document.getElementById('password').required = false;
            document.getElementById('statusField').classList.remove('hidden');

            // Fill form
            document.getElementById('name').value = user.name || '';
            document.getElementById('username').value = user.username || '';
            document.getElementById('role').value = user.role || '';
            document.getElementById('is_active').checked = user.is_active;
            document.getElementById('password').value = '';

            // Handle PIN display for supervisors
            if (user.role === 'supervisor' && user.pin) {
                document.getElementById('currentPinDisplay').classList.remove('hidden');
                document.getElementById('currentPinValue').textContent = user.pin;
                document.getElementById('pinNotice').classList.add('hidden');
            } else {
                document.getElementById('currentPinDisplay').classList.add('hidden');
                handleRoleChange();
            }

            // Show modal
            document.getElementById('userModal').classList.remove('hidden');
        } catch (error) {
            console.error('Error fetching user:', error);
            alert('Failed to load user data');
        }
    }

    function closeModal() {
        document.getElementById('userModal').classList.add('hidden');
    }

    function handleRoleChange() {
        const role = document.getElementById('role').value;
        const pinNotice = document.getElementById('pinNotice');
        const currentPinDisplay = document.getElementById('currentPinDisplay');

        if (role === 'supervisor' && !isEditMode) {
            pinNotice.classList.remove('hidden');
        } else {
            pinNotice.classList.add('hidden');
        }

        if (role !== 'supervisor') {
            currentPinDisplay.classList.add('hidden');
        }
    }

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // PIN Auto-refresh countdown timer
    function updatePinCountdown() {
        const now = new Date();
        const minutes = now.getMinutes();
        const seconds = now.getSeconds();

        // Calculate time until next 5-minute mark
        const minutesUntilRefresh = 4 - (minutes % 5);
        const secondsUntilRefresh = 60 - seconds;

        let totalSeconds = minutesUntilRefresh * 60 + secondsUntilRefresh;
        if (secondsUntilRefresh === 60) {
            totalSeconds = (minutesUntilRefresh + 1) * 60;
        }

        const displayMinutes = Math.floor(totalSeconds / 60);
        const displaySeconds = totalSeconds % 60;

        const countdownText = `${displayMinutes}:${displaySeconds.toString().padStart(2, '0')}`;

        document.querySelectorAll('.pin-countdown').forEach(el => {
            el.textContent = countdownText;
        });

        // Auto-refresh page when countdown reaches 0
        if (totalSeconds <= 1) {
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    }

    // Update countdown every second
    if (document.querySelectorAll('.pin-countdown').length > 0) {
        updatePinCountdown();
        setInterval(updatePinCountdown, 1000);
    }
</script>
@endpush
@endsection