<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('original_path');
            $table->string('generated_path');
            $table->string('color', 60);
            $table->string('style', 80)->nullable();
            $table->string('model')->default('gpt-image-2');
            $table->text('prompt');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('ai_design_id')->nullable()->after('reference_image')->constrained('ai_designs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', fn (Blueprint $table) => $table->dropConstrainedForeignId('ai_design_id'));
        Schema::dropIfExists('ai_designs');
    }
};
