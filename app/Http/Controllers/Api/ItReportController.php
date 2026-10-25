<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ItReportResource;
use App\Models\ItReport;
use App\Services\ItReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Laporan IT untuk pelapor (siswa dan pengguna lain). Pengelolaan oleh
 * teknisi dan admin tetap dilakukan lewat aplikasi Laravel.
 */
class ItReportController extends Controller
{
    public function index(Request $request): \Illuminate\Http\Resources\Json\AnonymousResourceCollection
    {
        $reports = ItReport::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate(10);

        return ItReportResource::collection($reports);
    }

    public function store(Request $request, ItReportService $service): JsonResponse
    {
        Gate::authorize('create', ItReport::class);

        $validated = $request->validate([
            'category' => ['required', Rule::in(array_keys(ItReport::CATEGORIES))],
            'title' => ['required', 'string', 'min:5', 'max:150'],
            'description' => ['required', 'string', 'min:10', 'max:3000'],
            'location' => ['nullable', 'string', 'max:150'],
            'urgency' => ['required', Rule::in(array_keys(ItReport::URGENCIES))],
        ]);

        $report = $service->create($request->user(), $validated);
        $report->load('updates.author:id,name,display_name');

        return (new ItReportResource($report))->response()->setStatusCode(201);
    }

    public function show(Request $request, ItReport $report): ItReportResource
    {
        // Pelapor hanya boleh melihat laporannya sendiri. Laporan milik
        // orang lain dijawab 404 agar keberadaannya tidak bocor.
        abort_unless($report->user_id === $request->user()->id, 404);

        $report->load([
            'updates' => fn ($q) => $q->where('is_internal', false),
            'updates.author:id,name,display_name',
        ]);

        return new ItReportResource($report);
    }

    public function comment(Request $request, ItReport $report, ItReportService $service): JsonResponse
    {
        abort_unless($report->user_id === $request->user()->id, 404);
        abort_if($report->isFinal(), 403, 'Laporan sudah ditutup.');

        $validated = $request->validate([
            'note' => ['required', 'string', 'min:2', 'max:2000'],
        ]);

        $service->addReporterNote($request->user(), $report, $validated['note']);

        return response()->json(['message' => 'Keterangan ditambahkan.'], 201);
    }
}
