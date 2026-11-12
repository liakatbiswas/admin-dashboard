        <header class="sticky top-0 z-20 flex h-[70px] items-center gap-1 border-b border-line bg-card px-4">
            <button id="mt" type="button"
                class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                aria-label="{{ __('Toggle menu') }}">
                <x-icon name="menu" />
            </button>

            <div class="ml-auto flex items-center gap-0.5 sm:gap-1">
                <x-language-switcher />
                <button id="fs" type="button"
                    class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head max-sm:hidden"
                    aria-label="{{ __('Fullscreen') }}">
                    <x-icon name="expand" />
                </button>

                <button id="th" type="button"
                    class="relative grid size-10 shrink-0 cursor-pointer place-items-center rounded-full text-mute hover:bg-soft hover:text-head"
                    aria-label="{{ __('Light/Dark mode') }}">
                    <x-icon name="moon" class="dark:hidden" />
                    <x-icon name="sun" class="hidden dark:block" />
                </button>

                {{-- Account dropdown --}}
                <span data-dw class="group relative inline-block">
                    <button type="button" data-dd class="grid size-10 cursor-pointer place-items-center rounded-full"
                        aria-label="{{ __('Account') }}">
                        <x-avatar :user="auth()->user()" size="size-9" />
                    </button>
                    <div data-dp
                        class="absolute right-0 top-[calc(100%+6px)] z-50 hidden min-w-[190px] rounded-lg border border-line bg-card p-1.5 shadow-xl group-[.open]:block">
                        <div class="px-3 py-2">
                            <p class="font-medium text-head">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-mute">{{ auth()->user()->email }}</p>
                        </div>
                        <hr class="my-1.5 border-line">
                        <a href="{{ route('profile.edit') }}"
                            class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-md px-3 py-[7px] text-ink hover:bg-soft">
                            <x-icon name="user" /> {{ __('My profile') }} </a>
                        @canany(['settings.site.manage', 'admission.manage'])
                            <a href="{{ route('settings.index') }}"
                                class="flex cursor-pointer items-center gap-2 whitespace-nowrap rounded-md px-3 py-[7px] text-ink hover:bg-soft">
                                <x-icon name="gear" /> {{ __('Settings') }} </a>
                        @endcan
                        <hr class="my-1.5 border-line">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"
                                class="flex w-full cursor-pointer items-center gap-2 whitespace-nowrap rounded-md px-3 py-[7px] text-left text-bad hover:bg-soft">
                                <x-icon name="logout" /> {{ __('Log out') }} </button>
                        </form>
                    </div>
                </span>


            </div>
        </header>
