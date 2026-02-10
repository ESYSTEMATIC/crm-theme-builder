<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('platform')->create('site_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('sites')->cascadeOnDelete();
            $table->integer('version');
            $table->enum('status', ['draft', 'published', 'archived']);
            $table->string('created_by')->nullable();
            $table->timestamps();

            $table->unique(['site_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::connection('platform')->dropIfExists('site_versions');
    }
};
