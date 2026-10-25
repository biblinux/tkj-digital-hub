<?php

namespace App\Http\Controllers;

use App\Models\ItReport;
use App\Models\ItReportUpdate;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ItReportController extends Controller
{
    /** Laporan milik pengguna yang sedang login. */
    public function index(Request $request): View
    {
        $reports = ItReport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return view('reports.index', ['reports' => $reports]);
    }

    public function create(): View
    {
        Gate::authorize('create', ItReport::class);

        return view('reports.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', ItReport::class);

        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(ItReport::CATEGORIES))],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'location' => ['nullable', 'string', 'max:150'],
            'urgency' => ['required', Rule::in(array_keys(ItReport::URGENCIES))],
        ]);

        $report = DB::transaction(function () use ($validated, $request): ItReport {
            $report = new ItReport($validated);
            $report->user_id = $request->user()->id;
            $report->status = 'open';
            $report->save();

            $report->reference = sprintf('TKJ-%s-%05d', now()->format('Y'), $report->id);
            $report->save();

            ItReportUpdate::create([
                'it_report_id' => $report->id,
                'user_id' => $request->user()->id,
                'from_status' => null,
                'to_status' => 'open',
                'note' => 'Laporan dibuat.',
                'is_internal' => false,
            ]);

            return $report;
        });

        return redirect()
            ->route('reports.show', $report)
            ->with('success', 'Laporan terkirim dengan nomor '.$report->reference.'.');
    }

    public function show(Request $request, ItReport $report): View
    {
        Gate::authorize('view', $report);

        $canSeeInternal = $request->user()->can('viewInternalNotes', $report);

        $report->load([
            'reporter:id,name,username,display_name',
            'assignee:id,name,username,display_name',
        ]);

        $updates = $report->updates()
            ->with('author:id,name,display_name,role')
            ->when(! $canSeeInternal, fn ($q) => $q->where('is_internal', false))
            ->get();

        $technicians = collect();
        if ($request->user()->can('assign', $report)) {
            $technicians = User::query()
                ->where('role', User::ROLE_TECHNICIAN)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'display_name']);
        }

        return view('reports.show', [
            'report' => $report,
            'updates' => $updates,
            'technicians' => $technicians,
            'canHandle' => $request->user()->can('handle', $report),
            'canAssign' => $request->user()->can('assign', $report),
            'isOwner' => $report->user_id === $request->user()->id,
        ]);
    }

    /** Pelapor menambahkan keterangan pada laporannya sendiri. */
    public function comment(Request $request, ItReport $report): RedirectResponse
    {
        Gate::authorize('view', $report);

        abort_unless($report->user_id === $request->user()->id, 403);
        abort_if($report->isFinal(), 403, 'Laporan sudah ditutup.');

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        ItReportUpdate::create([
            'it_report_id' => $report->id,
            'user_id' => $request->user()->id,
            'note' => $validated['note'],
            'is_internal' => false,
        ]);

        return back()->with('success', 'Keterangan ditambahkan.');
    }

    /** Daftar laporan untuk teknisi dan admin. */
    public function staffIndex(Request $request): View
    {
        Gate::authorize('viewStaffList', ItReport::class);

        $user = $request->user();

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(ItReport::STATUSES))],
            'urgency' => ['nullable', Rule::in(array_keys(ItReport::URGENCIES))],
            'q' => ['nullable', 'string', 'max:100'],
        ]);

        $reports = ItReport::query()
            ->with(['reporter:id,name,display_name', 'assignee:id,name,display_name'])
            // Tanpa izin manage_tickets, hanya laporan yang ditugaskan padanya.
            ->when(! $user->hasPermission('manage_tickets'),
                fn ($q) => $q->where('assigned_to', $user->id))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['urgency'] ?? null, fn ($q, $v) => $q->where('urgency', $v))
            ->when($filters['q'] ?? null, function ($q, $term) {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $term).'%';
                $q->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('reference', 'like', $like));
            })
            ->orderByRaw("case urgency when 'urgent' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reports.index', [
            'reports' => $reports,
            'filters' => $filters,
        ]);
    }

    /** Ubah status, tugaskan teknisi, dan/atau tambah catatan. */
    public function handle(Request $request, ItReport $report): RedirectResponse
    {
        Gate::authorize('handle', $report);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(ItReport::STATUSES))],
            'assigned_to' => ['nullable', 'integer'],
            'note' => ['nullable', 'string', 'max:2000'],
            'is_internal' => ['nullable', 'boolean'],
        ]);

        $actor = $request->user();
        $newAssignee = $report->assigned_to;

        if ($request->has('assigned_to')) {
            // Hanya pengelola tiket yang boleh mengganti penugasan.
            $requested = isset($validated['assigned_to']) && $validated['assigned_to'] !== ''
                ? (int) $validated['assigned_to']
                : null;

            if ($requested !== $report->assigned_to) {
                Gate::authorize('assign', $report);

                if ($requested !== null) {
                    $valid = User::query()
                        ->whereKey($requested)
                        ->where('role', User::ROLE_TECHNICIAN)
                        ->where('is_active', true)
                        ->exists();

                    abort_unless($valid, 422, 'Teknisi yang dipilih tidak valid.');
                }

                $newAssignee = $requested;
            }
        }

        $newStatus = $validated['status'];

        // Menugaskan laporan yang masih terbuka otomatis mengubah statusnya.
        if ($newAssignee !== $report->assigned_to && $newAssignee !== null && $newStatus === 'open') {
            $newStatus = 'assigned';
        }

        $note = isset($validated['note']) ? trim($validated['note']) : '';
        $statusChanged = $newStatus !== $report->status;
        $assigneeChanged = $newAssignee !== $report->assigned_to;

        if (! $statusChanged && ! $assigneeChanged && $note === '') {
            return back()->with('error', 'Tidak ada perubahan yang disimpan.');
        }

        DB::transaction(function () use ($report, $actor, $newStatus, $newAssignee, $note, $statusChanged, $assigneeChanged, $validated) {
            $fromStatus = $report->status;

            $report->status = $newStatus;
            $report->assigned_to = $newAssignee;
            $report->resolved_at = in_array($newStatus, ItReport::FINAL_STATUSES, true)
                ? ($report->resolved_at ?? now())
                : null;
            $report->save();

            if ($assigneeChanged) {
                $name = $newAssignee ? User::find($newAssignee)?->shownName() : null;

                ItReportUpdate::create([
                    'it_report_id' => $report->id,
                    'user_id' => $actor->id,
                    'note' => $name ? 'Ditugaskan kepada '.$name.'.' : 'Penugasan dibatalkan.',
                    'is_internal' => false,
                ]);
            }

            if ($statusChanged || $note !== '') {
                ItReportUpdate::create([
                    'it_report_id' => $report->id,
                    'user_id' => $actor->id,
                    'from_status' => $statusChanged ? $fromStatus : null,
                    'to_status' => $statusChanged ? $newStatus : null,
                    'note' => $note !== '' ? $note : null,
                    'is_internal' => $note !== '' && (bool) ($validated['is_internal'] ?? false),
                ]);
            }
        });

        return back()->with('success', 'Perubahan laporan disimpan.');
    }
}
