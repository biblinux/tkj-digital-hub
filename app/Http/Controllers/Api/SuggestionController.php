<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Suggestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuggestionController extends Controller
{
    private const THANKS = 'Terima kasih, saran Anda sudah kami terima.';

    public function store(Request $request): JsonResponse
    {
        // Honeypot: bot yang mengisi kolom tersembunyi dianggap berhasil
        // tanpa menyimpan apa pun.
        if (filled($request->input('website'))) {
            return response()->json(['message' => self::THANKS], 201);
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $suggestion = new Suggestion($validated);
        $suggestion->status = 'new';
        $suggestion->save();

        return response()->json(['message' => self::THANKS], 201);
    }
}
