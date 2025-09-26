<x-filament::page>
    <div class="space-y-6">
        <div>
            {{ $this->form }}
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-filament::section>
                <x-slot name="heading">Available Drivers</x-slot>

                @if (count($availableDrivers) === 0)
                    <div class="text-sm text-gray-500">No available drivers for the selected period.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-4">#</th>
                                    <th class="py-2 pr-4">Name</th>
                                    <th class="py-2 pr-4">Company</th>
                                    <th class="py-2 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($availableDrivers as $i => $driver)
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">{{ $i + 1 }}</td>
                                        <td class="py-2 pr-4">{{ $driver['name'] }}</td>
                                        <td class="py-2 pr-4">{{ $driver['company'] ?? '-' }}</td>
                                        <td class="py-2 pr-4">
                                            <x-filament::badge color="success">{{ $driver['status'] }}</x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Available Vehicles</x-slot>

                @if (count($availableVehicles) === 0)
                    <div class="text-sm text-gray-500">No available vehicles for the selected period.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left border-b">
                                    <th class="py-2 pr-4">#</th>
                                    <th class="py-2 pr-4">Brand</th>
                                    <th class="py-2 pr-4">Model</th>
                                    <th class="py-2 pr-4">Plate</th>
                                    <th class="py-2 pr-4">Company</th>
                                    <th class="py-2 pr-4">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($availableVehicles as $i => $vehicle)
                                    <tr class="border-b">
                                        <td class="py-2 pr-4">{{ $i + 1 }}</td>
                                        <td class="py-2 pr-4">{{ $vehicle['brand'] }}</td>
                                        <td class="py-2 pr-4">{{ $vehicle['model'] }}</td>
                                        <td class="py-2 pr-4">{{ $vehicle['plate_number'] ?? '-' }}</td>
                                        <td class="py-2 pr-4">{{ $vehicle['company'] ?? '-' }}</td>
                                        <td class="py-2 pr-4">
                                            <x-filament::badge color="success">{{ $vehicle['status'] }}</x-filament::badge>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </x-filament::section>
        </div>
    </div>
</x-filament::page>
