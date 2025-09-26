<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Trip;
use App\Filament\Resources\TripResource;

class ActiveTripsIndicator extends Component
{
    public function render()
    {
        $count = Trip::query()
            ->where('status', 'in_progress')
            ->count();

        return view('livewire.active-trips-indicator', [
            'count' => $count,
            'tripsIndexUrl' => TripResource::getUrl('index'),
        ]);
    }
}
