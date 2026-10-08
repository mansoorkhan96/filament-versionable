<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('versions', function (Blueprint $table): void {
            $table->json('relations')->nullable()->after('contents');
        });
    }

    public function down(): void
    {
        Schema::table('versions', function (Blueprint $table): void {
            $table->dropColumn('relations');
        });
    }
};
