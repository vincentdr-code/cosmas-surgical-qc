<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->json('yolo_detections')->nullable()->after('reasoning');
            $table->integer('yolo_count')->default(0)->after('yolo_detections');
            $table->string('yolo_model')->nullable()->after('yolo_count');
            $table->integer('inference_ms')->nullable()->after('yolo_model');
            $table->string('recommended_action')->nullable()->after('inference_ms');
            $table->string('regulatory_note')->nullable()->after('recommended_action');
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn([
                'yolo_detections', 'yolo_count', 'yolo_model',
                'inference_ms', 'recommended_action', 'regulatory_note',
            ]);
        });
    }
};
