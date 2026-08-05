<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('User Management') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">

                    {{-- Success Message --}}
                    <div id="success-alert" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 hidden" role="alert">
                        <span id="success-message"></span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Role</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Joined</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Can Manage Sessions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($users as $user)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->id }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $user->first_name }} {{ $user->last_name }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->email }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->user_role == 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ ucfirst($user->user_role) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $user->created_at->format('M d, Y') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="checkbox"
                                                class="session-toggle h-5 w-5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                                data-user-id="{{ $user->id }}"
                                                {{ $user->can_manage_sessions ? 'checked' : '' }}>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6"
                                            class="px-6 py-10 whitespace-nowrap text-sm text-gray-500 text-center">
                                            <p class="text-lg font-medium text-gray-400">No users found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-4">
                        {{ $users->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.session-toggle').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    const userId = this.dataset.userId;
                    const toggle = this;

                    fetch(`/admin/users/${userId}/toggle-session-permission`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                        },
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status) {
                            const alert = document.getElementById('success-alert');
                            const message = document.getElementById('success-message');
                            message.textContent = data.message;
                            alert.classList.remove('hidden');
                            setTimeout(() => alert.classList.add('hidden'), 3000);
                        } else {
                            // Revert on failure
                            toggle.checked = !toggle.checked;
                            alert('Failed to update permission.');
                        }
                    })
                    .catch(() => {
                        toggle.checked = !toggle.checked;
                        alert('Failed to update permission.');
                    });
                });
            });
        });
    </script>
</x-app-layout>
