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
  id, user_id, instrument_id, operator_id,
  defect_type (cracks|corrosion|scratches|porosity|none),
  instrument_class (scalpel|forceps|scissors|needle_holder|clamp|retractor|Unknown),
  confidence (0-100 float),
  pass_fail (PASS|FAIL|FLAGGED),
  composite_risk_score (numeric, higher=worse),
  risk_level (LOW|MEDIUM|HIGH|CRITICAL),
  yolo_count (integer, number of defects detected),
  inference_ms (integer, pipeline latency),
  created_at (datetime, UTC)
";

    public function index()
    {
        return view('intel.index');
    }

    public function query(Request $request)
    {
        $request->validate(['question' => 'required|string|max:600']);
        $question = $request->input('question');
        $apiKey   = config('services.anthropic.key');

        $tools = [[
            'name'        => 'query_inspection_database',
            'description' => 'Execute a read-only SQL SELECT query against the inspections database to answer QC analytics questions. Returns raw rows as JSON.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'sql'         => ['type' => 'string', 'description' => 'A safe SELECT query only. No INSERT/UPDATE/DELETE/DROP allowed.'],
                    'explanation' => ['type' => 'string', 'description' => 'One-line explanation of what this query retrieves.'],
                ],
                'required' => ['sql', 'explanation'],
            ],
        ]];

        $system = "You are DAMIAN, an AI quality-control data analyst embedded in the COSMAS surgical instrument manufacturing QC system. Cosmas and Damian are the patron saints of medicine and surgery. Answer the user question using the database tool. Be concise, cite numbers, and flag patient-safety concerns if trends warrant it. Database schema:" . self::SCHEMA;

        $messages = [['role' => 'user', 'content' => $question]];

        // Turn 1 — Claude decides what to query
        $resp1 = Http::withHeaders([
            'x-api-key'         => $apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type'      => 'application/json',
        ])->withoutVerifying()->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model'      => 'claude-haiku-4-5-20251001',
            'max_tokens' => 1024,
            'system'     => $system,
            'tools'      => $tools,
            'messages'   => $messages,
        ])->json();

        $messages[] = ['role' => 'assistant', 'content' => $resp1['content'] ?? []];

        // Execute tool calls
        $toolResults = [];
        foreach ($resp1['content'] ?? [] as $block) {
            if (($block['type'] ?? '') !== 'tool_use') continue;
            if (($block['name'] ?? '') !== 'query_inspection_database') continue;

            $sql = trim($block['input']['sql'] ?? '');

            if (!preg_match('/^\s*SELECT\b/i', $sql)) {
                $toolResults[] = ['type' => 'tool_result', 'tool_use_id' => $block['id'], 'content' => 'REJECTED: Only SELECT queries permitted.'];
                continue;
            }

            try {
                $rows = DB::select($sql);
                Log::info('DAMIAN query', ['sql' => $sql, 'rows' => count($rows)]);
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
                'max_tokens' => 1024,
                'system'     => $system,
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
