<?php

namespace Tests\Feature;

use App\Models\Exam;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicExamsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_list_is_accessible_without_auth(): void
    {
        Exam::factory()->count(3)->create();

        $this->getJson('/api/exams')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_filter_by_title(): void
    {
        Exam::factory()->create(['title' => 'Analisi Matematica']);
        Exam::factory()->create(['title' => 'Geometria']);

        $this->getJson('/api/exams?title=Matematica')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Analisi Matematica');
    }

    public function test_title_filter_treats_wildcards_literally(): void
    {
        Exam::factory()->create(['title' => 'Analisi 100% Completo']);
        Exam::factory()->create(['title' => 'Analisi 100 passi']);

        $this->getJson('/api/exams?title='.urlencode('100%'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Analisi 100% Completo');
    }

    public function test_filter_by_date(): void
    {
        Exam::factory()->create(['title' => 'Esame A', 'date' => '2026-09-10']);
        Exam::factory()->create(['title' => 'Esame B', 'date' => '2026-10-01']);

        $this->getJson('/api/exams?date=2026-09-10')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Esame A');
    }

    public function test_sort_by_date_desc(): void
    {
        Exam::factory()->create(['title' => 'Primo', 'date' => '2026-01-01']);
        Exam::factory()->create(['title' => 'Secondo', 'date' => '2026-12-31']);

        $response = $this->getJson('/api/exams?sort=desc')->assertOk();

        $this->assertSame(
            ['2026-12-31', '2026-01-01'],
            collect($response->json('data'))->pluck('date')->all(),
        );
    }

    public function test_invalid_date_query_param_is_rejected(): void
    {
        Exam::factory()->create();

        $this->getJson('/api/exams?date=garbage')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['date']);
    }

    public function test_invalid_sort_query_param_is_rejected(): void
    {
        Exam::factory()->create();

        $this->getJson('/api/exams?sort=evil')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }

    public function test_public_list_does_not_expose_votes(): void
    {
        Exam::factory()->create();

        $this->getJson('/api/exams')
            ->assertOk()
            ->assertJsonMissingPath('data.0.vote');
    }

    public function test_public_list_is_rate_limited(): void
    {
        Exam::factory()->create();

        $statuses = [];
        for ($i = 0; $i < 70; $i++) {
            $statuses[] = $this->getJson('/api/exams')->getStatusCode();
            if ($statuses[$i] === 429) {
                break;
            }
        }

        $this->assertContains(429, $statuses);
        $this->assertSame(200, $statuses[0]);
    }

    public function test_cors_preflight_allows_frontend_origin(): void
    {
        $this->call('OPTIONS', '/api/exams', [], [], [], [
            'HTTP_ORIGIN' => 'http://localhost:5173',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'GET',
        ])->assertStatus(204)
            ->assertHeader('Access-Control-Allow-Origin', 'http://localhost:5173');
    }
}
