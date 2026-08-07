<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateExamTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    public function test_admin_can_create_exam_on_non_holiday(): void
    {
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/exams', [
            'title' => 'Analisi Matematica I',
            'date' => '2026-09-10',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Analisi Matematica I')
            ->assertJsonPath('data.date', '2026-09-10');

        $this->assertDatabaseHas('exams', [
            'title' => 'Analisi Matematica I',
            'date' => '2026-09-10',
        ]);
    }

    public function test_exam_on_public_holiday_is_rejected(): void
    {
        $holiday = '2026-08-15';
        Http::fake(['date.nager.at/*' => Http::response([['date' => $holiday, 'name' => 'Ferragosto']], 200)]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/exams', [
            'title' => 'Esame festivo',
            'date' => $holiday,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'exam_holiday')
            ->assertJsonPath('message', "La data {$holiday} è un giorno festivo italiano: non è possibile creare l'esame.");

        $this->assertDatabaseMissing('exams', ['title' => 'Esame festivo']);
    }

    public function test_creation_fails_closed_when_nager_returns_server_error(): void
    {
        Http::fake(['date.nager.at/*' => Http::response([], 500)]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/exams', [
            'title' => 'Esame',
            'date' => '2026-09-10',
        ])->assertStatus(503);

        $this->assertDatabaseMissing('exams', ['title' => 'Esame']);
    }

    public function test_creation_fails_closed_when_nager_connection_fails(): void
    {
        Http::fake(['date.nager.at/*' => fn () => throw new ConnectionException('timeout')]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/exams', [
            'title' => 'Esame',
            'date' => '2026-09-10',
        ])->assertStatus(503);

        $this->assertDatabaseMissing('exams', ['title' => 'Esame']);
    }

    public function test_holidays_are_cached_with_single_http_call(): void
    {
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/exams', ['title' => 'Esame A', 'date' => '2026-09-10'])->assertCreated();
        $this->postJson('/api/admin/exams', ['title' => 'Esame B', 'date' => '2026-09-11'])->assertCreated();

        Http::assertSentCount(1);
    }

    public function test_only_admin_can_create_exam(): void
    {
        Http::fake(['date.nager.at/*' => Http::response([], 200)]);
        Sanctum::actingAs(User::factory()->create(['role' => Role::Supervisor]));

        $this->postJson('/api/admin/exams', [
            'title' => 'Esame',
            'date' => '2026-09-10',
        ])->assertStatus(403);
    }

    public function test_unauthenticated_request_is_rejected(): void
    {
        $this->postJson('/api/admin/exams', [
            'title' => 'Esame',
            'date' => '2026-09-10',
        ])->assertStatus(401);
    }

    public function test_validation_requires_title_and_date(): void
    {
        Sanctum::actingAs($this->admin());

        $this->postJson('/api/admin/exams', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['title', 'date']);
    }
}
