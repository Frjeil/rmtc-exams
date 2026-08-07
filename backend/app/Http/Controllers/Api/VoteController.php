<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\User;
use App\Support\Like;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class VoteController extends Controller
{
    #[OA\Post(
        path: '/supervisor/exams/{exam}/assign',
        tags: ['Votazioni'],
        summary: 'Assegna/aggiorna il voto di un utente iscritto (solo supervisor)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['user_id', 'vote'],
                properties: [
                    new OA\Property(property: 'user_id', type: 'integer'),
                    new OA\Property(property: 'vote', type: 'integer', minimum: 18, maximum: 30),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, description: 'Voto assegnato'),
            new OA\Response(response: 422, description: 'Utente non iscritto o validazione fallita'),
        ],
    )]
    public function assign(Request $request, Exam $exam): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'vote' => [
                'required',
                'integer',
                'min:'.config('exams.vote_min'),
                'max:'.config('exams.vote_max'),
            ],
        ]);

        $enrollment = DB::table('exam_user')
            ->where('user_id', $data['user_id'])
            ->where('exam_id', $exam->id)
            ->first();

        if (! $enrollment) {
            return response()->json([
                'message' => "L'utente non è iscritto all'esame '{$exam->title}': impossibile assegnare un voto.",
                'error' => 'not_enrolled',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::table('exam_user')
            ->where('id', $enrollment->id)
            ->update([
                'vote' => $data['vote'],
                'graded_by' => $request->user()->id,
                'updated_at' => now(),
            ]);

        $user = User::find($data['user_id']);

        if (! $user) {
            return response()->json([
                'message' => 'Utente non trovato.',
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        return response()->json([
            'message' => "Voto {$data['vote']} assegnato a {$user->name} per l'esame '{$exam->title}'.",
            'exam' => [
                'id' => $exam->id,
                'title' => $exam->title,
                'date' => $exam->date->format('Y-m-d'),
                'vote' => $data['vote'],
            ],
        ]);
    }

    #[OA\Get(
        path: '/supervisor/my/votes',
        tags: ['Votazioni'],
        summary: 'I voti assegnati dal supervisor corrente',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'title', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista votazioni'),
        ],
    )]
    public function myVotes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:asc,desc'],
        ]);

        $query = DB::table('exam_user')
            ->join('exams', 'exams.id', '=', 'exam_user.exam_id')
            ->join('users', 'users.id', '=', 'exam_user.user_id')
            ->where('exam_user.graded_by', $request->user()->id)
            ->whereNotNull('exam_user.vote');

        if (isset($validated['title'])) {
            Like::where($query, 'exams.title', $validated['title']);
        }

        if (isset($validated['date'])) {
            $query->whereDate('exams.date', $validated['date']);
        }

        $query->orderBy('exams.date', ($validated['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc');

        $votes = $query->get([
            'exams.id as exam_id',
            'exams.title as exam_title',
            'exams.date as exam_date',
            'users.name as student_name',
            'users.email as student_email',
            'exam_user.vote',
            'exam_user.updated_at as graded_at',
        ]);

        return response()->json([
            'data' => $votes->map(fn (object $vote): array => [
                'exam_id' => $vote->exam_id,
                'exam_title' => $vote->exam_title,
                'exam_date' => $vote->exam_date,
                'student_name' => $vote->student_name,
                'student_email' => $vote->student_email,
                'vote' => $vote->vote,
                'graded_at' => $vote->graded_at,
            ]),
        ]);
    }
}
