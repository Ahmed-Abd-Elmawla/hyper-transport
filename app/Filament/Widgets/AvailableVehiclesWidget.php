<?php

namespace App\Filament\Widgets;

use App\Models\Vehicle;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\Filter;
use App\Filament\Resources\VehicleResource;

class AvailableVehiclesWidget extends BaseWidget
{
    protected static ?string $heading = 'Available Vehicles';
    protected static ?int $sort = 30;
    protected int|string|array $columnSpan = 1;

    protected function getTableQuery(): Builder
    {
        return Vehicle::query()
            ->with('company')
            ->where('is_active', true)
            ->where('status', 'available');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('plate_number')
                ->label('Vehicle')
                ->formatStateUsing(fn ($state, Vehicle $record) => "{$record->brand} {$record->model} {$record->plate_number}")
                ->searchable()
                ->sortable()
                ->url(fn (Vehicle $record) => VehicleResource::getUrl('edit', ['record' => $record])),

            Tables\Columns\TextColumn::make('year')->sortable()->toggleable(),

            Tables\Columns\ColorColumn::make('color')
                ->label('Color')
                ->sortable()
                ->toggleable(),

            Tables\Columns\TextColumn::make('company.name')->label('Company')->sortable(),
            Tables\Columns\TextColumn::make('capacity')->sortable()->label('Capacity'),
            Tables\Columns\TextColumn::make('status')->badge(),
        ];
    }

    protected function getTablePollingInterval(): ?string
    {
        return '30s';
    }
}
