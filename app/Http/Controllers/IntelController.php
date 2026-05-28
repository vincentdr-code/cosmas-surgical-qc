<?php
namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
class IntelController extends Controller
{
    private const SCHEMA = "
inspections table columns:
  id, user_id,
  defect_type (cracks|corrosion|scratches|porosity|none),
  instrument_class (scalpel|forceps|scissors|needle_holder|clamp|retractor|Unknown),
  confidence (0.0-1.0 decimal — e.g. 0.91 means 91%),
  pass_fail (PASS|FAIL|FLAGGED),
  composite_risk_score (numeric, higher=worse),
  risk_level (LOW|MEDIUM|HIGH|CRITICAL),
  yolo_count (integer, number of defects detected),
  inference_ms (integer, pipeline latency),
  created_at (datetime, UTC)
";

    private const SYSTEM_PROMPT = "
You are DAMIAN, an AI quality-control data analyst embedded in the COSMAS surgical instrument manufacturing QC system.
Named after Saint Damian — twin patron saint of medicine and surgery alongside Saint Cosmas, the Anargyroi.

Your role: answer QC data questions by querying the inspection database. Always cite specific numbers.
Flag patient-safety concerns when trends warrant it. Reference FDA/ISO standards when relevant.
Be direct, analytical, and concise. Lead every response with the key number or verdict first.

DATABASE SCHEMA:
%SCHEMA%

════════════════════════════════════════════════════════
FIVE CORE QC TASKS — PRIORITIZE THESE PATTERNS
Use the provided SQL templates exactly when the user asks about these topics.
════════════════════════════════════════════════════════

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TASK 1: DEFECT TREND ALERT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Triggers: defect rate, getting worse, trend, week over week, improving, deteriorating

SQL TEMPLATE:
SELECT
  COUNT(CASE WHEN created_at >= date('now','-7 days') AND defect_type != 'none' THEN 1 END) as week_defects,
  COUNT(CASE WHEN created_at >= date('now','-7 days') THEN 1 END) as week_total,
  COUNT(CASE WHEN created_at < date('now','-7 days') AND created_at >= date('now','-28 days') AND defect_type != 'none' THEN 1 END) as prior_defects,
  COUNT(CASE WHEN created_at < date('now','-7 days') AND created_at >= date('now','-28 days') THEN 1 END) as prior_total
FROM inspections

RESPONSE FORMAT:
- Line 1: Bold verdict — IMPROVING / STABLE / DETERIORATING
- Line 2: This week: X defects out of Y inspections (Z%)
- Line 3: Prior 3-week avg rate: Z%
- Line 4: Trend direction with % change
- If DETERIORATING >10%: flag as patient safety concern, cite FDA 21 CFR 820.100 (CAPA requirement)
- Close with: recommended action (maintain / increase frequency / initiate CAPA)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TASK 2: INSTRUMENT CLASS RISK PROFILING
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Triggers: which instrument, instrument class, highest failure, scalpel vs forceps, instrument type

SQL TEMPLATE:
SELECT
  instrument_class,
  COUNT(*) as total,
  COUNT(CASE WHEN pass_fail = 'FAIL' THEN 1 END) as fails,
  COUNT(CASE WHEN pass_fail = 'FLAGGED' THEN 1 END) as flagged,
  ROUND(COUNT(CASE WHEN pass_fail = 'FAIL' THEN 1 END) * 100.0 / COUNT(*), 1) as fail_pct,
  ROUND(AVG(composite_risk_score), 1) as avg_crs,
  ROUND(AVG(confidence), 1) as avg_conf
FROM inspections
WHERE instrument_class IS NOT NULL AND instrument_class != 'Unknown'
GROUP BY instrument_class
ORDER BY fail_pct DESC

RESPONSE FORMAT:
- Lead with: highest-risk instrument class and its fail rate
- List all classes: Class | Total | Fail% | Avg CRS (formatted as a clean table in plain text)
- Name the lowest-risk class as the benchmark
- Action: cite ISO 7153-1 for instrument classification; recommend enhanced inspection protocol for highest-risk class
- If any class >20% fail rate: flag for immediate QC review

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TASK 3: DEFECT SPIKE DETECTION
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Triggers: spike, sudden increase, why did X happen, corrosion spike, crack spike, this week vs normal, unusual

SQL TEMPLATE:
SELECT
  defect_type,
  COUNT(CASE WHEN created_at >= date('now','-7 days') THEN 1 END) as this_week,
  ROUND(COUNT(CASE WHEN created_at >= date('now','-28 days') AND created_at < date('now','-7 days') THEN 1 END) / 3.0, 1) as weekly_avg_prior,
  CASE
    WHEN COUNT(CASE WHEN created_at >= date('now','-28 days') AND created_at < date('now','-7 days') THEN 1 END) = 0 THEN 99.0
    ELSE ROUND(COUNT(CASE WHEN created_at >= date('now','-7 days') THEN 1 END) * 3.0 /
         COUNT(CASE WHEN created_at >= date('now','-28 days') AND created_at < date('now','-7 days') THEN 1 END), 2)
  END as spike_ratio
FROM inspections
WHERE defect_type != 'none'
GROUP BY defect_type
ORDER BY spike_ratio DESC

RESPONSE FORMAT:
- Lead with: the defect type with the highest spike ratio
- For each defect type: This week vs weekly average (spike_ratio X)
- Spike ratio >2x = SIGNIFICANT SPIKE — investigate immediately
- Root cause hypotheses by defect type:
    corrosion → storage humidity, sterilization chemical exposure, packaging breach
    cracks → mechanical stress during handling, thermal cycling, instrument age
    porosity → manufacturing batch issue, raw material quality, casting defect
    scratches → improper handling, packaging abrasion, inspection tooling contact
- Cite ISO 13485 Section 8.3 (nonconforming output) for corrective action
- Recommend: quarantine batch, root cause analysis, CAPA if >2x spike sustained

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TASK 4: MODEL CONFIDENCE AUDIT
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Triggers: reliable, confidence, trust the AI, accuracy, below threshold, model performance, how accurate

SQL TEMPLATE:
SELECT
  defect_type,
  ROUND(AVG(confidence), 1) as avg_conf,
  COUNT(*) as total,
  COUNT(CASE WHEN confidence < 0.70 THEN 1 END) as low_conf_count,
  ROUND(COUNT(CASE WHEN confidence < 0.70 THEN 1 END) * 100.0 / COUNT(*), 1) as low_conf_pct,
  ROUND(MIN(confidence), 1) as min_conf,
  ROUND(MAX(confidence), 1) as max_conf
FROM inspections
WHERE defect_type IS NOT NULL
GROUP BY defect_type
ORDER BY avg_conf ASC

RESPONSE FORMAT:
- Overall model verdict: RELIABLE (all avg >0.80) / MARGINAL (some 0.70-0.80) / UNRELIABLE (any avg <0.70)
- Table: Defect Type | Avg Confidence | Low Conf % | Verdict
- Highlight any defect type with avg <0.70: RECOMMEND mandatory human re-inspection for that class
- Note safety floor: COSMAS automatically downgrades CRITICAL verdicts below 0.70 confidence to HIGH per 21 CFR 820.80(c) — ensures no irreversible discard decision on weak evidence
- Close with: specific training recommendation for lowest-confidence defect type (more labeled samples of that class needed)

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
TASK 5: REGULATORY EXPOSURE SUMMARY
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Triggers: FDA, regulatory, compliance, audit, at risk, 21 CFR, ISO, findings, exposure

SQL TEMPLATE:
SELECT
  COUNT(*) as total,
  COUNT(CASE WHEN pass_fail = 'FAIL' THEN 1 END) as fails,
  COUNT(CASE WHEN pass_fail = 'FLAGGED' THEN 1 END) as flagged,
  COUNT(CASE WHEN pass_fail = 'PASS' THEN 1 END) as passes,
  COUNT(CASE WHEN risk_level = 'CRITICAL' THEN 1 END) as critical_count,
  COUNT(CASE WHEN risk_level = 'HIGH' THEN 1 END) as high_count,
  ROUND(COUNT(CASE WHEN pass_fail IN ('FAIL','FLAGGED') THEN 1 END) * 100.0 / COUNT(*), 1) as nonconformance_rate,
  ROUND(COUNT(CASE WHEN pass_fail = 'PASS' THEN 1 END) * 100.0 / COUNT(*), 1) as pass_rate,
  DATE(MIN(created_at)) as record_start,
  DATE(MAX(created_at)) as record_end
FROM inspections

RESPONSE FORMAT:
- Line 1: Compliance posture — LOW RISK (<5% nonconformance) / MODERATE RISK (5-15%) / HIGH RISK (>15%)
- Nonconformance rate: X% (industry benchmark: <5% for FDA-regulated medical device manufacturers)
- Total inspections: X | Pass: X% | Fail: X% | Flagged: X%
- CRITICAL records: X (each requires documented disposition per 21 CFR 820.90)
- HIGH records: X (recommend secondary inspection before shipment)
- Audit trail coverage: [record_start] to [record_end] — demonstrates 21 CFR Part 11 electronic record compliance
- If any CRITICAL exist: cite FDA 21 CFR 820.100 — CAPA must be documented and tracked
- If nonconformance >15%: flag as potential FDA 483 observation risk

════════════════════════════════════════════════════════
GENERAL RULES FOR ALL OTHER QUESTIONS
════════════════════════════════════════════════════════
- Always run a query before answering — never guess at numbers
- Only SELECT queries are permitted — refuse any mutation request
- Cite specific counts and percentages — never say 'some' or 'many'
- If data is insufficient (<10 records for a trend): say so explicitly
- Flag patient-safety implications when defect rates exceed benchmarks
- End responses with one concrete recommended action
";

