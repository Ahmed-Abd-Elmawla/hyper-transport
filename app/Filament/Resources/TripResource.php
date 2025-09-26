<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TripResource\Pages;
use App\Filament\Resources\TripResource\RelationManagers;
use App\Models\Trip;
use App\Models\Company;
use App\Models\Driver;
use App\Models\Vehicle;
use App\Services\AvailabilityService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Carbon\Carbon;

class TripResource extends Resource
{
    protected static ?string $model = Trip::class;

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-map';

    public static function form(Form $form): Form
    {
        $tz = config('app.timezone');
        $now = \Carbon\Carbon::now($tz);

        return $form
            ->schema([
                Forms\Components\Section::make('Schedule & Status')
                    ->icon('heroicon-o-clock')
                    ->iconColor('primary')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('start_at')
                                    ->label('Start Date & Time')
                                    ->icon('heroicon-o-play-circle')
                                    ->required()
                                    ->placeholder('Select start date and time')
                                    ->native(false)
                                    ->displayFormat('Y-m-d H:i')
                                    ->seconds(false)
                                    ->timezone($tz)
                                    ->default($now)          // set to current datetime
                                    ->minDate($now)          // disable any past datetime
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        if ($state) {
                                            $set('end_at', null);
                                            $set('driver_id', null);
                                            $set('vehicle_id', null);
                                        }
                                    })
                                    ->disabled(fn ($record) => $record && $record->status === 'in_progress'),

                                Forms\Components\DateTimePicker::make('end_at')
                                    ->label('End Date & Time')
                                    ->icon('heroicon-o-stop-circle')
                                    ->placeholder('Select end date and time')
                                    ->native(false)
                                    ->displayFormat('Y-m-d H:i')
                                    ->seconds(false)
                                    ->timezone($tz)
                                    ->default($now)          // set to current datetime
                                    ->minDate($now)          // disable any past datetime
                                    ->after('start_at')
                                    ->disabled(fn (callable $get, $record) => !$get('start_at') || ($record && $record->status === 'in_progress'))
                                    ->live()
                                    ->afterStateUpdated(function (callable $set, $state) {
                                        $set('driver_id', null);
                                        $set('vehicle_id', null);
                                    }),

                                Forms\Components\Select::make('status')
                                    ->label('Trip Status')
                                    ->prefixIcon('heroicon-o-flag')
                                    ->required()
                                    ->default('scheduled')
                                    ->options([
                                        'scheduled' => 'Scheduled',
                                        'in_progress' => 'In Progress',
                                        'completed' => 'Completed',
                                        'cancelled' => 'Cancelled',
                                        'delayed' => 'Delayed',
                                    ])
                                    ->placeholder('Select trip status')
                                    ->disabled(fn ($record) => $record && $record->status === 'in_progress'),
                            ]),

                        Forms\Components\Textarea::make('notes')
                            ->label('Trip Notes')
                            ->placeholder('Add any additional notes about this trip...')
                            ->rows(3)
                            ->columnSpanFull()
                            ->disabled(fn ($record) => $record && $record->status === 'in_progress'),
                    ]),

                Forms\Components\Section::make('Trip Information')
                    ->icon('heroicon-o-information-circle')
                    ->iconColor('primary')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('company_id')
                                    ->label('Company')
                                    ->prefixIcon('heroicon-o-building-office-2')
                                    ->relationship('company', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->placeholder('Select a company')
                                    ->live()
                                    ->afterStateUpdated(function (callable $set) {
                                        $set('driver_id', null);
                                        $set('vehicle_id', null);
                                    })
                                    ->disabled(fn ($record) => $record && $record->status === 'in_progress'),

                                Forms\Components\Select::make('driver_id')
                                    ->label('Driver')
                                    ->prefixIcon('heroicon-o-user-circle')
                                    ->options(function (callable $get, ?Trip $record) {
                                        $companyId = $get('company_id');
                                        $startAt = $get('start_at');
                                        $endAt = $get('end_at');

                                        if (!$companyId || !$startAt) {
                                            return [];
                                        }

                                        $availableDrivers = AvailabilityService::getAvailableDrivers(
                                            $companyId,
                                            Carbon::parse($startAt),
                                            $endAt ? Carbon::parse($endAt) : null,
                                            $record?->id
                                        );

                                        return $availableDrivers->pluck('name', 'id')->toArray();
                                    })
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select a driver')
                                    ->disabled(fn (callable $get, $record) => !$get('company_id') || !$get('start_at') || ($record && $record->status === 'in_progress'))
                                    ->live(),

                                Forms\Components\Select::make('vehicle_id')
                                    ->label('Vehicle')
                                    ->prefixIcon('heroicon-o-truck')
                                    ->options(function (callable $get, ?Trip $record) {
                                        $companyId = $get('company_id');
                                        $startAt = $get('start_at');
                                        $endAt = $get('end_at');

                                        if (!$companyId || !$startAt) {
                                            return [];
                                        }

                                        $availableVehicles = AvailabilityService::getAvailableVehicles(
                                            $companyId,
                                            Carbon::parse($startAt),
                                            $endAt ? Carbon::parse($endAt) : null,
                                            $record?->id
                                        );

                                        return $availableVehicles->mapWithKeys(function ($vehicle) {
                                            $colorCircle = '<div class="inline-block w-3 h-3 rounded-full mr-2" style="background-color: ' . $vehicle->color . '; border: 1px solid ' . $vehicle->color . ';"></div>';
                                            $vehicleInfo = $vehicle->brand . ' ' . $vehicle->model . ' (' . $vehicle->plate_number . ')';
                                            return [$vehicle->id => $colorCircle . ' ' . $vehicleInfo];
                                        })->toArray();
                                    })
                                    ->allowHtml()
                                    ->searchable()
                                    ->required()
                                    ->placeholder('Select a vehicle')
                                    ->disabled(fn (callable $get, $record) => !$get('company_id') || !$get('start_at') || ($record && $record->status === 'in_progress'))
                                    ->live(),

                                Forms\Components\TextInput::make('destination')
                                    ->label('Destination')
                                    ->prefixIcon('heroicon-o-map-pin')
                                    ->required()
                                    ->maxLength(255)
                                    ->placeholder('Enter destination address')
                                    ->disabled(fn ($record) => $record && $record->status === 'in_progress'),
                            ]),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('driver.name')
                    ->label('Driver')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('vehicle_display')
                    ->label('Vehicle')
                    ->getStateUsing(fn (Trip $record): string =>
                        $record->vehicle ? "{$record->vehicle->brand} {$record->vehicle->model} ({$record->vehicle->plate_number})" : 'N/A'
                    )
                    // ->searchable(['vehicle.brand', 'vehicle.model', 'vehicle.plate_number'])
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->searchable()
                    ->limit(30)
                    ->tooltip(fn (Trip $record): string => $record->destination)
                    ->toggleable(),

                Tables\Columns\TextColumn::make('start_at')
                    ->label('Start Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('end_at')
                    ->label('End Time')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->placeholder('Not completed')
                    ->toggleable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->icon('heroicon-o-flag')
                    ->colors([
                        'warning' => 'scheduled',
                        'primary' => 'in_progress',
                        'success' => 'completed',
                        'danger' => 'cancelled',
                        'gray' => 'delayed',
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst(str_replace('_', ' ', $state)))
                    ->sortable()
                    ->toggleable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y g:i A')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('company_id')
                    ->label('Company')
                    ->relationship('company', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('driver_id')
                    ->label('Driver')
                    ->relationship('driver', 'name')
                    ->searchable()
                    ->preload(),

                Tables\Filters\SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'scheduled' => 'Scheduled',
                        'in_progress' => 'In Progress',
                        'completed' => 'Completed',
                        'cancelled' => 'Cancelled',
                        'delayed' => 'Delayed',
                    ]),

                Tables\Filters\Filter::make('start_date')
                    ->form([
                        Forms\Components\DatePicker::make('start_from')
                            ->label('Start Date From'),
                        Forms\Components\DatePicker::make('start_until')
                            ->label('Start Date Until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_at', '>=', $date),
                            )
                            ->when(
                                $data['start_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->icon('heroicon-o-pencil-square'),
                Tables\Actions\DeleteAction::make()
                    ->icon('heroicon-o-trash'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->icon('heroicon-o-trash'),
                ]),
            ])
            ->defaultSort('start_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrips::route('/'),
            'create' => Pages\CreateTrip::route('/create'),
            'edit' => Pages\EditTrip::route('/{record}/edit'),
        ];
    }
}
