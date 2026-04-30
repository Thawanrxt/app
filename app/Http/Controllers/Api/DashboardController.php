<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardMetricsService;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function show(DashboardMetricsService $dashboardMetrics): JsonResponse
    {
        $dashboard = $dashboardMetrics->buildPayload('all');
        $weather = $this->fetchWeather();

        return response()->json([
            'data' => [
                'updated_at' => $dashboard['updated_at'] ?: $weather['updated_at'],
                'summary' => $dashboard['summary'],
                'quick_stats' => $dashboard['quick_stats'],
                'status_overview' => $dashboard['status_overview'],
                'weather' => $weather,
                'urgent_alerts' => $dashboard['urgent_alerts'],
                'recent_activities' => $dashboard['recent_activities'],
                'today_followups' => $dashboard['today_followups'],
                'latest_assessments' => $dashboard['latest_assessments'],
                'common_issues' => $dashboard['common_issues'],
                'calendar_events' => $dashboard['calendar_events'],
            ],
        ]);
    }

    private function fetchWeather(): array
    {
        $defaultWeather = [
            'updated_at' => null,
            'condition' => '',
            'temperature_celsius' => null,
            'humidity_percent' => null,
            'wind_kmh' => null,
            'rain_chance_percent' => null,
            'advice' => '',
            'source' => 'open-meteo',
        ];

        $cacheKey = sprintf(
            'dashboard_weather_%s_%s',
            config('services.open_meteo.latitude'),
            config('services.open_meteo.longitude')
        );

        return Cache::store('file')->remember($cacheKey, now()->addMinutes(15), function () use ($defaultWeather): array {
            try {
                $response = Http::timeout(10)
                    ->retry(2, 300)
                    ->acceptJson()
                    ->get('https://api.open-meteo.com/v1/forecast', [
                        'latitude' => config('services.open_meteo.latitude'),
                        'longitude' => config('services.open_meteo.longitude'),
                        'timezone' => config('services.open_meteo.timezone', 'Asia/Bangkok'),
                        'current' => 'temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m',
                        'daily' => 'precipitation_probability_max',
                        'forecast_days' => 1,
                    ])
                    ->throw();
            } catch (RequestException|\Throwable $exception) {
                report($exception);

                return $defaultWeather;
            }

            $payload = $response->json();
            $current = $payload['current'] ?? [];
            $daily = $payload['daily'] ?? [];

            $rainChance = $daily['precipitation_probability_max'][0] ?? null;
            $condition = $this->mapWeatherCondition((int) ($current['weather_code'] ?? -1));

            return [
                'updated_at' => $current['time'] ?? null,
                'condition' => $condition,
                'temperature_celsius' => $current['temperature_2m'] ?? null,
                'humidity_percent' => $current['relative_humidity_2m'] ?? null,
                'wind_kmh' => $current['wind_speed_10m'] ?? null,
                'rain_chance_percent' => $rainChance,
                'advice' => $this->buildWeatherAdvice($condition, $rainChance),
                'source' => 'open-meteo',
            ];
        });
    }

    private function mapWeatherCondition(int $weatherCode): string
    {
        return match (true) {
            $weatherCode === 0 => 'ท้องฟ้าแจ่มใส',
            in_array($weatherCode, [1, 2], true) => 'มีเมฆบางส่วน',
            $weatherCode === 3 => 'เมฆมาก',
            in_array($weatherCode, [45, 48], true) => 'มีหมอก',
            in_array($weatherCode, [51, 53, 55, 56, 57], true) => 'ฝนปรอย',
            in_array($weatherCode, [61, 63, 65, 66, 67, 80, 81, 82], true) => 'มีฝน',
            in_array($weatherCode, [71, 73, 75, 77, 85, 86], true) => 'หิมะ',
            in_array($weatherCode, [95, 96, 99], true) => 'พายุฝนฟ้าคะนอง',
            default => 'ไม่ทราบสภาพอากาศ',
        };
    }

    private function buildWeatherAdvice(string $condition, int|float|null $rainChance): string
    {
        if ($rainChance !== null && $rainChance >= 70) {
            return 'มีแนวโน้มฝนสูง ควรตรวจระบบระบายน้ำและเลี่ยงงานกลางแจ้ง';
        }

        if (str_contains($condition, 'พายุ')) {
            return 'ควรเลื่อนงานลงพื้นที่และเฝ้าระวังความเสียหายจากลมแรง';
        }

        if (str_contains($condition, 'ฝน')) {
            return 'ควรเตรียมแผนตรวจแปลงหลังฝนและระวังน้ำขัง';
        }

        if ($condition === 'ท้องฟ้าแจ่มใส') {
            return 'อากาศค่อนข้างดี เหมาะกับการวางแผนลงพื้นที่';
        }

        return 'ติดตามสภาพอากาศอย่างต่อเนื่องก่อนวางแผนงานภาคสนาม';
    }
}
