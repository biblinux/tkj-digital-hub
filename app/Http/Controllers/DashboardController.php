<?php

namespace App\Http\Controllers;

use App\Models\ItReport;
use App\Models\Suggestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $data = [
            'user' => $user,
            'myReports' => ItReport::where('user_id', $user->id)->latest()->limit(5)->get(),
            'myOpenCount' => ItReport::where('user_id', $user->id)
                ->whereNotIn('status', ItReport::FINAL_STATUSES)->count(),
        ];

        if ($user->hasPermission('handle_assigned_tickets') && ! $user->hasPermission('manage_tickets')) {
            $data['assigned'] = ItReport::where('assigned_to', $user->id)
                ->whereNotIn('status', ItReport::FINAL_STATUSES)
                ->orderByRaw("case urgency when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
                ->limit(8)
                ->get();
        }

        if ($user->hasPermission('manage_tickets')) {
            $data['ticketStats'] = [
                'open' => ItReport::where('status', 'open')->count(),
                'in_progress' => ItReport::whereIn('status', ['assigned', 'in_progress'])->count(),
                'resolved' => ItReport::whereIn('status', ['resolved', 'closed'])->count(),
                'unassigned' => ItReport::whereNull('assigned_to')
                    ->whereNotIn('status', ItReport::FINAL_STATUSES)->count(),
            ];
        }

        if ($user->hasPermission('manage_suggestions')) {
            $data['newSuggestions'] = Suggestion::where('status', 'new')->count();
        }

        if ($user->hasPermission('manage_users')) {
            $data['userStats'] = [
                'total' => User::count(),
                'active' => User::where('is_active', true)->count(),
                'pending' => User::where('is_active', false)->count(),
            ];
        }

        return view('dashboard', $data);
    }
}
