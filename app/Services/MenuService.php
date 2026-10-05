<?php

namespace App\Services;

use App\Models\Menu;
use App\Models\MenuRotation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MenuService
{
    /**
     * Minggu ke-1..4 dari siklus, dihitung dari minggu ISO.
     */
    public function cycleWeek(Carbon $date): int
    {
        return (($date->isoWeek - 1) % 4) + 1;
    }

    public function menuForDate(Carbon $date): ?Menu
    {
        if ($date->isWeekend()) {
            return null;
        }

        return MenuRotation::query()
            ->where('week', $this->cycleWeek($date))
            ->where('weekday', $date->dayOfWeekIso)
            ->first()?->menu;
    }

    /**
     * Grid rotasi [minggu => [hari => MenuRotation]].
     *
     * @return Collection<int, Collection<int, MenuRotation>>
     */
    public function rotationGrid(): Collection
    {
        return MenuRotation::with('menu')->orderBy('week')->orderBy('weekday')->get()
            ->groupBy('week')
            ->map(fn (Collection $days) => $days->keyBy('weekday'));
    }

    /**
     * @return Collection<int, Menu>
     */
    public function weekMenus(Carbon $date): Collection
    {
        return MenuRotation::with('menu')
            ->where('week', $this->cycleWeek($date))
            ->orderBy('weekday')
            ->get()
            ->map->menu;
    }
}
