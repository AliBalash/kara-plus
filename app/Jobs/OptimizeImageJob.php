<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Throwable;

class OptimizeImageJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public string $disk;
    public string $path;
    public array $options;

    public function __construct(string $disk, string $path, array $options = [])
    {
        $this->disk = $disk;
        $this->path = $path;
        $this->options = $options;
    }

    public function handle(): void
    {
        $disk = Storage::disk($this->disk);

        if (! $disk->exists($this->path)) {
            return;
        }

        $extension = strtolower(pathinfo($this->path, PATHINFO_EXTENSION));
        $extension = $extension === 'jpeg' ? 'jpg' : $extension;
        $supported = ['jpg', 'png', 'webp'];

        if ($extension === '' || ! in_array($extension, $supported, true)) {
            return;
        }

        $quality = max(1, min(100, (int) ($this->options['quality'] ?? 25)));
        $maxWidth = max(320, (int) ($this->options['max_width'] ?? 1600));
        $maxHeight = max(320, (int) ($this->options['max_height'] ?? 1600));
        $optimize = (bool) ($this->options['optimize'] ?? true);

        $tempInput = null;
        $tempOutput = null;

        try {
            $tempInput = $this->createTempFile('img-in-', $extension);
            $tempOutput = $this->createTempFile('img-out-', $extension);

            $readStream = $disk->readStream($this->path);
            if ($readStream === false) {
                return;
            }

            $inputHandle = fopen($tempInput, 'wb');
            if ($inputHandle === false) {
                return;
            }

            stream_copy_to_stream($readStream, $inputHandle);
            fclose($readStream);
            fclose($inputHandle);

            $driver = extension_loaded('imagick') ? 'imagick' : 'gd';
            Image::useImageDriver($driver)
                ->loadFile($tempInput)
                ->orientation()
                ->fit(Fit::Max, $maxWidth, $maxHeight)
                ->quality($quality)
                ->format($extension)
                ->save($tempOutput);

            if ($optimize) {
                try {
                    ImageOptimizer::optimize($tempOutput);
                } catch (Throwable $optimizerException) {
                    Log::warning('Image optimizer binary not available.', [
                        'message' => $optimizerException->getMessage(),
                        'path' => $this->path,
                    ]);
                }
            }

            $writeStream = fopen($tempOutput, 'rb');
            if ($writeStream === false) {
                return;
            }

            $disk->put($this->path, $writeStream);
            fclose($writeStream);
        } catch (Throwable $exception) {
            Log::error('Deferred image optimization failed.', [
                'path' => $this->path,
                'exception' => $exception->getMessage(),
            ]);
        } finally {
            if ($tempInput && file_exists($tempInput)) {
                @unlink($tempInput);
            }

            if ($tempOutput && file_exists($tempOutput)) {
                @unlink($tempOutput);
            }
        }
    }

    private function createTempFile(string $prefix, string $extension): string
    {
        $base = tempnam(sys_get_temp_dir(), $prefix);

        if ($base === false) {
            throw new \RuntimeException('Unable to create temporary file for image optimization.');
        }

        $tempFile = $base . '.' . $extension;

        if (! rename($base, $tempFile)) {
            $tempFile = $base;
        }

        return $tempFile;
    }
}
