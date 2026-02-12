<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Events\DesignGenerated;

class GenerateDesignJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $prompt;
    protected $taskId;

    /**
     * Create a new job instance.
     *
     * @param string $prompt
     * @param string $taskId
     */
    public function __construct(string $prompt, string $taskId)
    {
        $this->prompt = $prompt;
        $this->taskId = $taskId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Llamar a la API de NanoBanana con timeout aumentado
            $response = Http::timeout(120)->post('https://api.nanobanana.com/generate', [
                'prompt' => $this->prompt
            ]);

            if ($response->failed()) {
                // Guardar error en la base de datos
                \App\Models\DesignGeneration::create([
                    'prompt' => $this->prompt,
                    'image_url' => null,
                    'task_id' => $this->taskId,
                    'error' => 'NanoBanana timeout…'
                ]);
                // Emitir evento de error
                event(new DesignGenerated($this->taskId, null, 'NanoBanana timeout…'));
                throw new \Exception('Error al generar el diseño');
            }

            $data = $response->json();

            // Guardar diseño generado en la base de datos
            \App\Models\DesignGeneration::create([
                'prompt' => $this->prompt,
                'image_url' => $data['image_url'] ?? null,
                'task_id' => $this->taskId,
                'error' => null
            ]);

            // Emitir evento cuando la imagen esté lista
            event(new DesignGenerated($this->taskId, $data['image_url'], null));
        } catch (\Exception $e) {
            Log::error('Error en GenerateDesignJob: ' . $e->getMessage());
            // Guardar error en la base de datos
            \App\Models\DesignGeneration::create([
                'prompt' => $this->prompt,
                'image_url' => null,
                'task_id' => $this->taskId,
                'error' => $e->getMessage()
            ]);
            // Emitir evento de error si ocurre excepción
            event(new DesignGenerated($this->taskId, null, $e->getMessage()));
        }
    }
}