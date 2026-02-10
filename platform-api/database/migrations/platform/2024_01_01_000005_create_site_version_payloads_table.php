<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('platform')->create('site_version_payloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_version_id')->constrained('site_versions')->cascadeOnDelete();
            $table->json('payload_json');
            $table->string('checksum');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('site_version_payloads');
    }
};
