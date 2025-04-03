<?php

namespace App\Jobs;

use App\Models\AnalysisRequest;
use App\Services\SkinAnalysisService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessSkinAnalysis implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 1;

    /**
     * The analysis request instance.
     *
     * @var AnalysisRequest
     */
    protected AnalysisRequest $analysisRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(AnalysisRequest $analysisRequest)
    {
        $this->analysisRequest = $analysisRequest;
    }

    /**
     * Execute the job.
     */
    public function handle(SkinAnalysisService $skinAnalysisService): void
    {
        try {
            // Проверяем, что запрос еще находится в статусе pending
            if (!$this->analysisRequest->isPending()) {
                return;
            }

            // Обновляем статус на "processing"
            $this->analysisRequest->markAsProcessing();

            // Получаем фотографию из запроса
            $photo = $this->analysisRequest->photo;

            if (!$photo) {
                throw new \Exception("Photo not found");
            }

            // Имитируем задержку обработки (в реальном приложении здесь будет обращение к ML-сервису)
            sleep(rand(5, 15)); // Случайная задержка от 5 до 15 секунд

            // Выполняем анализ фотографии
            $analysis = $skinAnalysisService->analyzePhoto($photo);

            // Обновляем статус запроса на "completed" и связываем с результатом анализа
            $this->analysisRequest->markAsCompleted($analysis->id);
        } catch (\Exception $e) {
            // В случае ошибки отмечаем запрос как "failed"
            Log::error('Skin analysis failed: ' . $e->getMessage());
            $this->analysisRequest->markAsFailed($e->getMessage());
        }
    }
}
