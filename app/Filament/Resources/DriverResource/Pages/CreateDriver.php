<?php

namespace App\Filament\Resources\DriverResource\Pages;

use App\Filament\Resources\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Driver')
            ->icon('heroicon-o-check-circle');
    }

    protected function getCreateAnotherFormAction(): Actions\Action
    {
        return parent::getCreateAnotherFormAction()
            ->label('Create & Create Another')
            ->icon('heroicon-o-plus-circle')
            ->color('gray');
    }

    protected function getCancelFormAction(): Actions\Action
    {
        return parent::getCancelFormAction()
            ->label('Cancel')
            ->icon('heroicon-o-x-circle')
            ->color('gray');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