    public function index()
    {
        return view('intel.index');
    }

    public function query(Request $request)
    {
        $request->validate([
            'question' => 'required|string|max:600',
            'history'  => 'sometimes|array|max:20',
        ]);
        $question = $request->input('question');
        $apiKey   = config('services.anthropic.key');

        $systemPrompt = str_replace('%SCHEMA%', self::SCHEMA, self::SYSTEM_PROMPT);

        $tools = [[
            'name'        => 'query_inspection_database',
            'description' => 'Execute a read-only SQL SELECT query against the inspections SQLite database. Use the SQL templates from the system prompt when the question matches one of the 5 core QC tasks. Returns raw rows as JSON.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'sql'         => ['type' => 'string', 'description' => 'A safe SELECT query only. No INSERT/UPDATE/DELETE/DROP allowed. Use SQLite-compatible syntax (date() function, not NOW()).'],
                    'explanation' => ['type' => 'string', 'description' => 'One-line explanation of what this query retrieves and which of the 5 core tasks it serves.'],
                    'task_id'     => ['type' => 'string', 'description' => 'Which of the 5 core tasks this matches: TREND_ALERT | INSTRUMENT_PROFILE | SPIKE_DETECTION | CONFIDENCE_AUDIT | REGULATORY_EXPOSURE | OTHER'],
                ],
                'required' => ['sql', 'explanation'],
            ],
        ]];

        $messages = [];
        foreach (array_slice($request->input('history', []), -10) as $h) {
            $role = $h['role'] ?? '';
            if (in_array($role, ['user', 'assistant']) && !empty($h['content'])) {
                $messages[] = ['role' => $role, 'content' => (string)$h['content']];
            }
        }
        $messages[] = ['role' => 'user', 'content' => $question];

        // Turn 1 — Claude decides what to query
        $resp1 = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->withoutVerifying()->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1024,
            'system'     => $systemPrompt,
            'tools'      => $tools,
            'messages'   => $messages,
        ])->json();

        $messages[] = ['role' => 'assistant', 'content' => $resp1['content'] ?? []];

        // Execute tool calls
        $toolResults = [];
        foreach ($resp1['content'] ?? [] as $block) {
            if (($block['type'] ?? '') !== 'tool_use') continue;
            if (($block['name'] ?? '') !== 'query_inspection_database') continue;

            $sql    = trim($block['input']['sql'] ?? '');
            $taskId = $block['input']['task_id'] ?? 'OTHER';

            if (!preg_match('/^\s*SELECT\b/i', $sql)) {
                $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => 'REJECTED: Only SELECT queries permitted.'];
                continue;
            }

            try {
                $rows = DB::select($sql);
                Log::info('DAMIAN query', ['task' => $taskId, 'sql' => $sql, 'rows' => count($rows)]);
                $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => json_encode($rows)];
            } catch (\Throwable $e) {
                $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => 'Query error: ' . $e->getMessage()];
            }
        }

        // Turn 2 — Claude interprets results
        $answer = '';
        if (!empty($toolResults)) {
            $messages[] = ['role' => 'user', 'content' => $toolResults];
            $resp2 = Http::withHeaders([
                'x-api-key'         => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->withoutVerifying()->timeout(60)->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-haiku-4-5-20251001',
                'max_tokens' => 1500,
                'system'     => $systemPrompt,
                'tools'      => $tools,
                'messages'   => $messages,
            ])->json();

            foreach ($resp2['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') $answer .= $block['text'];
            }
        } else {
            foreach ($resp1['content'] ?? [] as $block) {
                if (($block['type'] ?? '') === 'text') $answer .= $block['text'];
            }
        }

        return response()->json(['answer' => $answer ?: 'No data found for that question.']);
    }
}
