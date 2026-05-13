<?php

namespace App\Http\Controllers\Api;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InspectionController extends Controller
{
    /**
     * List all inspections with filters and pagination
     * GET /api/inspections?page=1&per_page=20&status=PASS&user_id=1
     */
    public function index(Request $request): JsonResponse
    {
        $query = Inspection::with('user');

        // Filter by status
        if ($request->has('status')) {
            $query->where('pass_fail', $request->input('status'));
        }

        // Filter by user_id
        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        // Filter by defect type
        if ($request->has('defect_type')) {
            $query->where('defect_type', 'like', '%' . $request->input('defect_type') . '%');
        }

        // Filter by date range
        if ($request->has('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->has('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $inspections = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $inspections->items(),
            'pagination' => [
                'total' => $inspections->total(),
                'count' => $inspections->count(),
                'per_page' => $inspections->perPage(),
                'current_page' => $inspections->currentPage(),
                'last_page' => $inspections->lastPage(),
            ],
        ], 200);
    }

    /**
     * Get specific inspection
     * GET /api/inspections/{id}
     */
    public function show(Inspection $inspection): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $inspection->load('user'),
        ], 200);
    }

    /**
     * Upload image and trigger analysis
     * POST /api/inspections
     * 
     * Request body:
     * {
     *   "image": <file>,
     *   "user_id": 1 (optional, defaults to 1)
     * }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate input
            $request->validate([
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
                'user_id' => 'sometimes|integer|exists:users,id',
            ]);

            // Store image
            $path = $request->file('image')->store('inspections', 'public');

            // Create inspection record
            $inspection = Inspection::create([
                'user_id' => $request->input('user_id', 1),
                'image_path' => $path,
                'pass_fail' => 'PENDING',
            ]);

            // Trigger async analysis (for now, sync)
            $this->analyzeWithClaude($inspection);

            return response()->json([
                'status' => 'success',
                'message' => 'Image uploaded and analysis started',
                'data' => $inspection->fresh(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('API inspection upload error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process image',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analyze image using Claude Vision API
     */
    private function analyzeWithClaude(Inspection $inspection): void
    {
        try {
            $imagePath = storage_path('app/public/' . $inspection->image_path);

            if (!file_exists($imagePath)) {
                throw new \Exception('Image file not found');
            }

            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            $response = Http::withHeaders([
                'x-api-key' => env('ANTHROPIC_API_KEY'),
                'anthropic-version' => '2023-06-01',
            ])->withoutVerifying()->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-6',
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'image',
                                'source' => [
                                    'type' => 'base64',
                                    'media_type' => $mimeType,
                                    'data' => $imageData,
                                ],
                            ],
                            [
                                'type' => 'text',
                                'text' => $this->getInspectionPrompt(),
                            ],
                        ],
                    ],
                ],
            ]);

            if ($response->successful()) {
                $result = $response->json();
                if (isset($result['content'][0]['text'])) {
                    $this->parseAndSaveResults($inspection, $result['content'][0]['text']);
                }
            } else {
                throw new \Exception('API request failed: ' . $response->status());
            }
        } catch (\Exception $e) {
            Log::error('Claude Vision API error: ' . $e->getMessage());
            $inspection->update([
                'pass_fail' => 'ERROR',
                'claude_reasoning' => 'API Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get inspection prompt
     */
    private function getInspectionPrompt(): string
    {
        return <<<'PROMPT'
You are an expert surgical instrument quality inspector. Analyze this image of a surgical instrument and:

1. Identify any defects (rust, corrosion, pitting, cracks, discoloration, damage, etc.)
2. Rate your confidence (0-100%)
3. Provide a pass/fail recommendation
4. Give detailed reasoning

Respond in JSON format:
{
    "defect_type": "string or null if no defects",
    "confidence": number,
    "pass_fail": "PASS or FAIL",
    "reasoning": "detailed explanation"
}
PROMPT;
    }

    /**
     * Parse and save Claude results
     */
    private function parseAndSaveResults(Inspection $inspection, string $response): void
    {
        try {
            preg_match('/\{.*\}/s', $response, $matches);

            if (empty($matches)) {
                throw new \Exception('Could not parse JSON response');
            }

            $data = json_decode($matches[0], true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            $inspection->update([
                'defect_type' => $data['defect_type'] ?? null,
                'confidence' => $data['confidence'] ?? 0,
                'pass_fail' => $data['pass_fail'] ?? 'FAIL',
                'claude_reasoning' => $data['reasoning'] ?? $response,
            ]);

        } catch (\Exception $e) {
            Log::error('Result parsing error: ' . $e->getMessage());
            $inspection->update([
                'claude_reasoning' => 'Parse Error: ' . $response,
                'pass_fail' => 'PENDING',
            ]);
        }
    }
}
