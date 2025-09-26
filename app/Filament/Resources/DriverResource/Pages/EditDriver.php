<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;

class EditDriver extends EditRecord
{
    protected static string $resource = DriverResource::class;

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

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Show notification when viewing driver with inactive status
        if ($this->record->status === 'inactive') {
            Notification::make()
                ->title('Status is locked')
                ->body('The status field cannot be changed when it is set to inactive.')
                ->warning()
                ->send();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // If current status is inactive, prevent changing the status field
        if ($this->record->status === 'inactive' && isset($data['status']) && $data['status'] !== 'inactive') {
            // Remove status from data to prevent change
            unset($data['status']);

            Notification::make()
                ->title('Status change blocked')
                ->body('Cannot change status when it is currently set to inactive.')
                ->danger()
                ->send();
        }

        return $data;
    }
}
