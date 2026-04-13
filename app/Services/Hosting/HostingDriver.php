<?php

namespace App\Services\Hosting;

use App\Models\Video;

interface HostingDriver
{
    /**
     * Get the unique identifier for this host (e.g. 'doodstream', 'streamtape').
     */
    public function getIdentifier(): string;

    /**
     * Upload a video to the host.
     * Returns an array containing 'file_id' and 'embed_url'.
     */
    public function upload(Video $video): array;

    /**
     * Get the current status of a file on the host.
     */
    public function getStatus(string $fileId): string;

    /**
     * Get the thumbnail URL from the host if available.
     */
    public function getThumbnailUrl(string $fileId): ?string;

    /**
     * Check the status of a remote upload/link copy task.
     */
    public function checkRemoteStatus(string $remoteId): array;
}
