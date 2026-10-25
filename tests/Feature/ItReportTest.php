<?php

namespace Tests\Feature;

use App\Models\ItReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItReportTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $o = []): array
    {
        return $o + [
            'category' => 'network',
            'title' => 'WiFi lab mati',
            'description' => 'Sejak pagi koneksi WiFi lab 2 tidak tersedia.',
            'location' => 'Lab 2',
            'urgency' => 'high',
        ];
    }

    private function report(User $owner, array $attrs = []): ItReport
    {
        $r = new ItReport($this->payload());
        $r->user_id = $owner->id;
        $r->status = $attrs['status'] ?? 'open';
        $r->assigned_to = $attrs['assigned_to'] ?? null;
        $r->save();
        $r->reference = 'TKJ-2026-'.str_pad((string) $r->id, 5, '0', STR_PAD_LEFT);
        $r->save();

        return $r;
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/laporan')->assertRedirect('/login');
        $this->get('/laporan/buat')->assertRedirect('/login');
        $this->post('/laporan', $this->payload())->assertRedirect('/login');
    }

    public function test_student_creates_report_with_reference_and_history(): void
    {
        $student = $this->makeUser();

        $response = $this->actingAs($student)->post('/laporan', $this->payload());

        $report = ItReport::firstOrFail();
        $response->assertRedirect(route('reports.show', $report));

        $this->assertSame($student->id, $report->user_id);
        $this->assertSame('open', $report->status);
        $this->assertMatchesRegularExpression('/^TKJ-\d{4}-\d{5}$/', $report->reference);
        $this->assertDatabaseHas('it_report_updates', ['it_report_id' => $report->id, 'to_status' => 'open']);
    }

    public function test_input_is_validated_and_privileged_fields_are_ignored(): void
    {
        $student = $this->makeUser();
        $other = $this->makeUser(User::ROLE_TECHNICIAN);

        $this->actingAs($student)->post('/laporan', ['title' => 'x'])
            ->assertSessionHasErrors(['category', 'title', 'description', 'urgency']);

        $this->actingAs($student)->post('/laporan', $this->payload([
            'status' => 'closed', 'assigned_to' => $other->id, 'user_id' => $other->id,
        ]));

        $report = ItReport::firstOrFail();
        $this->assertSame('open', $report->status);
        $this->assertNull($report->assigned_to);
        $this->assertSame($student->id, $report->user_id);
    }

    public function test_student_sees_only_own_reports(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $mine = $this->report($a);
        $theirs = $this->report($b);

        $this->actingAs($a)->get('/laporan')->assertSee($mine->reference)->assertDontSee($theirs->reference);
        $this->actingAs($a)->get(route('reports.show', $mine))->assertOk();
    }

    public function test_student_cannot_read_or_change_another_students_report(): void
    {
        $a = $this->makeUser();
        $b = $this->makeUser();
        $theirs = $this->report($b);

        $this->actingAs($a)->get(route('reports.show', $theirs))->assertForbidden();
        $this->actingAs($a)->post(route('reports.comment', $theirs), ['note' => 'halo'])->assertForbidden();
        $this->actingAs($a)->post(route('reports.handle', $theirs), ['status' => 'closed'])->assertForbidden();

        $this->assertSame('open', $theirs->fresh()->status);
    }

    public function test_student_cannot_change_status_of_own_report(): void
    {
        $a = $this->makeUser();
        $mine = $this->report($a);

        $this->actingAs($a)->post(route('reports.handle', $mine), ['status' => 'closed'])->assertForbidden();
        $this->assertSame('open', $mine->fresh()->status);
    }

    public function test_reporter_can_add_note_until_report_is_final(): void
    {
        $a = $this->makeUser();
        $mine = $this->report($a);

        $this->actingAs($a)->post(route('reports.comment', $mine), ['note' => 'Masih mati.'])->assertSessionHas('success');
        $this->assertDatabaseHas('it_report_updates', ['it_report_id' => $mine->id, 'note' => 'Masih mati.']);

        $mine->status = 'closed';
        $mine->save();
        $this->actingAs($a)->post(route('reports.comment', $mine), ['note' => 'Lagi'])->assertForbidden();
    }

    public function test_technician_sees_only_assigned_reports(): void
    {
        $tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $student = $this->makeUser();
        $assigned = $this->report($student, ['assigned_to' => $tech->id, 'status' => 'assigned']);
        $other = $this->report($student);

        $this->actingAs($tech)->get(route('admin.reports.index'))
            ->assertSee($assigned->reference)->assertDontSee($other->reference);

        $this->actingAs($tech)->get(route('reports.show', $assigned))->assertOk();
        $this->actingAs($tech)->get(route('reports.show', $other))->assertForbidden();
    }

    public function test_technician_updates_status_with_note_and_history(): void
    {
        $tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $report = $this->report($this->makeUser(), ['assigned_to' => $tech->id, 'status' => 'assigned']);

        $this->actingAs($tech)->post(route('reports.handle', $report), [
            'status' => 'in_progress', 'note' => 'Mengecek access point.',
        ])->assertSessionHas('success');

        $this->assertSame('in_progress', $report->fresh()->status);
        $this->assertDatabaseHas('it_report_updates', [
            'it_report_id' => $report->id, 'from_status' => 'assigned', 'to_status' => 'in_progress',
            'user_id' => $tech->id, 'note' => 'Mengecek access point.',
        ]);

        $this->actingAs($tech)->post(route('reports.handle', $report), ['status' => 'resolved']);
        $this->assertNotNull($report->fresh()->resolved_at);
    }

    public function test_technician_cannot_reassign_or_touch_unassigned_report(): void
    {
        $tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $other = $this->makeUser(User::ROLE_TECHNICIAN);
        $mine = $this->report($this->makeUser(), ['assigned_to' => $tech->id, 'status' => 'assigned']);
        $notMine = $this->report($this->makeUser());

        $this->actingAs($tech)->post(route('reports.handle', $mine), [
            'status' => 'assigned', 'assigned_to' => $other->id,
        ])->assertForbidden();
        $this->assertSame($tech->id, $mine->fresh()->assigned_to);

        $this->actingAs($tech)->post(route('reports.handle', $notMine), ['status' => 'closed'])->assertForbidden();
    }

    public function test_admin_assigns_technician_and_status_moves_to_assigned(): void
    {
        $admin = $this->makeAdmin();
        $tech = $this->makeUser(User::ROLE_TECHNICIAN);
        $report = $this->report($this->makeUser());

        $this->actingAs($admin)->post(route('reports.handle', $report), [
            'status' => 'open', 'assigned_to' => $tech->id,
        ])->assertSessionHas('success');

        $report->refresh();
        $this->assertSame($tech->id, $report->assigned_to);
        $this->assertSame('assigned', $report->status);
    }

    public function test_admin_cannot_assign_to_non_technician_or_inactive_technician(): void
    {
        $admin = $this->makeAdmin();
        $student = $this->makeUser();
        $inactiveTech = $this->makeUser(User::ROLE_TECHNICIAN, false);
        $report = $this->report($this->makeUser());

        foreach ([$student, $inactiveTech] as $bad) {
            $this->actingAs($admin)->post(route('reports.handle', $report), [
                'status' => 'open', 'assigned_to' => $bad->id,
            ])->assertStatus(422);
        }

        $this->assertNull($report->fresh()->assigned_to);
    }

    public function test_admin_without_ticket_permission_cannot_manage_reports(): void
    {
        $admin = $this->makeAdmin(['manage_users']);
        $report = $this->report($this->makeUser());

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.show', $report))->assertForbidden();
        $this->actingAs($admin)->post(route('reports.handle', $report), ['status' => 'closed'])->assertForbidden();
    }

    public function test_invalid_status_is_rejected_and_noop_is_reported(): void
    {
        $admin = $this->makeAdmin();
        $report = $this->report($this->makeUser());

        $this->actingAs($admin)->post(route('reports.handle', $report), ['status' => 'hancur'])
            ->assertSessionHasErrors('status');

        $this->actingAs($admin)->post(route('reports.handle', $report), ['status' => 'open'])
            ->assertSessionHas('error');
    }

    public function test_internal_notes_are_hidden_from_reporter(): void
    {
        $student = $this->makeUser();
        $admin = $this->makeAdmin();
        $report = $this->report($student);

        $this->actingAs($admin)->post(route('reports.handle', $report), [
            'status' => 'in_progress', 'note' => 'RAHASIA-INTERNAL', 'is_internal' => '1',
        ]);
        $this->actingAs($admin)->post(route('reports.handle', $report), [
            'status' => 'in_progress', 'note' => 'Catatan untuk pelapor',
        ]);

        $this->actingAs($student)->get(route('reports.show', $report))
            ->assertSee('Catatan untuk pelapor')->assertDontSee('RAHASIA-INTERNAL');

        $this->actingAs($admin)->get(route('reports.show', $report))->assertSee('RAHASIA-INTERNAL');
    }

    public function test_staff_list_filters_by_status(): void
    {
        $admin = $this->makeAdmin();
        $open = $this->report($this->makeUser());
        $done = $this->report($this->makeUser(), ['status' => 'resolved']);

        $this->actingAs($admin)->get(route('admin.reports.index', ['status' => 'resolved']))
            ->assertSee($done->reference)->assertDontSee($open->reference);
    }
}
