<?php

namespace App\Http\Controllers;

use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class InspectionController extends Controller
{
    /**
     * Show the upload form
     */
    public function showUploadForm()
    {
        return view('inspections.upload');
    }

    /**
     * Handle image upload and analysis
     */
    public function upload(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        try {
            // Store the image
            $path = $request->file('image')->store('inspections', 'public');
            
            // Create inspection record with pending status
            $inspection = Inspection::create([
                'user_id' => auth()->id() ?? 1, // Default to user 1 for MVP
                'image_path' => $path,
                'pass_fail' => 'PENDING',
            ]);

            // Call Claude Vision API for analysis
            $this->analyzeWithClaude($inspection);

            return redirect()->route('inspections.results', $inspection->id)
                ->with('success', 'Image uploaded and analyzed successfully');
        } catch (\Exception $e) {
            Log::error('Inspection upload error: ' . $e->getMessage());
            return back()->with('error', 'Failed to process image: ' . $e->getMessage());
        }
    }

    /**
     * Analyze image using Claude Vision API
     */
    private function analyzeWithClaude(Inspection $inspection)
    {
        try {
            $imagePath = storage_path('app/public/' . $inspection->image_path);
            
            if (!file_exists($imagePath)) {
                throw new \Exception('Image file not found');
            }

            // Read image and encode to base64
            $imageData = base64_encode(file_get_contents($imagePath));
            $mimeType = mime_content_type($imagePath);

            // Prepare Claude Vision API request using Laravel's HTTP client
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
     * Get the inspection prompt for Claude
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
     * Parse and save Claude API results
     */
    private function parseAndSaveResults(Inspection $inspection, string $response)
    {
        try {
            // Extract JSON from response
            preg_match('/\{.*\}/s', $response, $matches);
            
            if (empty($matches)) {
                throw new \Exception('Could not parse JSON response');
            }

            $data = json_decode($matches[0], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON: ' . json_last_error_msg());
            }

            // Update inspection record
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

    /**
     * Show inspection results
     */
    public function results(Inspection $inspection)
    {
        return view('inspections.results', compact('inspection'));
    }

    /**
     * Show audit log
     */
    public function auditLog()
    {
        $inspections = Inspection::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        return view('inspections.audit-log', compact('inspections'));
    }
}
