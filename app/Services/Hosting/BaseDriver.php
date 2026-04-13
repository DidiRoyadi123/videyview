<?php

namespace App\Services\Hosting;

use Illuminate\Support\Facades\Http;

abstract class BaseDriver implements HostingDriver
{
    /**
     * Get a pre-configured Http client that bypasses ISP blocks (Internet Positif)
     * using the CURLOPT_RESOLVE technique from the user's video-proxy.
     */
    protected function http(?string $url = null)
    {
        // Mapping of domains to verified stable Cloudflare/Direct IPs
        $domainIps = [
            'doodapi.com' => '172.67.70.170',
            'doodapi.co' => '172.67.70.170',
            'dood.to' => '172.67.70.170',
            'dood.re' => '172.67.70.170',
            'dood.li' => '172.67.70.170',
            'streamtape.com' => '195.35.23.222',
            'api.streamtape.com' => '104.21.96.46',
            'streamtape.to' => '195.35.23.222',
            'tapecontent.net' => '51.89.194.202',
            'filemoon.sx' => '185.248.171.24',
            'filemoon.org' => '185.248.171.24',
            'seekstreaming.com' => '172.67.70.170',
            'videy.co' => '172.67.73.18',
        ];

        // ISP BYPASS: Map domains directly to IPs to avoid DNS/SNI filtering
        $resolve = [];
        foreach ($domainIps as $domain => $ip) {
            $resolve[] = "$domain:443:$ip";
            $resolve[] = "$domain:80:$ip";
        }

        // DYNAMICS BYPASS: Handle transient subdomains (like sl-123.tapecontent.net)
        if ($url) {
            $urlHost = parse_url($url, PHP_URL_HOST);
            if ($urlHost && !isset($domainIps[$urlHost])) {
                if (str_contains($urlHost, 'streamtape') || str_contains($urlHost, 'tapecontent')) {
                    $resolve[] = "$urlHost:443:51.89.194.202";
                    $resolve[] = "$urlHost:80:51.89.194.202";
                } elseif (str_contains($urlHost, 'filemoon') || str_contains($urlHost, 'seekstreaming')) {
                    $resolve[] = "$urlHost:443:172.67.73.18";
                    $resolve[] = "$urlHost:80:172.67.73.18";
                } elseif (str_contains($urlHost, 'dood')) {
                    $resolve[] = "$urlHost:443:172.67.70.170";
                    $resolve[] = "$urlHost:80:172.67.70.170";
                }
            }
        }

        $apiHost = parse_url($this->getApiBaseUrl(), PHP_URL_HOST);

        return Http::withoutVerifying()
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36',
                'Accept' => '*/*',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Referer' => "https://$apiHost/",
            ])
            ->withOptions([
                'curl' => [
                    CURLOPT_RESOLVE => $resolve,
                    CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
                ],
                'timeout' => 900, // Large files need time
                'connect_timeout' => 15,
            ]);
    }

    /**
     * Each driver must provide its primary API base URL for header generation.
     */
    abstract protected function getApiBaseUrl(): string;
}
