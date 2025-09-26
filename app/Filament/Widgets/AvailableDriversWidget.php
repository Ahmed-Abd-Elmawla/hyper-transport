<?php

namespace App\Filament\Widgets;

use App\Models\Driver;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Filters\Filter;
use App\Filament\Resources\DriverResource;

class AvailableDriversWidget extends BaseWidget
{
    protected static ?string $heading = 'Available Drivers';
    protected static ?int $sort = 30;
    protected int|string|array $columnSpan = 1;

    protected function getTableQuery(): Builder
    {
        return Driver::query()
            ->with('company')
            ->where('is_active', true)
            ->where('status', 'active');
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->searchable()
                ->sortable()
                ->url(fn (Driver $record) => DriverResource::getUrl('edit', ['record' => $record])),
            Tables\Columns\TextColumn::make('company.name')
                ->label('Company')
                ->sortable(),
            Tables\Columns\TextColumn::make('phone')->toggleable(),
            Tables\Columns\TextColumn::make('status')->badge(),
            Tables\Columns\TextColumn::make('hire_date')->date()->label('Hired')->toggleable(),
        ];
    }

    protected function getTablePollingInterval(): ?string
    {
        return '30s';
    }
}
