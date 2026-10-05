<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportController extends Controller
{
    public const PERIODS = ['6bulan' => '6 bulan terakhir', 'bulan' => 'Bulan ini', 'tahun' => 'Tahun ini'];

    public function __construct(private ReportService $reports, private DashboardService $dashboard) {}

    public function index(Request $request): View
    {
        return view('reports.index', $this->data($request));
    }

    public function print(Request $request): View
    {
        return view('reports.print', $this->data($request));
    }

    private function data(Request $request): array
    {
        $period = array_key_exists($request->query('periode'), self::PERIODS) ? $request->query('periode') : '6bulan';
        [$from, $to] = $this->reports->range($period);
        $monthFrom = now()->startOfMonth();
        $lastMonthFrom = now()->subMonthNoOverflow()->startOfMonth();

        $thisMonth = $this->dashboard->revenue(now());
        $lastMonth = $this->dashboard->revenue(now()->subMonthNoOverflow());
        $chartFrom = $period === 'bulan' ? now()->subMonthsNoOverflow(5)->startOfMonth() : $from;

        $portionsLastMonth = $this->reports->portionsPerLine($lastMonthFrom, $lastMonthFrom->copy()->endOfMonth());

        return [
            'period' => $period,
            'periods' => self::PERIODS,
            'from' => $from,
            'to' => $to,
            'revenueThisMonth' => $thisMonth,
            'revenueDelta' => $lastMonth ? round(($thisMonth - $lastMonth) / $lastMonth * 100, 1) : null,
            'chart' => $this->reports->monthlyRevenue($chartFrom, $to),
            'portionsLine' => $portionsLastMonth,
            'portionsMonthLabel' => month_id($lastMonthFrom->month, false),
            'portionsTotal' => array_sum($portionsLastMonth),
            'portionsDaily' => (int) round(array_sum($portionsLastMonth) / $lastMonthFrom->daysInMonth),
            'totals' => $this->reports->totals($from, $to),
            'topMenus' => $this->reports->topMenus($from, $to),
            'profitability' => $this->reports->profitability($from, $to),
            'monthFrom' => $monthFrom,
        ];
    }
}
