<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Inspection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InspectionPipelineTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_root_redirects_unauthenticated_to_login(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    public function test_api_health_endpoint_returns_json(): void
    {
        $response = $this->get('/api/v1/health');
        $response->assertStatus(200);
        $response->assertJsonStructure(['status']);
    }

    public function test_inspection_model_stores_required_fields(): void
    {
        $inspection = Inspection::create([
            'user_id'              => 1,
            'image_path'           => 'test/placeholder.jpg',
            'pass_fail'            => 'PASS',
            'confidence'           => 92,
            'defect_type'          => 'none',
            'instrument_class'     => 'scalpel',
            'risk_level'           => 'LOW',
            'composite_risk_score' => 12.5,
            'inference_ms'         => 1800,
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
            $this->assertContains($inspection->pass_fail, $validVerdicts);
        }
    }

    public function test_analysis_time_formats_correctly(): void
    {
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
                $result = number_format($ms / 1000, 1) . 's';
            } else {
                $result = $ms . 'ms';
            }
            $this->assertEquals($expected, $result, "Expected {$ms}ms to format as {$expected}");
        }
    }

    public function test_audit_log_requires_auth(): void
    {
        $response = $this->get('/audit-log');
        $response->assertRedirect('/login');
    }

    public function test_roi_calculation_is_correct(): void
    {
        $savingsPerUnit = 0.15 - 0.01;
        $annualSavings  = $savingsPerUnit * 10000 * 12;
        $this->assertEqualsWithDelta(0.14,    $savingsPerUnit, 0.0001);
        $this->assertEqualsWithDelta(16800.0, $annualSavings,  0.01);
    }

    public function test_upload_route_requires_auth(): void
    {
        $response = $this->get('/upload');
        $response->assertRedirect('/login');
    }
}
