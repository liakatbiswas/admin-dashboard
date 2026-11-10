    <x-app-layout title="Settings" :crumbs="['Settings' => null]">

        <div class="max-w-2xl">
            <x-card class="p-6">
                <h2 class="text-[15px] font-medium text-head">Public registration</h2>
                <p class="mb-5 mt-1 text-mute">When on, anyone can create an account (they get the student role). When
                    off,
                    the register page shows "Registration is currently closed".</p>
                @include('settings._registration-toggle', ['registrationEnabled' => $registrationEnabled])
            </x-card>
        </div>
    </x-app-layout>
