<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RoiController extends Controller
{
    /**
     * Public API — called by the Cosmas marketing site ROI simulator.
     * No auth required. CORS headers allow cosmas-website.vercel.app.
     * Exempted from CSRF in web.php via withoutMiddleware().
     *
     * POST /api/roi-analysis
     * Body: { company, ticker, revenue, cogs, qcCost, inspectionLabor, totalY1, totalY2, totalY3, fdaEvent }
     * Returns: { analysis: string, company: string, ticker: string }
     */
    public function analyze(Request $request)
    {
        // CORS — allow marketing site and local dev
        $allowedOrigins = [
            'https://cosmas-website.vercel.app',
            'http://localhost:3000',
            'http://localhost:5173',
        ];
        $origin = $request->header('Origin', '');
        $corsOrigin = in_array($origin, $allowedOrigins) ? $origin : 'https://cosmas-website.vercel.app';

        if ($request->isMethod('OPTIONS')) {
            return response('', 204)->withHeaders([
                'Access-Control-Allow-Origin'  => $corsOrigin,
                'Access-Control-Allow-Methods' => 'POST, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Accept',
                'Access-Control-Max-Age'       => '86400',
            ]);
        }

        $data = $request->validate([
            'company'         => 'required|string|max:100',
            'ticker'          => 'required|string|max:10',
            'revenue'         => 'required|numeric|min:0',
            'cogs'            => 'required|numeric|min:0',
            'qcCost'          => 'required|numeric|min:0',
            'inspectionLabor' => 'required|numeric|min:0',
            'totalY1'         => 'required|numeric|min:0',
            'totalY2'         => 'required|numeric|min:0',
            'totalY3'         => 'required|numeric|min:0',
            'fdaEvent'        => 'nullable|string|max:200',
        ]);

        $fdaContext = $data['fdaEvent']
            ? "\n\nFDA REGULATORY CONTEXT: {$data['company']} ({$data['ticker']}) has a documented regulatory event: {$data['fdaEvent']}. This is publicly disclosed and directly relevant to implementation urgency and board-level risk appetite."
            : '';

        $prompt = <<<EOT
You are a strategic advisor specializing in medical device manufacturing quality systems. A potential customer is evaluating DAMIAN, an AI-powered defect detection system for surgical instrument manufacturing built by Cosmas.

DAMIAN's core capabilities: two-stage YOLOv8 computer vision pipeline (instrument classification + defect detection, mAP50=0.764), a 5-tool Claude agentic inspection loop that generates structured QC reports, a composite risk score grounded in FDA MAUDE recall data and FMEA methodology, and a full 21 CFR Part 820 / Part 11 electronic audit trail.

COMPANY FINANCIAL PROFILE (FY2024 10-K, publicly filed):
Company: {$data['company']} ({$data['ticker']})
Annual Revenue: \${$data['revenue']}M
Cost of Goods Sold: \${$data['cogs']}M
Implied Annual QC Cost (2.5% of revenue): \${$data['qcCost']}M
Vision-Inspectable Inspection Labor (24% of QC cost): \${$data['inspectionLabor']}M{$fdaContext}

ESTIMATED DAMIAN ANNUAL SAVINGS (financial model):
Year 1: \${$data['totalY1']}M  (20% labor reduction + recall avoidance)
Year 2: \${$data['totalY2']}M  (35% labor reduction + recall avoidance)
Year 3: \${$data['totalY3']}M  (50% labor reduction + recall avoidance)

Write a concise strategic implementation analysis in four labeled sections. Be specific to this company's scale and profile. Do not overclaim — DAMIAN augments human QC judgment, it does not replace it. Write in plain business English.

IMPLEMENTATION PRIORITY
Why this company is a strong candidate for DAMIAN right now, given their scale, cost structure, and any regulatory context. Two to three sentences.

RECOMMENDED PHASE 1
Which production lines or instrument categories to target first, how to structure a 90-day parallel-run pilot alongside existing QC, and the two most important success metrics. Three to four sentences.

KEY RISKS TO MANAGE
The two most significant implementation risks for a manufacturer at this scale, and a concrete mitigation for each. Two to three sentences.

EXECUTIVE SUMMARY
One sentence. The ROI case in plain language that a VP of Quality or CFO could use verbatim in an internal briefing.
EOT;

        $apiKey = config('services.anthropic.key') ?? env('ANTHROPIC_API_KEY');

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 700,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if (! $response->successful()) {
                Log::warning('ROI analysis API error', ['status' => $response->status()]);
                return response()->json(['error' => 'Analysis failed'], 500)
                    ->withHeaders(['Access-Control-Allow-Origin' => $corsOrigin]);
            }

            $analysis = $response->json('content.0.text', 'Analysis unavailable.');

            return response()->json([
                'analysis' => $analysis,
                'company'  => $data['company'],
                'ticker'   => $data['ticker'],
            ])->withHeaders(['Access-Control-Allow-Origin' => $corsOrigin]);

        } catch (\Exception $e) {
            Log::error('ROI analysis exception', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Analysis temporarily unavailable'], 503)
                ->withHeaders(['Access-Control-Allow-Origin' => $corsOrigin]);
        }
    }

    public function index(Request $request)
    {
        // Defaults
        $inspectionsPerMonth = (int) $request->input('inspections_per_month', 10000);
        $manualCostPer       = (float) $request->input('manual_cost_per', 0.15);
        $aiCostPer           = 0.01; // fixed: YOLOv8 + Claude API

        // Clamp inputs to sane ranges
        $inspectionsPerMonth = max(100, min(1000000, $inspectionsPerMonth));
        $manualCostPer       = max(0.05, min(10.0, $manualCostPer));

        // Monthly figures
        $manualMonthly   = $inspectionsPerMonth * $manualCostPer;
        $aiMonthly       = $inspectionsPerMonth * $aiCostPer;
        $savingsMonthly  = $manualMonthly - $aiMonthly;
        $savingsAnnual   = $savingsMonthly * 12;

        // 3-year ROI (Forrester benchmark: implementation cost ~$45k for mid-size manufacturer)
        $implementationCost = 45000;
        $threeYearSavings   = $savingsAnnual * 3;
        $roi3yr             = $implementationCost > 0
            ? round((($threeYearSavings - $implementationCost) / $implementationCost) * 100, 1)
            : 0;

        // Payback period in months
        $paybackMonths = $savingsMonthly > 0
            ? ceil($implementationCost / $savingsMonthly)
            : null;

        // Speed improvement: manual ~45s/inspection, AI ~0.2s
        $manualSecondsPerMonth = $inspectionsPerMonth * 45;
        $aiSecondsPerMonth     = $inspectionsPerMonth * 0.2;
        $hoursSaved            = round(($manualSecondsPerMonth - $aiSecondsPerMonth) / 3600, 1);

        // Actual system stats
        $totalInspections = Inspection::count();
        $actualSaved      = $totalInspections * ($manualCostPer - $aiCostPer);
        $passRate         = $totalInspections > 0
            ? round((Inspection::whereRaw("UPPER(pass_fail) = 'PASS'")->count() / $totalInspections) * 100, 1)
            : 0;
        $defectsCaught    = Inspection::whereRaw("UPPER(pass_fail) = 'FAIL'")->count();

        return view('roi', compact(
            'inspectionsPerMonth', 'manualCostPer', 'aiCostPer',
            'manualMonthly', 'aiMonthly', 'savingsMonthly', 'savingsAnnual',
            'implementationCost', 'threeYearSavings', 'roi3yr', 'paybackMonths',
            'hoursSaved', 'totalInspections', 'actualSaved', 'passRate', 'defectsCaught'
        ));
    }
}
