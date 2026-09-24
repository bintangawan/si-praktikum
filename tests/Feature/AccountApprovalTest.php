<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AccountApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_waits_for_staff_without_email_notification_or_role_escalation(): void
    {
        Notification::fake();
        $this->post('/register', [
            'id' => '0701231001', 'name' => 'Mahasiswa Baru', 'email' => 'baru@example.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'Laboran', 'approved_at' => now(),
        ])->assertRedirect(route('account.pending', absolute: false));
        $user = User::findOrFail('0701231001');
        $this->assertSame('Mahasiswa', $user->role);
        $this->assertNull($user->approved_at);
        $this->assertNull($user->email_verified_at);
        Notification::assertNothingSent();
        $this->get(route('account.pending'))->assertOk()->assertSee('Tidak perlu verifikasi email');
        $this->get(route('dashboard'))->assertRedirect(route('account.pending'));
        $this->post(route('courses.enroll'), ['enrollment_code' => 'ANY'])->assertRedirect(route('account.pending'));
        $this->post(route('accounts.approve-all'))->assertRedirect(route('account.pending'));
    }

    public function test_laboran_and_aslab_can_approve_individual_and_all_pending_accounts_idempotently(): void
    {
        foreach (['Laboran', 'Aslab'] as $role) {
            $staff = User::factory()->create(['role' => $role, 'email_verified_at' => null]);
            $student = User::factory()->create(['approved_at' => null, 'email_verified_at' => null]);
            $others = User::factory()->count(27)->create(['approved_at' => null]);
            $pendingDosen = User::factory()->create(['role' => 'Dosen', 'approved_at' => null]);
            $approvalPage = $this->actingAs($staff)->get(route('accounts.approvals'));
            $approvalPage->assertOk()
                ->assertSee('Verifikasi semua')
                ->assertSee($others->last()->id)
                ->assertDontSee('Navigasi halaman');
            $this->assertCount(28, $approvalPage->viewData('students'));
            $this->post(route('accounts.approve', $student))->assertRedirect();
            $approvedAt = $student->fresh()->approved_at;
            $this->assertSame($staff->id, $student->fresh()->approved_by);
            $this->assertNull($others->first()->fresh()->approved_at);
            $this->post(route('accounts.approve', $student))->assertRedirect();
            $this->assertEquals($approvedAt, $student->fresh()->approved_at);
            $this->post(route('accounts.approve-all'))->assertRedirect();
            $this->assertSame(0, User::where('role', 'Mahasiswa')->whereNull('approved_at')->count());
            $this->assertNull($pendingDosen->fresh()->approved_at);
            $this->post(route('accounts.approve', $pendingDosen))->assertForbidden();
            $this->actingAs($student->fresh())->get(route('dashboard'))->assertOk();
        }
    }

    public function test_other_roles_cannot_approve_accounts(): void
    {
        $pending = User::factory()->create(['approved_at' => null]);
        foreach (['Mahasiswa', 'Dosen'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]))->get(route('accounts.approvals'))->assertForbidden();
            $this->post(route('accounts.approve', $pending))->assertForbidden();
            $this->post(route('accounts.approve-all'))->assertForbidden();
            $this->delete(route('accounts.destroy', $pending))->assertForbidden();
        }
        $this->assertNull($pending->fresh()->approved_at);
    }

    public function test_laboran_and_aslab_can_delete_only_the_selected_pending_student(): void
    {
        foreach (['Laboran', 'Aslab'] as $role) {
            $staff = User::factory()->create(['role' => $role]);
            $selected = User::factory()->create(['approved_at' => null]);
            $otherPending = User::factory()->create(['approved_at' => null]);
            $approved = User::factory()->create();

            $this->actingAs($staff)->get(route('accounts.approvals'))
                ->assertOk()
                ->assertSee('Hapus akun');

            $this->delete(route('accounts.destroy', $selected))
                ->assertRedirect()
                ->assertSessionHas('success');

            $this->assertDatabaseMissing('users', ['id' => $selected->id]);
            $this->assertDatabaseHas('users', ['id' => $otherPending->id, 'approved_at' => null]);
            $this->assertDatabaseHas('users', ['id' => $approved->id]);

            $this->delete(route('accounts.destroy', $approved))
                ->assertRedirect()
                ->assertSessionHas('error');
            $this->assertDatabaseHas('users', ['id' => $approved->id]);
        }
    }
}
