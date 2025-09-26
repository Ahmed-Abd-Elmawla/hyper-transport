<?php

namespace App\Filament\Resources\VehicleResource\Pages;

use App\Filament\Resources\VehicleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditVehicle extends EditRecord
{
    protected static string $resource = VehicleResource::class;

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
        // Show warning if vehicle is in use
        if ($this->record->status === 'in_use') {
            Notification::make()
                ->warning()
                ->title('Vehicle In Use')
                ->body('This vehicle is currently in use. The status field cannot be changed.')
                ->persistent()
                ->send();

            // Prevent status changes for vehicles that are in use
            if (isset($data['status']) && $data['status'] !== $this->record->status) {
                unset($data['status']);

                Notification::make()
                    ->danger()
                    ->title('Status Change Blocked')
                    ->body('Cannot change status of a vehicle that is currently in use.')
                    ->send();
            }
        }

        return $data;
    }
}
