<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RecommendationController extends Controller
{
    /**
     * Display a listing of the user's recommendations.
     */
    public function index(Request $request)
    {
        $recommendations = $request->user()
            ->recommendations()
            ->with(['analysis.photo', 'recommendedProducts'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $formattedRecommendations = [];

        foreach ($recommendations as $recommendation) {
            $formattedRecommendations[] = $this->formatRecommendationResponse($recommendation);
        }

        return response()->json([
            'data' => $formattedRecommendations,
            'meta' => [
                'total' => $recommendations->total(),
                'per_page' => $recommendations->perPage(),
                'current_page' => $recommendations->currentPage(),
                'last_page' => $recommendations->lastPage()
            ]
        ]);
    }

    /**
     * Display the specified recommendation.
     */
    public function show(Request $request, $id)
    {
        $recommendation = $request->user()
            ->recommendations()
            ->with(['analysis.photo', 'recommendedProducts'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatRecommendationResponse($recommendation)
        ]);
    }

    /**
     * Get the latest recommendation for the user.
     */
    public function latest(Request $request)
    {
        $recommendation = $request->user()
            ->recommendations()
            ->with(['analysis.photo', 'recommendedProducts'])
            ->latest()
            ->first();

        if (!$recommendation) {
            return response()->json([
                'message' => 'No recommendations found'
            ], 404);
        }

        return response()->json([
            'data' => $this->formatRecommendationResponse($recommendation)
        ]);
    }

    /**
     * Compare two recommendations.
     */
    public function compare(Request $request)
    {
        $request->validate([
            'recommendation_ids' => 'required|array|min:2',
            'recommendation_ids.*' => 'required|exists:recommendations,id',
        ]);

        // Получаем ID рекомендаций из запроса (может быть больше двух)
        $recommendationIds = $request->recommendation_ids;

        // Получаем рекомендации пользователя
        $recommendations = $request->user()
            ->recommendations()
            ->with(['analysis.photo', 'recommendedProducts'])
            ->whereIn('id', $recommendationIds)
            ->get()
            ->keyBy('id');

        // Проверяем, что все запрошенные рекомендации найдены
        if (count($recommendations) < count($recommendationIds)) {
            return response()->json([
                'message' => 'One or more recommendations not found or do not belong to the user'
            ], 404);
        }

        // Сортируем рекомендации по дате (от старых к новым)
        $recommendations = $recommendations->sortBy('created_at');

        // Сравнение метрик
        $metricsComparison = [];
        $metricNames = [];

        // Собираем все уникальные названия метрик
        foreach ($recommendations as $recommendation) {
            $analysis = $recommendation->analysis;
            if ($analysis && isset($analysis->metrics) && is_array($analysis->metrics)) {
                foreach ($analysis->metrics as $metric) {
                    if (isset($metric['name'])) {
                        $metricNames[$metric['name']] = true;
                    }
                }
            }
        }

        // Составляем сравнение для каждой метрики
        foreach (array_keys($metricNames) as $metricName) {
            $metricValues = [];
            $firstValue = null;
            $lastValue = null;

            foreach ($recommendations as $recommendation) {
                $analysis = $recommendation->analysis;
                if ($analysis && isset($analysis->metrics) && is_array($analysis->metrics)) {
                    foreach ($analysis->metrics as $metric) {
                        if (isset($metric['name']) && $metric['name'] === $metricName && isset($metric['value'])) {
                            $value = floatval($metric['value']);
                            $metricValues[] = [
                                'recommendation_id' => $recommendation->id,
                                'date' => $recommendation->created_at->toDateString(),
                                'value' => $value
                            ];

                            if ($firstValue === null) {
                                $firstValue = $value;
                            }

                            $lastValue = $value;
                        }
                    }
                }
            }

            // Рассчитываем изменение в процентах
            $changePercentage = null;
            $trend = null;

            if ($firstValue !== null && $lastValue !== null && $firstValue != 0) {
                $changePercentage = round(($lastValue - $firstValue) / $firstValue * 100, 1);

                // Определяем тренд (improving, worsening, stable)
                if (abs($changePercentage) < 5) {
                    $trend = 'stable';
                } else {
                    // Для метрик, где уменьшение - это улучшение (например, жирность)
                    $isReductionPositive = in_array($metricName, ['Жирность', 'Oiliness']);

                    if (($changePercentage > 0 && !$isReductionPositive) ||
                        ($changePercentage < 0 && $isReductionPositive)) {
                        $trend = 'improving';
                    } else {
                        $trend = 'worsening';
                    }
                }
            }

            $metricsComparison[] = [
                'name' => $metricName,
                'values' => $metricValues,
                'change_percentage' => $changePercentage,
                'trend' => $trend
            ];
        }

        // Сравнение проблем с кожей
        $issuesComparison = [
            'resolved' => [],
            'improved' => [],
            'new' => [],
            'unchanged' => []
        ];

        // Получаем первую и последнюю рекомендации
        $firstRecommendation = $recommendations->first();
        $lastRecommendation = $recommendations->last();

        if ($firstRecommendation && $lastRecommendation) {
            $firstAnalysis = $firstRecommendation->analysis;
            $lastAnalysis = $lastRecommendation->analysis;

            if ($firstAnalysis && $lastAnalysis) {
                $firstIssues = $firstAnalysis->skin_issues ?? [];
                $lastIssues = $lastAnalysis->skin_issues ?? [];

                // Находим решенные проблемы (были в первом анализе, но нет в последнем)
                $issuesComparison['resolved'] = array_values(array_diff($firstIssues, $lastIssues));

                // Находим новые проблемы (не было в первом анализе, но есть в последнем)
                $issuesComparison['new'] = array_values(array_diff($lastIssues, $firstIssues));

                // Находим неизменные проблемы (есть и в первом, и в последнем)
                $issuesComparison['unchanged'] = array_values(array_intersect($firstIssues, $lastIssues));

                // Для "улучшенных" проблем нужны промежуточные данные - это упрощение
                // В реальном приложении здесь можно добавить более сложную логику
                if (count($recommendations) > 2) {
                    // Если есть промежуточные рекомендации, можно проверить тренд
                    $issuesComparison['improved'] = [];
                }
            }
        }

        return response()->json([
            'data' => [
                'metrics_comparison' => $metricsComparison,
                'issues_comparison' => $issuesComparison
            ]
        ]);
    }

    /**
     * Форматирование ответа рекомендации в требуемый формат.
     */
    private function formatRecommendationResponse(Recommendation $recommendation)
    {
        $recommendedProducts = [];

        if ($recommendation->recommendedProducts) {
            foreach ($recommendation->recommendedProducts as $product) {
                $recommendedProducts[] = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'brand' => $product->brand,
                    'image_url' => $product->image_url,
                    'recommendation_reason' => $product->pivot->recommendation_reason
                ];
            }
        }

        return [
            'id' => $recommendation->id,
            'user_id' => $recommendation->user_id,
            'analysis_id' => $recommendation->analysis_id,
            'date' => $recommendation->date,
            'recommendations' => $recommendation->recommendations,
            'recommended_products' => $recommendedProducts
        ];
    }
}
