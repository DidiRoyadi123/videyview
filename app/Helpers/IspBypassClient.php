<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Log;

class IspBypassClient
{
    /**
     * Real IP pools for domains blocked by ISP.
     * v3.0: Supports multiple IPs for failover and rotation.
     */
    protected static array $mappings = [
        'videy.co'           => ['172.67.73.18', '104.21.51.207', '104.21.31.25', '172.67.68.221'],
        'cdn.videy.co'       => ['172.67.73.18', '104.21.51.207', '104.21.13.19', '172.67.202.164'],
        'api.streamtape.com' => ['195.35.23.222', '195.35.23.223', '195.35.23.235'],
        'streamtape.com'     => ['195.35.23.222', '195.35.23.223'],
        'streamtape.to'      => ['195.35.23.222', '195.35.23.223'],
        // Content Clusters
        '861520586.tapecontent.net' => ['51.89.194.202'],
        '861113552.tapecontent.net' => ['51.83.140.208'],
    ];

    /**
     * Get a rotated IP for the given host.
     */
    protected static function getIpForHost(string $host): ?string
    {
        $ips = self::$mappings[$host] ?? null;
        if (!$ips) return null;

        if (is_array($ips)) {
            // Smart Rotation: Use a simple random selection for now
            // Future v3.1: Add health-check based selection
            return $ips[array_rand($ips)];
        }

        return $ips;
    }

    /**
     * Perform a cURL request with DNS bypass (ISP Bypass).
     */
    public static function request(string $method, string $url, array $params = [], array $headers = [], bool $isJson = true)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ip = self::getIpForHost($host);

        $ch = curl_init();
        
        $fullUrl = $url;
        if (strtoupper($method) === 'GET' && !empty($params)) {
            $fullUrl .= (str_contains($url, '?') ? '&' : '?') . http_build_query($params);
        }

        curl_setopt($ch, CURLOPT_URL, $fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60); // 1 minute timeout for API calls

        if ($ip) {
            // DNS Bypass technique from VideoController::proxy
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:443:{$ip}", "{$host}:80:{$ip}"]);
            Log::debug("IspBypassClient: Using RESOLVE {$host} -> {$ip}");
        }

        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: application/json',
        ];

        if (strtoupper($method) === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($isJson) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
                $defaultHeaders[] = 'Content-Type: application/json';
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            Log::error("IspBypassClient Error ($host): $error");
            return [
                'success' => false,
                'status'  => $httpCode,
                'error'   => $error,
                'body'    => $response
            ];
        }

        $decoded = json_decode($response, true);

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status'  => $httpCode,
            'body'    => $response,
            'json'    => $decoded
        ];
    }

    /**
     * Perform a multipart upload with DNS bypass.
     */
    public static function upload(string $url, string $filePath, array $fields = [], array $headers = [])
    {
        $host = parse_url($url, PHP_URL_HOST);
        $ip = self::getIpForHost($host);

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 900); // 15 minutes for large file uploads

        if ($ip) {
            curl_setopt($ch, CURLOPT_RESOLVE, ["{$host}:443:{$ip}", "{$host}:80:{$ip}"]);
        }

        $fields['file'] = new \CURLFile($filePath);
        
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);

        $defaultHeaders = [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ];

        curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($defaultHeaders, $headers));

        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            Log::error("IspBypassClient Upload Error ($host): $error");
            return ['success' => false, 'error' => $error];
        }

        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'status'  => $httpCode,
            'body'    => $response,
            'json'    => json_decode($response, true)
        ];
    }
}
