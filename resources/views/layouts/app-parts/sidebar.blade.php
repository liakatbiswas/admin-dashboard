    <aside id="sb"
        class="fixed inset-y-0 left-0 z-40 flex w-[250px] -translate-x-full flex-col border-r border-line bg-card transition-all duration-200 lg:translate-x-0 lg:[.sm-nav_&]:w-[70px] [.sb-open_&]:translate-x-0">
        <div class="flex h-[70px] shrink-0 items-center px-5 lg:[.sm-nav_&]:justify-center">
            <x-logo />
        </div>

        <nav class="flex-1 overflow-y-auto px-3 pb-6" aria-label="{{ __('Main') }}">
            <p class="px-3 pb-1.5 pt-[18px] text-xs font-medium tracking-[.08em] text-mute lg:[.sm-nav_&]:hidden">{{ __('Menu') }} </p>
            <x-nav-link :href="route('dashboard')" icon="home" :active="request()->routeIs('dashboard')">{{ __('Dashboard') }}</x-nav-link>

            @canany(['users.view', 'users.create', 'roles.manage', 'activity.view', 'settings.site.manage', 'admission.manage'])
                <p class="px-3 pb-1.5 pt-[18px] text-xs font-medium tracking-[.08em] text-mute lg:[.sm-nav_&]:hidden">{{ __('Administration') }}</p>
            @endcanany

            @can('activity.view')
                <x-nav-link :href="route('activity.index')" icon="clock" :active="request()->routeIs('activity.*')">{{ __('Activity log') }}</x-nav-link>
            @endcan
            <div class="group {{ request()->routeIs('settings.*') ? 'open' : '' }}">
                <button type="button" data-tg aria-controls="settings-submenu" aria-expanded="{{ request()->routeIs('settings.*') ? 'true' : 'false' }}"
                    class="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 text-[14.5px] lg:[.sm-nav_&]:justify-center {{ request()->routeIs('settings.*') ? 'bg-tint font-medium text-primary' : 'text-ink hover:bg-soft' }}">
                    <x-icon name="gear" />
                    <span class="lg:[.sm-nav_&]:hidden">{{ __('Settings') }}</span>
                    <x-icon name="chev" class="ml-auto size-4 transition-transform group-[.open]:rotate-90 lg:[.sm-nav_&]:hidden" />
                </button>
                <div id="settings-submenu" class="hidden pb-1 pl-[42px] pt-0.5 group-[.open]:block lg:[.sm-nav_&]:!hidden">
@can('admission.manage')
<a href="{{ route('settings.registration') }}" @if(request()->routeIs('settings.registration')) aria-current="page" @endif class="block px-2 py-1.5 text-sm {{ request()->routeIs('settings.registration') ? 'font-medium text-primary' : 'text-mute hover:text-primary' }}">{{ __('Registration on/off') }}</a>
@endcan
<a href="{{ route('settings.password') }}" @if(request()->routeIs('settings.password')) aria-current="page" @endif class="block px-2 py-1.5 text-sm {{ request()->routeIs('settings.password') ? 'font-medium text-primary' : 'text-mute hover:text-primary' }}">{{ __('Change password') }}</a>
@can('settings.site.manage')
<a href="{{ route('settings.site') }}" @if(request()->routeIs('settings.site')) aria-current="page" @endif class="block px-2 py-1.5 text-sm {{ request()->routeIs('settings.site') ? 'font-medium text-primary' : 'text-mute hover:text-primary' }}">{{ __('Site information') }}</a>
@endcan
                </div>
            </div>

            @canany(['users.view', 'users.create'])
                <div class="group {{ request()->routeIs('users.*') ? 'open' : '' }}">
                    <button type="button" aria-expanded="{{ request()->routeIs('users.*') ? 'true' : 'false' }}" class="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 text-[14.5px] lg:[.sm-nav_&]:justify-center {{ request()->routeIs('users.*') ? 'bg-tint font-medium text-primary' : 'text-ink hover:bg-soft' }}"
                        data-tg>
                        <x-icon name="users" />
                        <span class="lg:[.sm-nav_&]:hidden">{{ __('Users') }}</span>
                        <x-icon name="chev"
                            class="ml-auto size-4 transition-transform group-[.open]:rotate-90 lg:[.sm-nav_&]:hidden" />
                    </button>
                    <div class="hidden pb-1 pl-[42px] pt-0.5 group-[.open]:block lg:[.sm-nav_&]:!hidden">
                        @can('users.view')
                            <a href="{{ route('users.index') }}"
                                class="block px-2 py-1.5 text-sm text-mute hover:text-primary">{{ __('All users') }}</a>
                        @endcan
                        @can('users.create')
                            <a href="{{ route('users.create') }}"
                                class="block px-2 py-1.5 text-sm text-mute hover:text-primary">{{ __('Invite user') }}</a>
                        @endcan
                    </div>
                </div>
            @endcanany

            @can('roles.manage')
                <div class="group {{ request()->routeIs('roles.*') ? 'open' : '' }}">
                    <button type="button" aria-expanded="{{ request()->routeIs('roles.*') ? 'true' : 'false' }}" class="flex w-full cursor-pointer items-center gap-3 rounded-md px-3 py-2.5 text-[14.5px] lg:[.sm-nav_&]:justify-center {{ request()->routeIs('roles.*') ? 'bg-tint font-medium text-primary' : 'text-ink hover:bg-soft' }}"
                        data-tg>
                        <x-icon name="lock" />
                        <span class="lg:[.sm-nav_&]:hidden">{{ __('Roles') }}</span>
                        <x-icon name="chev"
                            class="ml-auto size-4 transition-transform group-[.open]:rotate-90 lg:[.sm-nav_&]:hidden" />
                    </button>
                    <div class="hidden pb-1 pl-[42px] pt-0.5 group-[.open]:block lg:[.sm-nav_&]:!hidden">
                        <a href="{{ route('roles.index') }}"
                            class="block px-2 py-1.5 text-sm text-mute hover:text-primary">{{ __('All roles') }}</a>
                        <a href="{{ route('roles.create') }}"
                            class="block px-2 py-1.5 text-sm text-mute hover:text-primary">{{ __('New role') }}</a>
                    </div>
                </div>
            @endcan

        </nav>
    </aside>
