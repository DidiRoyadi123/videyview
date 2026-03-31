<?php

namespace App\Services;

use App\Models\Video;
use App\Services\Hosting\HostingDriver;
use Illuminate\Support\Facades\Log;

class MultiHostService
{
    protected array $drivers = [];

    /**
     * Register a hosting driver.
     */
    public function registerDriver(HostingDriver $driver): void
    {
        $this->drivers[$driver->getIdentifier()] = $driver;
    }

    /**
     * Get a specific driver by its identifier.
     */
    public function driver(string $identifier): ?HostingDriver
    {
        return $this->drivers[$identifier] ?? null;
    }

    /**
     * Get all registered drivers.
     */
    public function allDrivers(): array
    {
        return $this->drivers;
    }

    /**
     * Update the hosting status and mirror link for a video in a single atomic operation.
     */
    public function updateStatus(Video $video, string $host, string $status, ?array $data = null): void
    {
        // Refresh to ensure we have the absolute latest state before updating
        $video = $video->fresh();
        
        $currentStatus = $video->hosting_status ?? [];
        
        // If status is failed, we can append the error message for the dashboard
        if ($status === 'failed' && isset($data['error'])) {
            $currentStatus[$host] = 'failed: ' . substr($data['error'], 0, 50);
        } else {
            $currentStatus[$host] = $status;
        }
        
        $currentLinks = is_array($video->mirror_links) ? $video->mirror_links : [];
        if ($data && $status === 'success') {
            // Find if this host already has a link in the old array-of-objects format
            // but for simplicity we convert to the cleaner keyed format: [host => url]
            $url = is_array($data) ? ($data['embed_url'] ?? $data['url'] ?? $data) : $data;
            if (is_string($url)) {
                $currentLinks[$host] = $url;
            }
        }

        $video->update([
            'hosting_status' => $currentStatus,
            'mirror_links' => $currentLinks,
        ]);
        
        Log::info("Mirror status updated for video {$video->id} on host {$host}: {$status}");
    }
}
