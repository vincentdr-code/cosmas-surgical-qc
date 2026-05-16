<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('inspections', function (Blueprint $table) {
            $table->string('instrument_id', 24)->nullable()->after('id');
            $table->string('operator_id',   32)->nullable()->after('instrument_id');
        });
    }
    public function down(): void {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['instrument_id', 'operator_id']);
        });
    }
};
