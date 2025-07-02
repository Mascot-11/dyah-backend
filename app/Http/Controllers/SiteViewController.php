<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Jobs\LogSiteView;
use App\Models\SiteView;
use Illuminate\Support\Facades\DB;

class SiteViewController extends Controller
{
    // Log site view (already done)
    public function logView(Request $request)
    {
        $path = $request->input('path', '/');
        LogSiteView::dispatch($request->ip(), $request->header('User-Agent'), $path);

        return response()->json(['message' => 'Queued for logging']);
    }

    // Paginated detailed views with filters
    public function allViews(Request $request)
    {
        $query = SiteView::query();

        if ($request->startDate) {
            $query->where('view_date', '>=', $request->startDate);
        }
        if ($request->endDate) {
            $query->where('view_date', '<=', $request->endDate);
        }
        if ($request->path) {
            $query->where('path', $request->path);
        }

        $views = $query->orderBy('created_at', 'desc')->paginate(50);

        return response()->json($views);
    }

    // Daily views analytics with filters
    public function viewsAnalyticsDaily(Request $request)
    {
        $query = SiteView::select(DB::raw('view_date as date, COUNT(*) as views'))
            ->groupBy('view_date')
            ->orderBy('view_date');

        if ($request->startDate) {
            $query->where('view_date', '>=', $request->startDate);
        }
        if ($request->endDate) {
            $query->where('view_date', '<=', $request->endDate);
        }
        if ($request->path) {
            $query->where('path', $request->path);
        }

        $data = $query->get();

        return response()->json($data);
    }

    // Monthly views analytics with filters
    public function viewsAnalyticsMonthly(Request $request)
    {
        $query = SiteView::select(
            DB::raw("DATE_FORMAT(view_date, '%Y-%m') as month"),
            DB::raw('COUNT(*) as views')
        )
        ->groupBy('month')
        ->orderBy('month');

        if ($request->startDate) {
            $query->where('view_date', '>=', $request->startDate);
        }
        if ($request->endDate) {
            $query->where('view_date', '<=', $request->endDate);
        }
        if ($request->path) {
            $query->where('path', $request->path);
        }

        $data = $query->get();

        return response()->json($data);
    }

    // Top visited paths analytics with filters
    public function viewsAnalyticsTopPaths(Request $request)
    {
        $query = SiteView::select('path', DB::raw('COUNT(*) as views'))
            ->groupBy('path')
            ->orderByDesc('views')
            ->limit(10);

        if ($request->startDate) {
            $query->where('view_date', '>=', $request->startDate);
        }
        if ($request->endDate) {
            $query->where('view_date', '<=', $request->endDate);
        }
        if ($request->path) {
            $query->where('path', $request->path);
        }

        $data = $query->get();

        return response()->json($data);
    }
}
