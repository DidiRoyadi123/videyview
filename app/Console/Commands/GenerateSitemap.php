<?php

namespace App\Console\Commands;

use App\Models\Video;
use Illuminate\Console\Command;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'video:sitemap';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a lightweight sitemap.xml for search engines';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Initializing High-Efficiency Sitemap Engine...");

        $path = public_path('sitemap.xml');
        $handle = fopen($path, 'w');

        if (!$handle) {
            $this->error("Failed to open sitemap.xml for writing!");
            return;
        }

        // Write Header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL);
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL);

        // Write Home Page
        fwrite($handle, '  <url>' . PHP_EOL);
        fwrite($handle, '    <loc>' . url('/') . '</loc>' . PHP_EOL);
        fwrite($handle, '    <priority>1.0</priority>' . PHP_EOL);
        fwrite($handle, '    <changefreq>daily</changefreq>' . PHP_EOL);
        fwrite($handle, '  </url>' . PHP_EOL);

        // Stream Videos with Cursor to save memory
        $this->info("Streaming video entries...");
        foreach (Video::latest()->select('slug', 'updated_at')->cursor() as $video) {
            $entry = '  <url>' . PHP_EOL;
            $entry .= '    <loc>' . route('videos.show', $video->slug) . '</loc>' . PHP_EOL;
            $entry .= '    <lastmod>' . $video->updated_at->toAtomString() . '</lastmod>' . PHP_EOL;
            $entry .= '    <priority>0.8</priority>' . PHP_EOL;
            $entry .= '    <changefreq>weekly</changefreq>' . PHP_EOL;
            $entry .= '  </url>' . PHP_EOL;
            
            fwrite($handle, $entry);
        }

        // Write Footer
        fwrite($handle, '</urlset>');
        fclose($handle);

        $this->info("Sitemap.xml has been successfully deployed using the streaming engine!");
    }
}
