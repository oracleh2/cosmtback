<?php

namespace App\Console\Commands;

use App\Models\AnalysisRequest;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Symfony\Component\Console\Command\Command as CommandAlias;

class CleanOldAnalysisRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analysis-requests:clean {--days=7 : Number of days to keep}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old analysis requests';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = $this->option('days');
        $cutoffDate = Carbon::now()->subDays($days);

        $this->info("Cleaning analysis requests older than {$days} days...");

        // Удаляем запросы, которые уже завершены (успешно или с ошибкой)
        $count = AnalysisRequest::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['completed', 'failed'])
            ->delete();

        $this->info("Deleted {$count} old analysis requests.");

        // Обрабатываем "зависшие" запросы (остались в статусе pending или processing)
        $pendingRequests = AnalysisRequest::where('created_at', '<', $cutoffDate)
            ->whereIn('status', ['pending', 'processing'])
            ->get();

        foreach ($pendingRequests as $request) {
            $request->markAsFailed('Request timed out after ' . $days . ' days');
            $this->line("Marked request #{$request->id} as failed due to timeout.");
        }

        $this->info("Processed " . $pendingRequests->count() . " stuck requests.");

        return CommandAlias::SUCCESS;
    }
}
