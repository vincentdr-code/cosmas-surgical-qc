<?php
// Migration: add orchestrator columns to inspections table
// Run via: php artisan migrate  (after this file is placed in database/migrations/)
// Or executed directly by the deploy script via SSH.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            if (!Schema::hasColumn('inspections', 'composite_risk_score')) {
                $table->float('composite_risk_score')->nullable()->after('recommended_action')
                    ->comment('FMEA RPN × Bayesian recall prior × trend weight');
            }
            if (!Schema::hasColumn('inspections', 'risk_level')) {
                $table->string('risk_level', 20)->nullable()->after('composite_risk_score')
                    ->comment('LOW | MEDIUM | HIGH | CRITICAL');
            }
            if (!Schema::hasColumn('inspections', 'agent_steps')) {
                $table->text('agent_steps')->nullable()->after('risk_level')
                    ->comment('JSON array of tool calls made by the orchestrator');
            }
            if (!Schema::hasColumn('inspections', 'cost_matrix')) {
                $table->text('cost_matrix')->nullable()->after('agent_steps')
                    ->comment('JSON Expected Value cost matrix for QC decision support');
            }
            if (!Schema::hasColumn('inspections', 'instrument_class')) {
                $table->string('instrument_class', 100)->nullable()->after('defect_type')
                    ->comment('Identified surgical instrument class');
            }
        });
    }

    public function down(): void
    {
        Schema::table('inspections', function (Blueprint $table) {
            $table->dropColumn(['composite_risk_score', 'risk_level', 'agent_steps', 'cost_matrix', 'instrument_class']);
        });
    }
};
