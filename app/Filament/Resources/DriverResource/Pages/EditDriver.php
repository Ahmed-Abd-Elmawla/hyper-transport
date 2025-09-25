<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

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
}
