<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ isset($coachingSession) ? __('Edit Session') : __('Create Session') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form action="{{ isset($coachingSession) ? route('admin.coaching-sessions.update', $coachingSession) : route('admin.coaching-sessions.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @if(isset($coachingSession))
                            @method('PUT')
                        @endif

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <x-input-label for="title" :value="__('Title')" />
                                <x-text-input id="title" name="title" type="text" class="mt-1 block w-full" :value="old('title', $coachingSession->title ?? '')" required autofocus />
                                <x-input-error class="mt-2" :messages="$errors->get('title')" />
                            </div>

                            <div>
                                <x-input-label for="price" :value="__('Price ($)')" />
                                <x-text-input id="price" name="price" type="number" step="0.01" class="mt-1 block w-full" :value="old('price', $coachingSession->price ?? '')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('price')" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="short_description" :value="__('Short Description')" />
                                <textarea id="short_description" name="short_description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="2" required>{{ old('short_description', $coachingSession->short_description ?? '') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('short_description')" />
                            </div>

                            <div class="col-span-2">
                                <x-input-label for="long_description" :value="__('Long Description')" />
                                <textarea id="long_description" name="long_description" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" rows="4" required>{{ old('long_description', $coachingSession->long_description ?? '') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('long_description')" />
                            </div>

                            <div>
                                <x-input-label for="start_time" :value="__('Start Time')" />
                                <x-text-input id="start_time" name="start_time" type="time" class="mt-1 block w-full" :value="old('start_time', $coachingSession->start_time ?? '')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('start_time')" />
                            </div>

                            <div>
                                <x-input-label for="end_time" :value="__('End Time')" />
                                <x-text-input id="end_time" name="end_time" type="time" class="mt-1 block w-full" :value="old('end_time', $coachingSession->end_time ?? '')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('end_time')" />
                            </div>

                            <div>
                                <x-input-label for="duration" :value="__('Duration (Minutes)')" />
                                <x-text-input id="duration" name="duration" type="number" class="mt-1 block w-full" :value="old('duration', $coachingSession->duration ?? '')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('duration')" />
                            </div>

                            <div>
                                <x-input-label for="meeting_link" :value="__('Meeting Link')" />
                                <x-text-input id="meeting_link" name="meeting_link" type="url" class="mt-1 block w-full" :value="old('meeting_link', $coachingSession->meeting_link ?? '')" required />
                                <x-input-error class="mt-2" :messages="$errors->get('meeting_link')" />
                            </div>

                            <div>
                                <x-input-label for="available_days" :value="__('Available Days')" />
                                <div class="mt-2 space-y-2">
                                    @php
                                        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                        $selectedDays = old('available_days', $coachingSession->available_days ?? []);
                                    @endphp
                                    @foreach($days as $day)
                                        <div class="flex items-center">
                                            <input type="checkbox" name="available_days[]" value="{{ $day }}" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ in_array($day, $selectedDays) ? 'checked' : '' }}>
                                            <span class="ml-2 text-sm text-gray-600">{{ $day }}</span>
                                        </div>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('available_days')" />
                            </div>

                            <div>
                                <x-input-label for="image" :value="__('Image')" />
                                <input id="image" name="image" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" {{ isset($coachingSession) ? '' : 'required' }} />
                                @if(isset($coachingSession->image))
                                    <img src="{{ asset('storage/' . $coachingSession->image) }}" class="mt-2 h-20 w-auto rounded">
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('image')" />
                            </div>

                            <div>
                                <x-input-label for="video" :value="__('Video (Optional)')" />
                                <input id="video" name="video" type="file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                @if(isset($coachingSession->video))
                                    <div class="mt-2 text-sm text-indigo-600">Video uploaded</div>
                                @endif
                                <x-input-error class="mt-2" :messages="$errors->get('video')" />
                            </div>

                            <div>
                                <x-input-label for="status" :value="__('Status')" />
                                <select id="status" name="status" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="active" {{ (old('status', $coachingSession->status ?? '') == 'active') ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ (old('status', $coachingSession->status ?? '') == 'inactive') ? 'selected' : '' }}>Inactive</option>
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('status')" />
                            </div>
                        </div>

                        <div class="flex items-center justify-end mt-6">
                            <a href="{{ route('admin.coaching-sessions.index') }}" class="text-sm text-gray-600 hover:text-gray-900 mr-4">Cancel</a>
                            <x-primary-button>
                                {{ isset($coachingSession) ? __('Update Session') : __('Create Session') }}
                            </x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
