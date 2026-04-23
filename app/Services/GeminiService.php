<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model = 'gemini-1.5-flash';
    protected string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY', '');
    }

    /**
     * Generate SEO metadata (title, description, tags) for a video based on its messy title/filename.
     */
    public function suggestMetadata(string $input): array
    {
        if (empty($this->apiKey)) {
            return [
                'error' => 'Gemini API Key is not configured in .env',
                'success' => false
            ];
        }

        $prompt = "You are an SEO expert for a premium video sharing platform called VideyView. 
                   I will give you a messy filename or video title. 
                   Your task is to provide:
                   1. A clean, catchy, and SEO-friendly title.
                   2. A compelling meta description (max 160 characters).
                   3. A comma-separated list of relevant tags (max 10 tags).
                   4. A suggested category name from these options: [Action, Amateur, Professional, Viral, Special].
                   
                   Format your response ONLY as a JSON object with keys: title, description, tags, category.
                   
                   Input: {$input}";

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}{$this->model}:generateContent?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ]
            ]);

            if ($response->failed()) {
                Log::error("Gemini API Error: " . $response->body());
                return [
                    'error' => 'Failed to reach Gemini API.',
                    'success' => false
                ];
            }

            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '{}';
            
            return [
                'data' => json_decode($text, true),
                'success' => true
            ];
        } catch (\Exception $e) {
            Log::error("Gemini Service Exception: " . $e->getMessage());
            return [
                'error' => 'An exception occurred while calling Gemini API.',
                'success' => false
            ];
        }
    }
}
