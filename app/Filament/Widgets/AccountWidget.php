<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class AccountWidget extends Widget
{
    protected static string $view = 'filament.widgets.account-widget';
    protected static bool $isLazy = false;
    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }
}
