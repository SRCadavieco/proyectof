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
            // Polling a Pixazo para obtener el estado de la imagen
            $response = Http::withHeaders([
                'Ocp-Apim-Subscription-Key' => config('services.pixazo.key'),
                'Content-Type' => 'application/json',
            ])->post(
                'https://gateway.pixazo.ai/nano-banana-polling/nano-banana/getStatus',
                [
                    'requestId' => $this->taskId
                ]
            );

            $data = $response->json();

            if (!empty($data['images'][0]['url'])) {
                // Guardar imagen generada en la base de datos
                \App\Models\DesignGeneration::create([
                    'prompt' => $this->prompt,
                    'image_url' => $data['images'][0]['url'],
                    'task_id' => $this->taskId,
                    'error' => null
                ]);
                // Emitir evento cuando la imagen esté lista
                event(new DesignGenerated($this->taskId, $data['images'][0]['url'], null));
            } else {
                // Reintentar en 10 segundos si la imagen no está lista
                $this->release(10);
            }
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