<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessSkinAnalysis;
use App\Models\AnalysisRequest;
use App\Models\SkinAnalysis;
use App\Models\SkinPhoto;
use App\Models\Recommendation;
use App\Services\SkinAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SkinAnalysisController extends Controller
{
    protected SkinAnalysisService $skinAnalysisService;

    /**
     * Create a new controller instance.
     */
    public function __construct(SkinAnalysisService $skinAnalysisService)
    {
        $this->skinAnalysisService = $skinAnalysisService;
    }

    /**
     * Get all analyses for the authenticated user.
     */
    public function index(Request $request)
    {
        $analyses = $request->user()
            ->skinAnalyses()
            ->with(['photo', 'recommendation.recommendedProducts'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $formattedAnalyses = [];

        foreach ($analyses as $analysis) {
            $formattedAnalyses[] = $this->formatAnalysisResponse($analysis);
        }

        return response()->json([
            'data' => $formattedAnalyses,
            'meta' => [
                'total' => $analyses->total(),
                'per_page' => $analyses->perPage(),
                'current_page' => $analyses->currentPage(),
                'last_page' => $analyses->lastPage()
            ]
        ]);
    }

    /**
     * Запрос на асинхронный анализ фотографии.
     */
    public function requestAnalysis(Request $request, $photoId)
    {
        // Валидация данных
        $request->validate([
            'skin_type' => 'nullable|string|max:50',
            'skin_concerns' => 'nullable|array',
            'skin_concerns.*' => 'string|max:50',
        ]);

        // Найти фотографию или вернуть 404
        $photo = $request->user()->skinPhotos()->findOrFail($photoId);

        // Проверить, существует ли уже анализ
        if ($photo->skinAnalysis) {
            return response()->json([
                'message' => 'Photo already analyzed',
                'data' => [
                    'request_id' => null,
                    'status' => 'completed',
                    'analysis_id' => $photo->skinAnalysis->id
                ]
            ]);
        }

        // Обновить метаданные фотографии, если они предоставлены
        if ($request->has('skin_type') || $request->has('skin_concerns')) {
            $metadata = $photo->metadata ?? [];

            if ($request->has('skin_type')) {
                $metadata['skin_type'] = $request->skin_type;
            }

            if ($request->has('skin_concerns')) {
                $metadata['skin_concerns'] = $request->skin_concerns;
            }

            $photo->metadata = $metadata;
            $photo->save();
        }

        // Проверяем, есть ли уже запрос на анализ этой фотографии
        $existingRequest = AnalysisRequest::where('photo_id', $photo->id)
            ->where('user_id', $request->user()->id)
            ->whereIn('status', ['pending', 'processing'])
            ->first();

        if ($existingRequest) {
            // Если запрос уже существует, возвращаем его ID
            return response()->json([
                'message' => 'Analysis request already exists',
                'data' => [
                    'request_id' => $existingRequest->id,
                    'status' => $existingRequest->status
                ]
            ]);
        }

        // Создаем новый запрос на анализ
        $analysisRequest = AnalysisRequest::create([
            'user_id' => $request->user()->id,
            'photo_id' => $photo->id,
            'status' => 'pending',
            'additional_data' => [
                'skin_type' => $photo->metadata['skin_type'] ?? null,
                'skin_concerns' => $photo->metadata['skin_concerns'] ?? []
            ]
        ]);

        // Запускаем обработку в фоновом режиме
        ProcessSkinAnalysis::dispatch($analysisRequest);

        return response()->json([
            'message' => 'Analysis request has been created',
            'data' => [
                'request_id' => $analysisRequest->id,
                'status' => 'pending'
            ]
        ]);
    }

    /**
     * Проверка статуса запроса на анализ.
     */
    public function checkAnalysisStatus(Request $request, $requestId)
    {
        // Найти запрос или вернуть 404
        $analysisRequest = AnalysisRequest::where('user_id', $request->user()->id)
            ->findOrFail($requestId);

        $responseData = [
            'status' => $analysisRequest->status
        ];

        // Если анализ завершен, включаем его данные
        if ($analysisRequest->isCompleted() && $analysisRequest->analysis) {
            $responseData['data'] = $this->formatAnalysisResponse($analysisRequest->analysis->load('recommendation.recommendedProducts'));
        }

        // Если анализ завершился с ошибкой, включаем сообщение об ошибке
        if ($analysisRequest->isFailed()) {
            $responseData['error'] = $analysisRequest->error_message;
        }

        return response()->json($responseData);
    }

    /**
     * Show a specific analysis.
     */
    public function show(Request $request, $id)
    {
        $analysis = $request->user()
            ->skinAnalyses()
            ->with(['photo', 'recommendation.recommendedProducts'])
            ->findOrFail($id);

        return response()->json([
            'data' => $this->formatAnalysisResponse($analysis)
        ]);
    }

    /**
     * Get analysis history/timeline for visualization.
     */
    public function timeline(Request $request)
    {
        $analyses = $request->user()
            ->skinAnalyses()
            ->with('photo')
            ->orderBy('created_at', 'desc')
            ->get();

        // Группировка по месяцам
        $timeline = [];
        $analysesGrouped = $analyses->groupBy(function ($analysis) {
            return $analysis->created_at->format('Y-m');
        });

        foreach ($analysesGrouped as $yearMonth => $monthAnalyses) {
            // Создание метрик для каждого месяца
            $metrics = [];

            // Собираем все возможные метрики из анализов
            $metricNames = collect();
            foreach ($monthAnalyses as $analysis) {
                if (isset($analysis->metrics) && is_array($analysis->metrics)) {
                    foreach ($analysis->metrics as $metric) {
                        $metricNames->push($metric['name']);
                    }
                }
            }
            $metricNames = $metricNames->unique()->values();

            // Для каждой метрики собираем значения по датам
            foreach ($metricNames as $metricName) {
                $values = [];

                foreach ($monthAnalyses as $analysis) {
                    if (isset($analysis->metrics) && is_array($analysis->metrics)) {
                        foreach ($analysis->metrics as $metric) {
                            if ($metric['name'] === $metricName) {
                                $values[] = [
                                    'date' => $analysis->created_at->toDateString(),
                                    'value' => $metric['value']
                                ];
                                break;
                            }
                        }
                    }
                }

                $metrics[] = [
                    'name' => $metricName,
                    'values' => $values
                ];
            }

            // Формат месяца на русском языке
            $dateParts = explode('-', $yearMonth);
            $year = $dateParts[0];
            $month = $dateParts[1];

            $months = [
                '01' => 'Январь', '02' => 'Февраль', '03' => 'Март',
                '04' => 'Апрель', '05' => 'Май', '06' => 'Июнь',
                '07' => 'Июль', '08' => 'Август', '09' => 'Сентябрь',
                '10' => 'Октябрь', '11' => 'Ноябрь', '12' => 'Декабрь'
            ];

            $monthName = $months[$month] ?? $month;
            $displayName = "$monthName $year";

            $timeline[] = [
                'month' => $displayName,
                'metrics' => $metrics
            ];
        }

        return response()->json([
            'data' => $timeline
        ]);
    }

    /**
     * Форматирование ответа анализа в требуемый формат.
     */
    private function formatAnalysisResponse(SkinAnalysis $analysis)
    {
        $recommendation = $analysis->recommendation;
        $recommendedProducts = [];

        if ($recommendation && $recommendation->recommendedProducts) {
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
            'id' => $analysis->id,
            'user_id' => $analysis->photo->user_id,
            'photo_id' => $analysis->photo_id,
            'image_url' => $analysis->image_url,
            'thumbnail_url' => $analysis->thumbnail_url,
            'analysis_date' => $analysis->analysis_date,
            'skin_condition' => $analysis->skin_condition,
            'skin_issues' => $analysis->skin_issues ?? [],
            'recommendations' => $recommendation ? $recommendation->recommendations : [],
            'metrics' => $analysis->metrics ?? [],
            'recommended_products' => $recommendedProducts
        ];
    }
}
