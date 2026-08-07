<?php

namespace Database\Seeders;

use App\Models\Exam;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed del database con dati dimostrativi.
     *
     * Utenti demo (password: "password"):
     *   admin@example.com       ruolo admin
     *   supervisor@example.com  ruolo supervisor
     *   student@example.com     ruolo user
     */
    public function run(): void
    {
        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        $supervisor = User::factory()->create([
            'name' => 'Supervisor',
            'email' => 'supervisor@example.com',
            'role' => 'supervisor',
        ]);

        $student = User::factory()->create([
            'name' => 'Studente',
            'email' => 'student@example.com',
            'role' => 'user',
        ]);

        $titles = [
            'Programmazione I',
            'Matematica Discreta',
            'Architettura degli Elaboratori',
            'Algoritmi e Strutture Dati',
            'Basi di Dati',
        ];

        $exams = Collection::make($titles)->map(
            fn (string $title, int $i): Exam => Exam::create([
                'title' => $title,
                'date' => now()->addMonths($i + 1)->format('Y-m-d'),
            ]),
        );

        $student->exams()->attach($exams->take(2)->pluck('id'));
    }
}
