<?php

namespace App\Filament\Resources\TripResource\Pages;

use App\Filament\Resources\TripResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTrip extends CreateRecord
{
    protected static string $resource = TripResource::class;

    protected function getCreateFormAction(): Actions\Action
    {
        return parent::getCreateFormAction()
            ->label('Create Trip')
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
