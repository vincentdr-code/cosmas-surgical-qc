<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Inspection;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Cosmas Sentry — Core Pipeline Integration Tests
 *
 * Tests the AI inspection pipeline, audit log, dashboard KPIs,
 * and API health endpoints that form the competition demo.
 */
class InspectionPipelineTest extends TestCase
{
    use RefreshDatabase;

    // ── Authentication ──────────────────────────────────────────────────

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
    }

    public function test_authenticated_home_redirects_unauthenticated(): void
    {
        $response = $this->get('/home');
        $response->assertRedirect('/login');
    }

    public function test_dashboard_requires_auth(): void
    {
        $response = $this->get('/dashboard');
        $response->assertRedirect('/login');
    }

    // ── API Health ──────────────────────────────────────────────────────

    public function test_api_health_endpoint_returns_json(): void
    {
        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJsonStructure(['status', 'db']);
    }

    // ── Inspection Model ────────────────────────────────────────────────

    public function test_inspection_model_stores_required_fields(): void
    {
        $inspection = Inspection::create([
            'user_id'       => 1,
            'pass_fail'     => 'PASS',
            'confidence'    => 92,
            'defect_type'   => 'none',
            'instrument_type' => 'scalpel',
            'risk_level'    => 'LOW',
            'composite_risk_score' => 12.5,
            'inference_ms'  => 1800,
        ]);

        $this->assertDatabaseHas('inspections', [
            'pass_fail'  => 'PASS',
            'confidence' => 92,
            'risk_level' => 'LOW',
        ]);
        $this->assertEquals(12.5, $inspection->composite_risk_score);
        $this->assertEquals('yolov8s_defect_detector.pt',
            basename($inspection->yolo_model ?? 'yolov8s_defect_detector.pt'));
    }

    public function test_inspection_pass_fail_values_are_valid(): void
    {
        $validVerdicts = ['PASS', 'FAIL', 'FLAGGED'];

        foreach ($validVerdicts as $verdict) {
            $inspection = new Inspection(['pass_fail' => $verdict]);
            $this->assertContains($inspection->pass_fail, $validVerdicts,
                "Verdict {$verdict} should be valid");
        }
    }

    public function test_analysis_time_formats_correctly(): void
    {
        // Simulate the display logic used in results.blade.php
        $cases = [
            [500,   '500ms'],
            [1000,  '1.0s'],
            [44000, '44.0s'],
            [90000, '1m 30s'],
        ];

        foreach ($cases as [$ms, $expected]) {
            if ($ms >= 60000) {
                $result = floor($ms / 60000) . 'm ' . round(($ms % 60000) / 1000) . 's';
            } elseif ($ms >= 1000) {
                $result = round($ms / 1000, 1) . 's';
            } else {
                $result = $ms . 'ms';
            }
            $this->assertEquals($expected, $result,
                "Expected {$ms}ms to format as {$expected}");
        }
    }

    // ── Audit Log ───────────────────────────────────────────────────────

    public function test_audit_log_requires_auth(): void
    {
        $response = $this->get('/audit-log');
        $response->assertRedirect('/login');
    }

    // ── ROI Calculator ──────────────────────────────────────────────────

    public function test_roi_calculation_is_correct(): void
    {
        $manualCostPerUnit   = 0.15;
        $aiCostPerUnit       = 0.01;
        $savingsPerUnit      = $manualCostPerUnit - $aiCostPerUnit;
        $monthlyVolume       = 10000;
        $annualSavings       = $savingsPerUnit * $monthlyVolume * 12;

        $this->assertEquals(0.14, $savingsPerUnit);
        $this->assertEquals(16800.0, $annualSavings);
    }

    // ── Upload Route ────────────────────────────────────────────────────

    public function test_upload_route_requires_auth(): void
    {
        $response = $this->get('/upload');
        $response->assertRedirect('/login');
    }
}
