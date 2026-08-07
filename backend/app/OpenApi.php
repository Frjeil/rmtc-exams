<?php

namespace App;

use OpenApi\Attributes as OA;

#[OA\Info(
    title: 'rmtc-exams API',
    version: '1.0.0',
    description: 'API per la gestione di trascrizioni d\'esame.',
)]
#[OA\Server(url: '/api')]
#[OA\SecurityScheme(
    securityScheme: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'token',
)]
#[OA\Schema(
    schema: 'User',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'name', type: 'string'),
        new OA\Property(property: 'email', type: 'string'),
        new OA\Property(property: 'role', type: 'string', enum: ['user', 'admin', 'supervisor']),
    ],
)]
#[OA\Schema(
    schema: 'ExamPublic',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'date', type: 'string', format: 'date'),
    ],
)]
#[OA\Schema(
    schema: 'Exam',
    properties: [
        new OA\Property(property: 'id', type: 'integer'),
        new OA\Property(property: 'title', type: 'string'),
        new OA\Property(property: 'date', type: 'string', format: 'date'),
        new OA\Property(property: 'vote', type: 'integer', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'AuthResponse',
    properties: [
        new OA\Property(property: 'token', type: 'string'),
        new OA\Property(property: 'user', ref: '#/components/schemas/User'),
    ],
)]
class OpenApi {}
