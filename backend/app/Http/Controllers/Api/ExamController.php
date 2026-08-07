<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\HolidayServiceUnavailableException;
use App\Http\Controllers\Controller;
use App\Http\Resources\ExamResource;
use App\Models\Exam;
use App\Models\User;
use App\Services\NagerHolidayService;
use App\Support\Like;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

class ExamController extends Controller
{
    public function __construct(private readonly NagerHolidayService $holidays) {}

    #[OA\Get(
        path: '/exams',
        tags: ['Esami'],
        summary: 'Elenco pubblico degli esami disponibili',
        parameters: [
            new OA\Parameter(name: 'title', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
            new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['asc', 'desc'])),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista esami', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/ExamPublic'))),
        ],
    )]
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:255'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'sort' => ['nullable', 'in:asc,desc'],
        ]);

        $query = Exam::query();

        if (isset($validated['title'])) {
            Like::where($query, 'title', $validated['title']);
        }

        if (isset($validated['date'])) {
            $query->whereDate('date', $validated['date']);
        }

        $query->orderBy('date', ($validated['sort'] ?? 'asc') === 'desc' ? 'desc' : 'asc');

        return ExamResource::collection($query->get());
    }

    #[OA\Post(
        path: '/admin/exams',
        tags: ['Esami'],
        summary: 'Crea un nuovo esame (solo admin); rifiutato se la data è un giorno festivo italiano',
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['title', 'date'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'date', type: 'string', format: 'date'),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, description: 'Esame creato', content: new OA\JsonContent(ref: '#/components/schemas/Exam')),
            new OA\Response(response: 422, description: 'Data festiva o validazione fallita'),
            new OA\Response(response: 503, description: 'Servizio giorni festivi non disponibile'),
        ],
    )]
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'date' => ['required', 'date_format:Y-m-d'],
        ]);

        try {
            if ($this->holidays->isHoliday($data['date'])) {
                return response()->json([
                    'message' => "La data {$data['date']} è un giorno festivo italiano: non è possibile creare l'esame.",
                    'error' => 'exam_holiday',
                ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
            }
        } catch (HolidayServiceUnavailableException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'holiday_service_unavailable',
            ], JsonResponse::HTTP_SERVICE_UNAVAILABLE);
        }

        $exam = Exam::create($data);

        return (new ExamResource($exam))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    #[OA\Post(
        path: '/exams/{exam}/enroll',
        tags: ['Esami'],
        summary: 'Iscrizione self-service a un esame',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 201, description: 'Iscrizione completata'),
            new OA\Response(response: 409, description: 'Già iscritto'),
        ],
    )]
    public function enroll(Request $request, Exam $exam): JsonResponse
    {
        $alreadyEnrolled = DB::table('exam_user')
            ->where('user_id', $request->user()->id)
            ->where('exam_id', $exam->id)
            ->exists();

        if ($alreadyEnrolled) {
            return $this->enrolledConflict($exam);
        }

        try {
            $request->user()->exams()->attach($exam->id);
        } catch (UniqueConstraintViolationException) {
            return $this->enrolledConflict($exam);
        }

        return response()->json([
            'message' => "Iscrizione all'esame '{$exam->title}' completata.",
        ], JsonResponse::HTTP_CREATED);
    }

    /**
     * Risposta standard per iscrizione già presente (409).
     */
    private function enrolledConflict(Exam $exam): JsonResponse
    {
        return response()->json([
            'message' => "Sei già iscritto all'esame '{$exam->title}'.",
            'error' => 'already_enrolled',
        ], JsonResponse::HTTP_CONFLICT);
    }

    #[OA\Get(
        path: '/my/exams',
        tags: ['Esami'],
        summary: "Gli esami dell'utente autenticato con il relativo voto",
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(response: 200, description: 'Lista esami', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/Exam'))),
        ],
    )]
    public function myExams(Request $request): AnonymousResourceCollection
    {
        $exams = $request->user()->exams()->orderBy('date')->get();

        return ExamResource::collection($exams);
    }

    #[OA\Get(
        path: '/exams/{exam}/users',
        tags: ['Esami'],
        summary: 'Utenti iscritti a un esame (solo supervisor)',
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(name: 'exam', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Lista utenti', content: new OA\JsonContent(type: 'array', items: new OA\Items(ref: '#/components/schemas/User'))),
        ],
    )]
    public function enrolledUsers(Request $request, Exam $exam): JsonResponse
    {
        $users = $exam->users()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email']);

        return response()->json([
            'data' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]),
        ]);
    }
}
