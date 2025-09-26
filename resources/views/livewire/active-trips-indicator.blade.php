<div wire:poll.10s class="fi-topbar-item flex items-center">
    <a href="{{ $tripsIndexUrl }}" class="flex items-center gap-2 px-3 py-1 rounded-md hover:bg-white/10 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#004F3B] text-white">
        <x-filament::badge color="success">
            Active Trips: {{ $count }}
        </x-filament::badge>
    </a>
</div>
