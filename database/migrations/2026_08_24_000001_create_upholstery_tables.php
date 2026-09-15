<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->string('icon')->default('sofa');
            $table->decimal('base_price', 10, 2)->default(0);
            $table->unsignedInteger('estimated_days')->default(7);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('category');
            $table->string('color');
            $table->string('color_hex', 7)->default('#B88A5A');
            $table->string('unit')->default('metro');
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('minimum_stock', 10, 2)->default(5);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('client_id')->constrained('users');
            $table->foreignId('service_id')->constrained();
            $table->enum('status', ['pending', 'confirmed', 'in_progress', 'quality', 'ready', 'delivered', 'cancelled'])->default('pending');
            $table->enum('priority', ['normal', 'high', 'urgent'])->default('normal');
            $table->string('furniture_type');
            $table->string('style')->nullable();
            $table->string('primary_color')->nullable();
            $table->foreignId('material_id')->nullable()->constrained();
            $table->string('dimensions')->nullable();
            $table->text('description');
            $table->date('requested_date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->decimal('advance', 10, 2)->default(0);
            $table->string('reference_image')->nullable();
            $table->text('internal_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('production_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->unsignedTinyInteger('sequence')->default(1);
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->dateTime('started_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status');
            $table->string('comment')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
        Schema::dropIfExists('production_tasks');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('services');
    }
};
