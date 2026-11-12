<x-app-layout title="Settings" :crumbs="['Settings' => null]">
    <div class="max-w-4xl space-y-6">
        <x-card class="p-6">
            <h2 class="text-[15px] font-medium text-head">Site information</h2>
            <p class="mb-5 mt-1 text-mute">
                Update the public site name, description and contact details.
            </p>

            <form method="POST" action="{{ route('settings.site.update') }}"
                enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <x-field label="Site title" name="site_title" :value="$siteSetting->site_title" required />
                    <x-field label="Subtitle" name="site_subtitle" :value="$siteSetting->site_subtitle" />
                    <x-field label="Tagline" name="site_tagline" :value="$siteSetting->site_tagline" />
                    <x-field label="Website" name="website" type="url" :value="$siteSetting->website"
                        placeholder="https://example.com" />
                    <x-field label="Contact email" name="email" type="email" :value="$siteSetting->email" />
                    <x-field label="Phone" name="phone" type="tel" :value="$siteSetting->phone" />

                    <div class="sm:col-span-2">
                        <x-field label="Description" name="site_description" type="textarea"
                            :value="$siteSetting->site_description" rows="4" />
                    </div>

                    <div class="sm:col-span-2">
                        <x-field label="Address" name="address" type="textarea"
                            :value="$siteSetting->address" rows="3" />
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    @foreach ([
                        'favicon' => 'Favicon',
                        'light_logo' => 'Light theme logo',
                        'dark_logo' => 'Dark theme logo',
                    ] as $input => $label)
                        @php
                            $pathColumn = $input.'_path';
                            $currentPath = $siteSetting->{$pathColumn};
                        @endphp

                        <div>
                            <label for="{{ $input }}" class="mb-1.5 block text-sm font-medium text-head">
                                {{ $label }}
                            </label>
                            <input id="{{ $input }}" type="file" name="{{ $input }}"
                                accept="image/png,image/jpeg,image/webp" class="w-full text-sm text-mute">

                            @error($input)
                                <p class="mt-1 text-sm text-bad">{{ $message }}</p>
                            @enderror

                            @if ($currentPath)
                                <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($currentPath) }}"
                                    alt="{{ $label }} preview" class="mt-3 max-h-20 max-w-48 object-contain">
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="flex justify-end">
                    <x-button>Save site settings</x-button>
                </div>
            </form>
        </x-card>

        <x-card class="p-6">
            <h2 class="text-[15px] font-medium text-head">Public registration</h2>
            <p class="mb-5 mt-1 text-mute">
                When enabled, new visitors can register with the student role.
            </p>
            @include('settings._registration-toggle', [
                'registrationEnabled' => $registrationEnabled,
            ])
        </x-card>
    </div>
</x-app-layout>
