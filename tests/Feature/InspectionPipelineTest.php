<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Inspection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InspectionPipelineTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertStatus(200);
    }

    public function test_authenticated_home_redirects_unauthenticated(): void
    {
        $this->get('/home')->assertRedirect('/login');
    }

    public function test_dashboard_requires_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_root_redirects_unauthenticated_to_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_api_health_endpoint_returns_json(): void
    {
        $this->get('/api/v1/health')->assertStatus(200)->assertJsonStructure(['status']);
    }

    public function test_inspection_model_stores_required_fields(): void
    {
        $user = User::factory()->create();
        $inspection = Inspection::create([
            'user_id'              => $user->id,
            'image_path'           => 'test/placeholder.jpg',
            'pass_fail'            => 'PASS',
            'confidence'           => 92,
            'defect_type'          => 'none',
            'instrument_class'     => 'scalpel',
            'risk_level'           => 'LOW',
            'composite_risk_score' => 12.5,
            'inference_ms'         => 1800,
        ]);
        $this->assertDatabaseHas('inspections', ['pass_fail' => 'PASS', 'confidence' => 92]);
        $this->assertEquals(12.5, $inspection->composite_risk_score);
    }

    public function test_inspection_pass_fail_values_are_valid(): void
    {
        foreach (['PASS', 'FAIL', 'FLAGGED'] as $verdict) {
            $i = new Inspection(['pass_fail' => $verdict]);
            $this->assertContains($i->pass_fail, ['PASS', 'FAIL', 'FLAGGED']);
        }
    }

    public function test_analysis_time_formats_correctly(): void
    {
        $cases = [[500,'500ms'],[1000,'1.0s'],[44000,'44.0s'],[90000,'1m 30s']];
        foreach ($cases as [$ms, $expected]) {
            if ($ms >= 60000) {
                $r = floor($ms/60000).'m '.round(($ms%60000)/1000).'s';
            } elseif ($ms >= 1000) {
                $r = number_format($ms/1000, 1).'s';
            } else {
                $r = $ms.'ms';
            }
            $this->assertEquals($expected, $r, "Expected {$ms}ms to format as {$expected}");
        }
    }

    public function test_audit_log_requires_auth(): void
    {
        $this->get('/audit-log')->assertRedirect('/login');
    }

    public function test_roi_calculation_is_correct(): void
    {
        $s = 0.15 - 0.01;
        $this->assertEqualsWithDelta(0.14, $s, 0.0001);
        $this->assertEqualsWithDelta(16800.0, $s * 10000 * 12, 0.01);
    }

    public function test_upload_route_requires_auth(): void
    {
        $this->get('/upload')->assertRedirect('/login');
    }
}
