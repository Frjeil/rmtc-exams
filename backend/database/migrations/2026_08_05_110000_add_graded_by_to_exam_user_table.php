<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_user', function (Blueprint $table): void {
            $table->foreignId('graded_by')->nullable()->after('vote')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_user', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('graded_by');
        });
    }
};
