<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\Exam;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MyExamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_sees_only_own_exams_with_votes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $exam = Exam::factory()->create();
        $otherExam = Exam::factory()->create();

        $user->exams()->attach($exam->id, ['vote' => 27]);
        $other->exams()->attach($otherExam->id, ['vote' => 30]);

        Sanctum::actingAs($user);

        $this->getJson('/api/my/exams')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $exam->id)
            ->assertJsonPath('data.0.title', $exam->title)
            ->assertJsonPath('data.0.vote', 27);
    }

    public function test_requires_authentication(): void
    {
        $this->getJson('/api/my/exams')->assertStatus(401);
    }

    public function test_admin_cannot_access_my_exams(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => Role::Admin]));

        $this->getJson('/api/my/exams')->assertStatus(403);
    }

    public function test_supervisor_cannot_access_my_exams(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => Role::Supervisor]));

        $this->getJson('/api/my/exams')->assertStatus(403);
    }
}
