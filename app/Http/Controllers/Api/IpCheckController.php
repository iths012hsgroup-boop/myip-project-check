<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class IpCheckController extends Controller
{
    public function checkIp(Request $request, $ip = null)
    {
        // Jika IP tidak di-pass di parameter, ambil dari request client
        $ipAddress = $ip ?? $request->ip();

        // Hindari cek localhost (untuk testing lokal)
        if ($ipAddress === '127.0.0.1' || $ipAddress === '::1') {
            return response()->json([
                'status' => 'success',
                'cached' => false,
                'data' => [
                    'ip' => $ipAddress,
                    'city' => 'Localhost',
                    'country' => 'Local',
                    'isp' => 'Local ISP',
                ]
            ]);
        }

        // Cache Key unik untuk IP ini
        $cacheKey = 'ip_location_' . str_replace(':', '_', $ipAddress);

        // Ambil dari Cache jika ada, jika tidak, fetch dari API dan simpan di cache
        // Waktu cache: 30 hari (30 * 24 * 60 * 60 detik)
        $data = Cache::remember($cacheKey, 30 * 24 * 60 * 60, function () use ($ipAddress) {
            // Memanggil API gratis dari ip-api.com
            // Kita meminta return dalam JSON format
            $response = Http::get("http://ip-api.com/json/{$ipAddress}");

            if ($response->successful()) {
                return $response->json();
            }

            return null; // Jika gagal
        });

        if (!$data || ($data['status'] ?? '') === 'fail') {
             return response()->json([
                'status' => 'error',
                'message' => 'Gagal mendapatkan data lokasi IP',
                'ip' => $ipAddress,
            ], 404);
        }

        // Mengecek apakah data ini dari cache atau fresh
        $isCached = Cache::has($cacheKey);

        return response()->json([
            'status' => 'success',
            'cached' => $isCached, // Hanya untuk bukti bahwa cache bekerja
            'data' => [
                'ip' => $data['query'] ?? $ipAddress,
                'country' => $data['country'] ?? 'Unknown',
                'countryCode' => $data['countryCode'] ?? '',
                'regionName' => $data['regionName'] ?? 'Unknown',
                'city' => $data['city'] ?? 'Unknown',
                'zip' => $data['zip'] ?? '',
                'lat' => $data['lat'] ?? 0,
                'lon' => $data['lon'] ?? 0,
                'timezone' => $data['timezone'] ?? '',
                'isp' => $data['isp'] ?? 'Unknown',
                'org' => $data['org'] ?? 'Unknown',
                'as' => $data['as'] ?? 'Unknown',
            ]
        ]);
    }
}
