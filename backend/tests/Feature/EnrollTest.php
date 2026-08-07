<?php

namespace Tests\Feature;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class EnrollTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_enroll_in_available_exam(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson("/api/exams/{$exam->id}/enroll")
            ->assertCreated();

        $this->assertDatabaseHas('exam_user', [
            'user_id' => $user->id,
            'exam_id' => $exam->id,
            'vote' => null,
        ]);
    }

    public function test_duplicate_enrollment_is_conflict(): void
    {
        $user = User::factory()->create();
        $exam = Exam::factory()->create();
        $user->exams()->attach($exam->id);
        Sanctum::actingAs($user);

        $this->postJson("/api/exams/{$exam->id}/enroll")
            ->assertStatus(409)
            ->assertJsonPath('error', 'already_enrolled');
    }

    public function test_enrollment_requires_authentication(): void
    {
        $exam = Exam::factory()->create();

        $this->postJson("/api/exams/{$exam->id}/enroll")
            ->assertStatus(401);
    }

    public function test_enrollment_race_is_conflict_when_unique_constraint_is_violated(): void
    {
        $exam = Exam::factory()->create();

        $relation = Mockery::mock(BelongsToMany::class);
        $relation->shouldReceive('attach')->once()->andThrow(
            new UniqueConstraintViolationException(
                'pgsql',
                'insert into "exam_user" ("user_id", "exam_id") values (?, ?)',
                [],
                new RuntimeException('unique constraint'),
            ),
        );

        $user = Mockery::mock(User::class)->makePartial();
        $user->shouldReceive('exams')->once()->andReturn($relation);

        Sanctum::actingAs($user);

        $this->postJson("/api/exams/{$exam->id}/enroll")
            ->assertStatus(409)
            ->assertJsonPath('message', "Sei già iscritto all'esame '{$exam->title}'.");
    }

    public function test_enrollment_to_missing_exam_is_not_found(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/exams/999/enroll')->assertStatus(404);
    }
}
