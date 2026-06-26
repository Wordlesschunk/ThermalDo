<?php

declare(strict_types=1);

namespace App\Components;

use Mike42\Escpos\Printer;
use RuntimeException;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final readonly class Weather implements ComponentInterface
{
    private const string GEOCODING_URL =
        'https://geocoding-api.open-meteo.com/v1/search';

    private const string FORECAST_URL =
        'https://api.open-meteo.com/v1/forecast';

    public Printer $printer;

    public function __construct(
        private HttpClientInterface $httpClient,
        Printer $printer
    ) {
        $this->printer = $printer;
    }

    public function print(string $location = 'Grimsby'): void
    {
        $weatherData = $this->getWeather($location);

        $icon = $this->getWeatherIcon(
            $weatherData['icon']
        );

        $currentRainChance = $weatherData['rain_chance']['now'];

        $info = [
            sprintf(
                'Location:    %s',
                $location
            ),
            sprintf(
                'Condition:   %s',
                $weatherData['condition']
            ),
            sprintf(
                'Temperature: H:%.1f°C L:%.1f°C',
                $weatherData['temperature']['high'],
                $weatherData['temperature']['low']
            ),
            sprintf(
                'Feels Like:  %.1f°C',
                $weatherData['feels_like']['value']
            ),
            sprintf(
                'Rain Chance: %s',
                $currentRainChance === null
                    ? 'N/A'
                    : $currentRainChance . '%'
            ),
            sprintf(
                'Max Rain:    %d%%',
                $weatherData['rain_chance']['maximum_today']
            ),
            sprintf(
                'Wind:        %.1f km/h',
                $weatherData['wind']['speed']
            ),
            sprintf(
                'Humidity:    %d%%',
                $weatherData['humidity']['value']
            ),
            sprintf(
                'Pressure:    %.0f hPa',
                $weatherData['pressure']['value']
            ),
        ];

        $height = max(
            count($icon),
            count($info)
        );

        for ($i = 0; $i < $height; $i++) {
            $left = $icon[$i] ?? '';
            $right = $info[$i] ?? '';

            $this->printer->text(
                str_pad($left, 16) .
                $right .
                "\n"
            );
        }

        $this->printer->feed();
    }

    /**
     * Retrieve the current weather without printing it.
     */
    private function getWeather(string $locationName): array
    {
        $location = $this->findLocation($locationName);
        $weather = $this->fetchForecast($location);

        $current = $weather['current'];
        $daily = $weather['daily'];

        $weatherCode = (int) $current['weather_code'];

        /*
         * Open-Meteo returns:
         *
         * 1 = Day
         * 0 = Night
         */
        $isDay = (int) ($current['is_day'] ?? 1) === 1;

        return [
            'location' => $this->formatLocation($location),
            'condition' => $this->getCondition($weatherCode),
            'icon' => $this->getIconType(
                $weatherCode,
                $isDay
            ),
            'is_day' => $isDay,

            'temperature' => [
                'now' => (float) $current['temperature_2m'],
                'high' => (float) $daily['temperature_2m_max'][0],
                'low' => (float) $daily['temperature_2m_min'][0],
                'unit' => '°C',
            ],

            'feels_like' => [
                'value' => (float) $current['apparent_temperature'],
                'unit' => '°C',
            ],

            'wind' => [
                'speed' => (float) $current['wind_speed_10m'],
                'unit' => 'km/h',
            ],

            'humidity' => [
                'value' => (int) $current['relative_humidity_2m'],
                'unit' => '%',
            ],

            'rain_chance' => [
                'now' => $this->getCurrentRainChance($weather),
                'maximum_today' => (int) (
                    $daily['precipitation_probability_max'][0] ?? 0
                ),
                'unit' => '%',
            ],

            'pressure' => [
                'value' => (float) $current['pressure_msl'],
                'unit' => 'hPa',
            ],

            'observed_at' => $current['time'],
        ];
    }

    private function findLocation(string $locationName): array
    {
        $response = $this->httpClient->request(
            'GET',
            self::GEOCODING_URL,
            [
                'query' => [
                    'name' => $locationName,
                    'count' => 1,
                    'language' => 'en',
                    'format' => 'json',
                ],
            ]
        )->toArray();

        $location = $response['results'][0] ?? null;

        if ($location === null) {
            throw new RuntimeException(
                sprintf(
                    'Could not find weather location "%s".',
                    $locationName
                )
            );
        }

        return $location;
    }

    private function fetchForecast(array $location): array
    {
        return $this->httpClient->request(
            'GET',
            self::FORECAST_URL,
            [
                'query' => [
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],

                    'current' => implode(',', [
                        'temperature_2m',
                        'apparent_temperature',
                        'relative_humidity_2m',
                        'weather_code',
                        'is_day',
                        'wind_speed_10m',
                        'wind_direction_10m',
                        'pressure_msl',
                    ]),

                    'hourly' => implode(',', [
                        'precipitation_probability',
                    ]),

                    'daily' => implode(',', [
                        'temperature_2m_max',
                        'temperature_2m_min',
                        'precipitation_probability_max',
                    ]),

                    'timezone' => $location['timezone']
                        ?? 'Europe/London',

                    'forecast_days' => 1,
                ],
            ]
        )->toArray();
    }

    private function formatLocation(array $location): string
    {
        $parts = array_filter([
            $location['name'] ?? null,
            $location['admin1'] ?? null,
            $location['country'] ?? null,
        ]);

        return implode(
            ', ',
            array_unique($parts)
        );
    }

    private function getCurrentRainChance(array $weather): ?int
    {
        $currentTime = $weather['current']['time'] ?? null;
        $hourlyTimes = $weather['hourly']['time'] ?? [];
        $rainChances =
            $weather['hourly']['precipitation_probability'] ?? [];

        if ($currentTime === null) {
            return null;
        }

        /*
         * Convert something such as:
         *
         * 2026-06-26T17:15
         *
         * Into the matching hourly value:
         *
         * 2026-06-26T17:00
         */
        $currentHour = substr(
                $currentTime,
                0,
                13
            ) . ':00';

        $index = array_search(
            $currentHour,
            $hourlyTimes,
            true
        );

        if (
            $index === false
            || !isset($rainChances[$index])
        ) {
            return null;
        }

        return (int) $rainChances[$index];
    }

    private function getCondition(int $code): string
    {
        return match ($code) {
            0 => 'Clear',
            1 => 'Mainly clear',
            2 => 'Partly cloudy',
            3 => 'Overcast',

            45, 48 => 'Foggy',

            51, 53, 55 => 'Drizzle',
            56, 57 => 'Freezing drizzle',

            61 => 'Light rain',
            63 => 'Moderate rain',
            65 => 'Heavy rain',
            66, 67 => 'Freezing rain',

            71 => 'Light snow',
            73 => 'Moderate snow',
            75 => 'Heavy snow',
            77 => 'Snow grains',

            80 => 'Light rain showers',
            81 => 'Moderate rain showers',
            82 => 'Heavy rain showers',

            85, 86 => 'Snow showers',

            95 => 'Thunderstorm',
            96, 99 => 'Thunderstorm with hail',

            default => 'Unknown',
        };
    }

    private function getIconType(
        int $code,
        bool $isDay
    ): string {
        $type = match ($code) {
            0, 1 => 'sunny',

            2, 3, 45, 48 => 'cloudy',

            51, 53, 55,
            56, 57,
            61, 63, 65,
            66, 67,
            80, 81, 82 => 'rain',

            71, 73, 75,
            77, 85, 86 => 'snow',

            95, 96, 99 => 'storm',

            default => 'cloudy',
        };

        if ($isDay) {
            return $type;
        }

        return $type . '-night';
    }

    private function getWeatherIcon(string $type): array
    {
        $icons = [
            /*
             * Daytime icons
             */
            'sunny' => [
                '    \ | /',
                '     .-.',
                ' -- (   ) --',
                "     `-'",
                '    / | \\',
            ],

            'cloudy' => [
                '',
                '      .--.',
                '   .-(    ).',
                '  (___.__)__)',
                '',
            ],

            'rain' => [
                '      .--.',
                '   .-(    ).',
                '  (___.__)__)',
                "    ' ' ' '",
                "   ' ' ' '",
            ],

            'snow' => [
                '      .--.',
                '   .-(    ).',
                '  (___.__)__)',
                '    *  *  *',
                '   *  *  *',
            ],

            'storm' => [
                '      .--.',
                '   .-(    ).',
                '  (___.__)__)',
                '      / /',
                '     /_/',
            ],

            /*
             * Nighttime icons
             */
            'sunny-night' => [
                '       _..._',
                '     .::::  `.',
                '    :::::     :',
                "    `::::.   .'",
                "      `-...-'",
            ],

            'cloudy-night' => [
                '       _..._',
                '     .::::  `.',
                '      .--.   :',
                "   .-(    ).'",
                '  (___.__)__)',
            ],

            'rain-night' => [
                '       _..._',
                '      .--.  `.',
                '   .-(    ).  :',
                '  (___.__)__)',
                "    ' ' ' '",
            ],

            'snow-night' => [
                '       _..._',
                '      .--.  `.',
                '   .-(    ).  :',
                '  (___.__)__)',
                '    *  *  *',
            ],

            'storm-night' => [
                '       _..._',
                '      .--.  `.',
                '   .-(    ).  :',
                '  (___.__)__/)',
                '      /_/',
            ],
        ];

        return $icons[strtolower($type)]
            ?? $icons['cloudy'];
    }
}
