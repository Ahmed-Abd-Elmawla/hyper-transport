<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditTrip extends EditRecord
{
    protected static string $resource = TripResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->outlined(),
        ];
    }

    protected function getSaveFormAction(): Actions\Action
    {
        return parent::getSaveFormAction()
            ->label('Save Changes')
            ->icon('heroicon-o-check-circle');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel')
            ->icon('heroicon-o-x-circle')
            ->color('gray');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Show warning if trip is in progress
        if ($this->record->status === 'in_progress') {
            Notification::make()
                ->warning()
                ->title('Trip In Progress')
                ->body('This trip is currently in progress. No changes can be made to trip details.')
                ->persistent()
                ->send();

            // Prevent any changes for trips that are in progress
            Notification::make()
                ->danger()
                ->title('Changes Blocked')
                ->body('Cannot modify a trip that is currently in progress.')
                ->send();

            // Return original data to prevent any changes
            return $this->record->toArray();
        }

        return $data;
    }
}
