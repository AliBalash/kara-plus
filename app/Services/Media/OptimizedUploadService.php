<?php

namespace App\Services\Media;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Spatie\Image\Enums\Fit;
use Spatie\Image\Image;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;
use Throwable;

class OptimizedUploadService
{
    private static array $optimizerAvailability = [];

    public function store(TemporaryUploadedFile|UploadedFile $file, string $path, string $disk = 'public', array $options = []): string
    {
        $format = $this->resolveFormat($options['format'] ?? 'webp');
        $quality = max(1, min(100, (int) ($options['quality'] ?? 25)));
        $maxWidth = max(320, (int) ($options['max_width'] ?? 1600));
        $maxHeight = max(320, (int) ($options['max_height'] ?? 1600));
        $optimize = (bool) ($options['optimize'] ?? true);

        $storage = Storage::disk($disk);
        $tempFile = $this->prepareTempFile($format);
        $path = $this->normalizePathExtension($path, $format);
        $shouldOptimize = $optimize && $this->externalOptimizersAvailable($format);

        try {
            $image = Image::useImageDriver($this->preferredDriver());

            $pipeline = $image->loadFile($file->getRealPath())
                ->orientation()
                ->fit(Fit::Max, $maxWidth, $maxHeight)
                ->quality($quality)
                ->format($format);

            $pipeline->save($tempFile);

            if ($shouldOptimize) {
                try {
                    ImageOptimizer::optimize($tempFile);
                } catch (Throwable $optimizerException) {
                    Log::warning('Image optimizer binary not available.', [
                        'message' => $optimizerException->getMessage(),
                    ]);
                }
            }

            $stream = fopen($tempFile, 'rb');
            $storage->put($path, $stream);
            fclose($stream);
        } catch (Throwable $exception) {
            Log::error('Image optimization failed, storing original file instead.', [
                'path' => $path,
                'exception' => $exception->getMessage(),
            ]);
            $fallbackExtension = $this->resolveOriginalExtension($file) ?? $format;
            $fallbackPath = $this->normalizePathExtension($path, $fallbackExtension);
            $file->storeAs(dirname($fallbackPath), basename($fallbackPath), $disk);
            $path = $fallbackPath;
        } finally {
            if (isset($tempFile) && file_exists($tempFile)) {
                @unlink($tempFile);
            }
        }

        return $path;
    }

    private function normalizePathExtension(string $path, string $format): string
    {
        $currentExtension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($currentExtension === $format) {
            return $path;
        }

        if ($currentExtension === '') {
            return rtrim($path, '.') . '.' . $format;
        }

        return preg_replace('/\.[^.]+$/', '.' . $format, $path);
    }

    private function resolveOriginalExtension(TemporaryUploadedFile|UploadedFile $file): ?string
    {
        $extension = $file->getClientOriginalExtension() ?: $file->extension();

        return $extension ? strtolower($extension) : null;
    }

    private function resolveFormat(string $requested): string
    {
        $format = strtolower($requested);

        return match ($format) {
            'jpeg' => 'jpg',
            'jpg', 'png', 'webp' => $format,
            default => 'webp',
        };
    }

    private function preferredDriver(): string
    {
        return extension_loaded('imagick') ? 'imagick' : 'gd';
    }

    private function externalOptimizersAvailable(string $format): bool
    {
        $normalized = $this->resolveFormat($format);

        if (array_key_exists($normalized, self::$optimizerAvailability)) {
            return self::$optimizerAvailability[$normalized];
        }

        if (! $this->canUseExternalOptimizers()) {
            self::$optimizerAvailability[$normalized] = false;
            return false;
        }

        $binaries = match ($normalized) {
            'webp' => ['cwebp'],
            'jpg' => ['jpegoptim', 'jpegtran'],
            'png' => ['pngquant', 'optipng'],
            default => [],
        };

        foreach ($binaries as $binary) {
            if ($this->binaryExists($binary)) {
                self::$optimizerAvailability[$normalized] = true;
                return true;
            }
        }

        self::$optimizerAvailability[$normalized] = false;
        return false;
    }

    private function canUseExternalOptimizers(): bool
    {
        $requiredFunctions = ['proc_open', 'exec', 'shell_exec'];
        $disabledFunctions = array_map('trim', explode(',', (string) ini_get('disable_functions')));

        foreach ($requiredFunctions as $function) {
            if (!function_exists($function) || in_array($function, $disabledFunctions, true)) {
                return false;
            }
        }

        return true;
    }

    private function binaryExists(string $binary): bool
    {
        $escaped = escapeshellarg($binary);
        $path = trim((string) @shell_exec("command -v {$escaped} 2>/dev/null"));

        return $path !== '' && is_executable($path);
    }

    private function prepareTempFile(string $format): string
    {
        $base = tempnam(sys_get_temp_dir(), 'opt-upload-');

        if ($base === false) {
            throw new \RuntimeException('Unable to create temporary file for image optimization.');
        }

        $tempFile = $base . '.' . $format;
        if (! rename($base, $tempFile)) {
            $tempFile = $base;
        }

        return $tempFile;
    }
}
