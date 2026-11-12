<footer class="footer bg-white shadow dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
    <div class="px-4 sm:px-6 lg:px-8">
        <div
            class="flex flex-col sm:flex-row items-center justify-between gap-1 sm:gap-4 py-4 text-sm text-gray-600 dark:text-gray-400">

            <!-- Left -->
            <div class="w-full sm:w-auto text-center sm:text-left leading-relaxed">
                &copy; {{ date('Y') }}
                <a href="{{ $siteSettings->website }}" target="_blank" rel="noopener noreferrer"
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">
                    {{ $siteSettings->site_title }}
                </a>.
                <span class="block sm:inline">All Rights Reserved.</span>
            </div>

            <!-- Right -->
            <div class="w-full sm:w-auto text-center sm:text-right leading-relaxed">
                Developed By
                <a href="https://liakatbiswas.com" target="_blank" rel="noopener noreferrer"
                    class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 hover:underline">
                    Liakat Biswas
                </a>
            </div>

        </div>
    </div>
</footer>
