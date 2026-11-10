<x-guest-layout>
    <div class="text-center">
        <span
            class="grid size-12 flex-none place-items-center rounded-md rounded-full! bg-bad/15 text-bad mx-auto mb-4"><x-icon
                name="lock" /></span>
        <h4 class="text-lg text-head font-medium">Registration is currently closed</h4>
        <p class="text-mute mt-2 mb-6">নতুন রেজিস্ট্রেশন এখন বন্ধ আছে।</p>
        <a href="{{ route('login') }}"
            class="inline-flex cursor-pointer items-center justify-center gap-1.5 rounded-md border border-transparent px-4 py-2 text-sm font-medium leading-snug transition focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-1 focus-visible:outline-primary/45 bg-soft text-head hover:bg-line">Back
            to login</a>
    </div>
</x-guest-layout>
