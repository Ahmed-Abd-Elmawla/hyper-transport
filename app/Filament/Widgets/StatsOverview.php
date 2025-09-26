<?php

namespace App\Filament\Widgets;

use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use App\Filament\Resources\CompanyResource;
use App\Filament\Resources\DriverResource;
use App\Filament\Resources\VehicleResource;

class StatsOverview extends BaseWidget
{
    protected function getCards(): array
    {
        return [
            Card::make('Companies', Company::query()->count())
                ->description('Total companies')
                ->icon('heroicon-o-building-office')
                ->color('primary')
                ->url(CompanyResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'transition-shadow duration-200 hover:shadow-md ring-1 ring-[#004F3B]',
                ]),

            Card::make('Drivers', Driver::query()->count())
                ->description('Total drivers')
                ->icon('heroicon-o-user-group')
                ->color('primary')
                ->url(DriverResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'transition-shadow duration-200 hover:shadow-md ring-1 ring-[#004F3B]',
                ]),

            Card::make('Vehicles', Vehicle::query()->count())
                ->description('Total vehicles')
                ->icon('heroicon-o-truck')
                ->color('primary')
                ->url(VehicleResource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'transition-shadow duration-200 hover:shadow-md ring-1 ring-[#004F3B]',
                ]),
        ];
    }

    protected function getColumns(): int
    {
        return 3;
    }

    protected static ?int $sort = 10;
}
