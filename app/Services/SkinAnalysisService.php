<?php

namespace App\Services;

use App\Models\SkinAnalysis;
use App\Models\SkinPhoto;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SkinAnalysisService
{
    /**
     * Analyze a skin photo and create an analysis record.
     *
     * @param SkinPhoto $photo
     * @return SkinAnalysis
     */
    public function analyzePhoto(SkinPhoto $photo): SkinAnalysis
    {
        try {
            // В реальном приложении здесь был бы запрос к ML-сервису
            // Например, Google Cloud Vision API или специализированный API для анализа кожи

            // Для тестирования создаем псевдо-анализ
            $mockAnalysis = $this->mockAnalysis($photo);

            // Создаем запись анализа
            $analysis = SkinAnalysis::create([
                'photo_id' => $photo->id,
                'skin_condition' => $mockAnalysis['skin_condition'],
                'skin_issues' => $mockAnalysis['skin_issues'],
                'metrics' => $mockAnalysis['metrics'],
            ]);

            return $analysis;
        } catch (\Exception $e) {
            Log::error('Failed to analyze skin photo: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Create a mock analysis for development purposes.
     *
     * @param SkinPhoto $photo
     * @return array
     */
    private function mockAnalysis(SkinPhoto $photo): array
    {
        // Имитируем зависимость результатов анализа от метаданных фото
        $metadata = $photo->metadata ?? [];
        $skinType = $metadata['skin_type'] ?? null;
        $skinConcerns = $metadata['skin_concerns'] ?? [];

        // Генерируем реалистичные данные анализа кожи
        $hydrationLevel = rand(40, 95);
        $oilLevel = rand(30, 90);
        $sensitivityLevel = rand(20, 80);

        // Подстраиваем уровни под указанный тип кожи
        if ($skinType == 'Сухая') {
            $hydrationLevel = rand(30, 60);
            $oilLevel = rand(20, 40);
        } elseif ($skinType == 'Жирная') {
            $hydrationLevel = rand(60, 90);
            $oilLevel = rand(70, 95);
        } elseif ($skinType == 'Комбинированная') {
            $hydrationLevel = rand(50, 75);
            $oilLevel = rand(50, 80);
        }

        // Создаем список проблем с кожей
        $possibleIssues = [
            'Сухость',
            'Жирность',
            'Акне',
            'Покраснение',
            'Пигментация',
            'Морщины',
            'Черные точки',
            'Расширенные поры',
            'Неровная текстура',
            'Темные круги'
        ];

        // Добавляем указанные проблемы кожи
        $issues = [];
        foreach ($skinConcerns as $concern) {
            if (in_array($concern, $possibleIssues)) {
                $issues[] = $concern;
            }
        }

        // Случайно добавляем еще несколько проблем
        $numRandomIssues = min(max(0, 3 - count($issues)), 2); // Максимум 2 случайные проблемы
        $remainingIssues = array_diff($possibleIssues, $issues);

        if ($numRandomIssues > 0 && count($remainingIssues) > 0) {
            $randomKeys = array_rand($remainingIssues, min($numRandomIssues, count($remainingIssues)));
            if (!is_array($randomKeys)) {
                $randomKeys = [$randomKeys];
            }

            foreach ($randomKeys as $key) {
                $issues[] = $remainingIssues[$key];
            }
        }

        // Создаем метрики для визуализации
        $metrics = [
            [
                'name' => 'Увлажненность',
                'value' => $hydrationLevel,
                'max_value' => 100.0,
                'unit' => '%'
            ],
            [
                'name' => 'Жирность',
                'value' => $oilLevel,
                'max_value' => 100.0,
                'unit' => '%'
            ],
            [
                'name' => 'Чувствительность',
                'value' => $sensitivityLevel,
                'max_value' => 100.0,
                'unit' => '%'
            ]
        ];

        // Создаем полный результат анализа
        return [
            'skin_condition' => [
                'hydration' => $hydrationLevel,
                'oil' => $oilLevel,
                'sensitivity' => $sensitivityLevel,
                'analysis_confidence' => rand(75, 98) / 100,
            ],
            'skin_issues' => $issues,
            'metrics' => $metrics,
        ];
    }

    /**
     * В будущем интеграция с реальным ML-сервисом для анализа кожи.
     * Это заглушка для такой интеграции.
     *
     * @param string $imagePath
     * @return array
     */
    private function analyzeWithAI($imagePath)
    {
        // Здесь была бы ваша интеграция с ML-сервисом, например:

        /*
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.ai_api.key'),
            ])->attach(
                'image', file_get_contents($imagePath), 'image.jpg'
            )->post('https://api.example.com/skin-analysis');

            return $response->json();
        } catch (\Exception $e) {
            Log::error('AI analysis failed: ' . $e->getMessage());
            return $this->mockAnalysis(null);
        }
        */

        // Возвращаем тестовые данные
        return $this->mockAnalysis(null);
    }
}
