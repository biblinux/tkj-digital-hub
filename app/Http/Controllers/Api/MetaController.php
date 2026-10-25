<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ItReport;
use Illuminate\Http\JsonResponse;

/** Daftar pilihan untuk mengisi formulir di frontend. */
class MetaController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'report_categories' => ItReport::CATEGORIES,
            'report_urgencies' => ItReport::URGENCIES,
            'report_statuses' => ItReport::STATUSES,
        ]);
    }
}
