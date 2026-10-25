<?php

namespace App\Http\Controllers;

use App\Models\Suggestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SuggestionController extends Controller
{
    /** Formulir publik. */
    public function create(): View
    {
        return view('suggestions.create');
    }

    public function store(Request $request): RedirectResponse
    {
        // Honeypot: bot biasanya mengisi kolom tersembunyi. Permintaan
        // diperlakukan seolah berhasil agar bot tidak tahu ditolak.
        if (filled($request->input('website'))) {
            return redirect()->route('suggestions.create')
                ->with('success', 'Terima kasih, saran Anda sudah kami terima.');
        }

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:100'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'subject' => ['required', 'string', 'min:3', 'max:150'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $suggestion = new Suggestion($validated);
        $suggestion->user_id = $request->user()?->id;
        $suggestion->status = 'new';
        $suggestion->save();

        return redirect()->route('suggestions.create')
            ->with('success', 'Terima kasih, saran Anda sudah kami terima.');
    }

    /** Daftar saran untuk pengelola. */
    public function index(Request $request): View
    {
        $this->authorizeManage($request);

        $filters = $request->validate([
            'status' => ['nullable', Rule::in(array_keys(Suggestion::STATUSES))],
        ]);

        $suggestions = Suggestion::query()
            ->with('user:id,name,username')
            ->when($filters['status'] ?? null, fn ($q, $s) => $q->where('status', $s))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('admin.suggestions.index', [
            'suggestions' => $suggestions,
            'filters' => $filters,
        ]);
    }

    public function update(Request $request, Suggestion $suggestion): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'status' => ['required', Rule::in(array_keys(Suggestion::STATUSES))],
            'admin_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $suggestion->status = $validated['status'];
        $suggestion->admin_note = $validated['admin_note'] ?? null;
        $suggestion->handled_by = $request->user()->id;
        $suggestion->handled_at = now();
        $suggestion->save();

        return back()->with('success', 'Tindak lanjut saran disimpan.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()?->hasPermission('manage_suggestions'),
            403,
            'Kamu tidak memiliki izin untuk mengelola saran.'
        );
    }
}
