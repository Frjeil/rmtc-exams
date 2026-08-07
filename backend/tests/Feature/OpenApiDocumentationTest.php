<?php

namespace Tests\Feature;

use Tests\TestCase;

class OpenApiDocumentationTest extends TestCase
{
    public function test_openapi_spec_is_generated_with_expected_endpoints_and_security(): void
    {
        $this->artisan('l5-swagger:generate')->assertExitCode(0);

        $path = storage_path('api-docs/api-docs.json');
        $this->assertFileExists($path);

        $spec = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame('rmtc-exams API', $spec['info']['title']);
        $this->assertSame('1.0.0', $spec['info']['version']);

        $this->assertArrayHasKey('/auth/login', $spec['paths']);
        $this->assertArrayHasKey('/admin/exams', $spec['paths']);
        $this->assertArrayHasKey('/my/exams', $spec['paths']);
        $this->assertArrayHasKey('/supervisor/my/votes', $spec['paths']);

        $this->assertArrayNotHasKey('security', $spec['paths']['/auth/login']['post']);
        $this->assertSame([['bearerAuth' => []]], $spec['paths']['/admin/exams']['post']['security']);
        $this->assertSame([['bearerAuth' => []]], $spec['paths']['/my/exams']['get']['security']);
    }
}
