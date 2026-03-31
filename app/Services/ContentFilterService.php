<?php

namespace App\Services;

class ContentFilterService
{
    protected $badWords = [
        // Slurs & Insults (Indonesian & English)
        'anjing', 'babi', 'monyet', 'tolol', 'goblok', 'idiot', 'puki', 'kontol', 'memek', 'ngentot', 'fuck', 'shit', 'asshole', 'bitch',
        // Racial/Hate
        'rasis', 'negro', 'cina', 'pribumi', 'kafir',
        // Gambling (Judol)
        'judi', 'slot', 'gacor', 'zeus', 'olympus', 'maxwin', 'depo', 'wd', 'deposit', 'withdraw', 'bet', 'casino', 'pragmatic',
        // Common Scam/Spam terms
        'free money', 'get rich', 'click here',
    ];

    /**
     * Check if content contains forbidden keywords or links.
     * Returns true if content is clean, false if inappropriate.
     */
    public function isClean(string $content): bool
    {
        $content = strtolower($content);

        // Check for bad words
        foreach ($this->badWords as $word) {
            if (str_contains($content, $word)) {
                return false;
            }
        }

        // Check for links (dangerous links, gambling sites, etc.)
        // Robust regex for URLs
        if (preg_match('/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/\S*)?/', $content)) {
            return false;
        }

        return true;
    }

    /**
     * Get error message based on content failure.
     */
    public function getErrorMessage(string $content): string
    {
        if ($this->hasLinks($content)) {
            return 'Sharing links or promoting gambling sites is strictly prohibited.';
        }

        return 'Your comment contains inappropriate language and has been blocked.';
    }

    protected function hasLinks(string $content): bool
    {
        return preg_match('/[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}(\/\S*)?/', $content);
    }
}
