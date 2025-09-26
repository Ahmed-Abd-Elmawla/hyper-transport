<?php

namespace App\Filament\Pages;

use App\Models\Company;
use App\Services\AvailabilityService;
use Carbon\Carbon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class AvailabilityLookup extends Page
{
    protected static ?string $navigationGroup = 'Pages';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    protected static ?string $navigationLabel = 'Availability';
    protected static ?string $title = 'Availability Lookup';

    // Point to the Blade view
    protected static string $view = 'filament.pages.availability-lookup';

    // Form state
    public ?int $company_id = null;
    public ?string $start_at = null;
    public ?string $end_at = null;

    // Results
    public array $availableDrivers = [];
    public array $availableVehicles = [];

    public function mount(): void
    {
        // Default company: first company (or null if none)
        $defaultCompanyId = \App\Models\Company::value('id');
        $this->company_id = $defaultCompanyId;

        // Default time range: now to +2 hours
        $tz = config('app.timezone');
        $start = \Carbon\Carbon::now($tz);
        $end = \Carbon\Carbon::now($tz)->addHours(2);

        $this->start_at = $start->format('Y-m-d H:i');
        $this->end_at = $end->format('Y-m-d H:i');

        // Keep tables empty until Search is clicked
        // $this->recalculate();
    }

    public function form(Form $form): Form
    {
        $tz = config('app.timezone');

        return $form
            ->schema([
                Select::make('company_id')
                    ->label('Company')
                    ->options(fn () => ['0' => 'All Companies'] + Company::pluck('name', 'id')->all())
                    ->required()
                    ->searchable(),

                DateTimePicker::make('start_at')
                    ->label('Start')
                    ->seconds(false)
                    ->native(false)
                    ->timezone($tz)
                    ->displayFormat('Y-m-d H:i')
                    ->required(),

                DateTimePicker::make('end_at')
                    ->label('End')
                    ->seconds(false)
                    ->native(false)
                    ->timezone($tz)
                    ->displayFormat('Y-m-d H:i'),
            ]);
            // Removed ->statePath('data') so fields bind directly to page properties
    }

    public function recalculate(): void
    {
        if ($this->company_id === null || ! $this->start_at) {
            $this->availableDrivers = [];
            $this->availableVehicles = [];
            return;
        }

        try {
            $tz = config('app.timezone');

            // Robust parsing
            $startRaw = is_string($this->start_at) ? trim($this->start_at) : $this->start_at;
            $endRaw = is_string($this->end_at) ? trim($this->end_at) : $this->end_at;

            $start = \Carbon\Carbon::parse($startRaw, $tz);
            $end = $endRaw ? \Carbon\Carbon::parse($endRaw, $tz) : null;

            if ($end && $end->lessThanOrEqualTo($start)) {
                throw new \InvalidArgumentException('End time must be after start time.');
            }

            $companyIds = $this->company_id === 0
                ? \App\Models\Company::pluck('id')->all()
                : [$this->company_id];

            // Aggregate across companies
            $drivers = collect();
            $vehicles = collect();

            foreach ($companyIds as $cid) {
                $drivers = $drivers->merge(\App\Services\AvailabilityService::getAvailableDrivers((int) $cid, $start, $end));
                $vehicles = $vehicles->merge(\App\Services\AvailabilityService::getAvailableVehicles((int) $cid, $start, $end));
            }

            // Ensure uniqueness and eager-load company to avoid N+1 queries
            $drivers = \Illuminate\Database\Eloquent\Collection::make($drivers->unique('id')->values()->all());
            $vehicles = \Illuminate\Database\Eloquent\Collection::make($vehicles->unique('id')->values()->all());

            $drivers->load('company');
            $vehicles->load('company');

            // Flatten to arrays and include company name (no Blade changes needed)
            $this->availableDrivers = $drivers->map(fn ($d) => [
                'id' => $d->id,
                'name' => $d->name,
                'status' => $d->status,
                'company' => $d->company?->name,
            ])->values()->all();

            $this->availableVehicles = $vehicles->map(fn ($v) => [
                'id' => $v->id,
                'brand' => $v->brand,
                'model' => $v->model,
                'status' => $v->status,
                'plate_number' => $v->plate_number ?? null,
                'company' => $v->company?->name,
            ])->values()->all();
        } catch (\Throwable $e) {
            \Filament\Notifications\Notification::make()
                ->title('Error')
                ->body('Failed to compute availability: ' . $e->getMessage())
                ->danger()
                ->send();

            $this->availableDrivers = [];
            $this->availableVehicles = [];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('search')
                ->label('Search')
                ->icon('heroicon-o-magnifying-glass')
                ->color('primary')
                ->action('recalculate'),
        ];
    }
}
