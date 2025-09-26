<?php

namespace App\Filament\Widgets;

use App\Models\Trip;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Filament\Resources\TripResource;
use Carbon\Carbon;

class TripsOverview extends BaseWidget
{
    protected static ?int $sort = 20;
    protected function getCards(): array
    {
        $start = Carbon::now()->startOfMonth();
        $end = Carbon::now()->endOfMonth();

        $totalTrips = Trip::query()->count();

        $completedThisMonth = Trip::query()
            ->where('status', 'completed')
            ->whereBetween('end_at', [$start, $end])
            ->count();

        return [
            Card::make('All Trips', $totalTrips)
                ->description('Total trips recorded')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->url(TripResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'transition-shadow duration-200 hover:shadow-md ring-1 ring-[#004F3B]',
                ]),

            Card::make('Completed This Month', $completedThisMonth)
                ->description($start->format('F Y'))
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->url(TripResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'transition-shadow duration-200 hover:shadow-md ring-1 ring-[#004F3B]',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 2;
    }
}
