<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AssignVoteTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_can_assign_vote_to_enrolled_user(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/supervisor/exams/{$exam->id}/assign", [
            'user_id' => $student->id,
            'vote' => 28,
        ])->assertOk();

        $this->assertDatabaseHas('exam_user', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'vote' => 28,
        ]);
    }

    public function test_supervisor_can_update_existing_vote(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id, ['vote' => 24]);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/supervisor/exams/{$exam->id}/assign", [
            'user_id' => $student->id,
            'vote' => 30,
        ])->assertOk();

        $this->assertDatabaseHas('exam_user', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'vote' => 30,
        ]);
    }

    public function test_vote_requires_existing_enrollment(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/supervisor/exams/{$exam->id}/assign", [
            'user_id' => $student->id,
            'vote' => 28,
        ])->assertStatus(422)
            ->assertJsonPath('error', 'not_enrolled')
            ->assertJsonPath('message', "L'utente non è iscritto all'esame '{$exam->title}': impossibile assegnare un voto.");
    }

    public function test_vote_out_of_range_is_rejected(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/supervisor/exams/{$exam->id}/assign", [
            'user_id' => $student->id,
            'vote' => 15,
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['vote']);
    }

    public function test_only_supervisor_can_assign_votes(): void
    {
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id);
        Sanctum::actingAs($student);

        $this->postJson("/api/supervisor/exams/{$exam->id}/assign", [
            'user_id' => $student->id,
            'vote' => 28,
        ])->assertStatus(403);
    }

    public function test_assign_records_the_grading_supervisor(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id);
        Sanctum::actingAs($supervisor);

        $this->postJson("/api/supervisor/exams/{$exam->id}/assign", [
            'user_id' => $student->id,
            'vote' => 27,
        ])->assertOk();

        $this->assertDatabaseHas('exam_user', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'vote' => 27,
            'graded_by' => $supervisor->id,
        ]);
    }

    public function test_supervisor_can_list_their_assigned_votes(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create(['name' => 'Anna']);
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id, ['vote' => 28, 'graded_by' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exam_title', $exam->title)
            ->assertJsonPath('data.0.student_email', $student->email)
            ->assertJsonPath('data.0.vote', 28);
    }

    public function test_my_votes_show_only_votes_assigned_by_the_current_supervisor(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $other = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam1 = Exam::factory()->create();
        $exam2 = Exam::factory()->create();
        $student->exams()->attach($exam1->id, ['vote' => 28, 'graded_by' => $supervisor->id]);
        $student->exams()->attach($exam2->id, ['vote' => 30, 'graded_by' => $other->id]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exam_id', $exam1->id);
    }

    public function test_only_supervisor_can_list_assigned_votes(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->getJson('/api/supervisor/my/votes')->assertStatus(403);
    }

    public function test_my_votes_can_be_filtered_by_title(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $examA = Exam::factory()->create(['title' => 'Analisi Matematica']);
        $examB = Exam::factory()->create(['title' => 'Geometria']);
        $student->exams()->attach($examA->id, ['vote' => 28, 'graded_by' => $supervisor->id]);
        $student->exams()->attach($examB->id, ['vote' => 30, 'graded_by' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes?title=Geometria')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exam_title', 'Geometria');
    }

    public function test_my_votes_title_filter_treats_wildcards_literally(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $examA = Exam::factory()->create(['title' => 'Esame 100% Completo']);
        $examB = Exam::factory()->create(['title' => 'Esame 100 passi']);
        $student->exams()->attach($examA->id, ['vote' => 28, 'graded_by' => $supervisor->id]);
        $student->exams()->attach($examB->id, ['vote' => 30, 'graded_by' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes?title='.urlencode('100%'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exam_title', 'Esame 100% Completo');
    }

    public function test_my_votes_can_be_filtered_by_date(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $examA = Exam::factory()->create(['title' => 'Esame A', 'date' => '2026-09-10']);
        $examB = Exam::factory()->create(['title' => 'Esame B', 'date' => '2026-10-01']);
        $student->exams()->attach($examA->id, ['vote' => 28, 'graded_by' => $supervisor->id]);
        $student->exams()->attach($examB->id, ['vote' => 30, 'graded_by' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes?date=2026-10-01')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.exam_title', 'Esame B');
    }

    public function test_my_votes_can_be_sorted_by_exam_date_desc(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $examA = Exam::factory()->create(['title' => 'Primo', 'date' => '2026-01-01']);
        $examB = Exam::factory()->create(['title' => 'Secondo', 'date' => '2026-12-31']);
        $student->exams()->attach($examA->id, ['vote' => 28, 'graded_by' => $supervisor->id]);
        $student->exams()->attach($examB->id, ['vote' => 30, 'graded_by' => $supervisor->id]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes?sort=desc')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.exam_id', $examB->id)
            ->assertJsonPath('data.1.exam_id', $examA->id);
    }

    public function test_my_votes_is_empty_when_nothing_graded(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        Sanctum::actingAs($supervisor);

        $this->getJson('/api/supervisor/my/votes')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_deleting_supervisor_preserves_vote_and_nulls_graded_by(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id, ['vote' => 28, 'graded_by' => $supervisor->id]);

        $supervisor->delete();

        $this->assertDatabaseHas('exam_user', [
            'user_id' => $student->id,
            'exam_id' => $exam->id,
            'vote' => 28,
            'graded_by' => null,
        ]);
    }

    public function test_supervisor_can_list_enrolled_users(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $student = User::factory()->create(['name' => 'Anna']);
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id);
        Sanctum::actingAs($supervisor);

        $this->getJson("/api/exams/{$exam->id}/users")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $student->id)
            ->assertJsonPath('data.0.email', $student->email);
    }

    public function test_only_supervisor_can_list_enrolled_users(): void
    {
        $student = User::factory()->create();
        $exam = Exam::factory()->create();
        $student->exams()->attach($exam->id);
        Sanctum::actingAs($student);

        $this->getJson("/api/exams/{$exam->id}/users")->assertStatus(403);
    }

    public function test_enrolled_users_are_ordered_by_name(): void
    {
        $supervisor = User::factory()->create(['role' => Role::Supervisor]);
        $exam = Exam::factory()->create();
        $bianca = User::factory()->create(['name' => 'Bianca']);
        $anna = User::factory()->create(['name' => 'Anna']);
        $bianca->exams()->attach($exam->id);
        $anna->exams()->attach($exam->id);
        Sanctum::actingAs($supervisor);

        $this->getJson("/api/exams/{$exam->id}/users")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Anna')
            ->assertJsonPath('data.1.name', 'Bianca');
    }
}
