<?php

namespace App\Services;

use App\Models\ItReport;
use App\Models\ItReportUpdate;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Logika pembuatan laporan IT yang dipakai bersama oleh web dan API,
 * agar aturan (nomor referensi, riwayat awal) tidak berbeda antar jalur.
 */
class ItReportService
{
    /**
     * @param  array{category:string,title:string,description:string,location?:?string,urgency:string}  $data
     */
    public function create(User $reporter, array $data): ItReport
    {
        return DB::transaction(function () use ($reporter, $data): ItReport {
            $report = new ItReport($data);
            $report->user_id = $reporter->id;
            $report->status = 'open';
            $report->save();

            $report->reference = sprintf('TKJ-%s-%05d', now()->format('Y'), $report->id);
            $report->save();

            ItReportUpdate::create([
                'it_report_id' => $report->id,
                'user_id' => $reporter->id,
                'from_status' => null,
                'to_status' => 'open',
                'note' => 'Laporan dibuat.',
                'is_internal' => false,
            ]);

            return $report;
        });
    }

    public function addReporterNote(User $reporter, ItReport $report, string $note): ItReportUpdate
    {
        return ItReportUpdate::create([
            'it_report_id' => $report->id,
            'user_id' => $reporter->id,
            'note' => $note,
            'is_internal' => false,
        ]);
    }
}
