<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Edit Badge:') }} {{ $badge->name }}
            </h2>
            <a href="{{ route('admin.badges.index') }}" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 border border-indigo-600 dark:border-indigo-400 font-bold py-2 px-4 rounded">
                Back to List
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    <form action="{{ route('admin.badges.update', $badge->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="name">
                                Badge Name
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" name="name" type="text" value="{{ old('name', $badge->name) }}" required>
                            @error('name') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="description">
                                Description
                            </label>
                            <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="description" name="description">{{ old('description', $badge->description) }}</textarea>
                            @error('description') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="type">
                                Progress Type
                            </label>
                            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="type" name="type" required>
                                <option value="skills" {{ (old('type', $badge->type) == 'skills') ? 'selected' : '' }}>Skills</option>
                                <option value="goals" {{ (old('type', $badge->type) == 'goals') ? 'selected' : '' }}>Goals</option>
                            </select>
                            @error('type') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="required_amount">
                                Required Amount
                            </label>
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="required_amount" name="required_amount" type="number" min="1" value="{{ old('required_amount', $badge->required_amount) }}" required>
                            @error('required_amount') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="mb-6">
                            <label class="block text-gray-700 dark:text-gray-300 text-sm font-bold mb-2" for="icon">
                                Update Badge Icon (Optional)
                            </label>
                            @if($badge->icon)
                                <div class="mb-2">
                                    <img src="{{ asset($badge->icon) }}" alt="{{ $badge->name }}" class="h-16 w-16 rounded-full border">
                                    <p class="text-xs text-gray-500">Current Icon</p>
                                </div>
                            @endif
                            <input class="shadow appearance-none border rounded w-full py-2 px-3 dark:text-gray-100 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="icon" name="icon" type="file">
                            <p class="text-xs text-gray-500 mt-1">Leave blank to keep the current icon. Max 2MB.</p>
                            @error('icon') <p class="text-red-500 text-xs italic mt-2">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex items-center justify-between">
                            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                                Update Badge
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
