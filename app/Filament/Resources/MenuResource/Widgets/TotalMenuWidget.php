<?php

namespace App\Filament\Resources\MenuResource\Widgets;

use App\Models\Menu;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TotalMenuWidget extends BaseWidget
{
    protected function getStats(): array
    {
        // Get the total count of menus
        $menuCount = Menu::count();

        return [
            Stat::make('Total Menus', $menuCount)
                ->color('primary')
                ->icon('heroicon-o-bars-3'), // Change this to an appropriate icon
        ];
    }
}
