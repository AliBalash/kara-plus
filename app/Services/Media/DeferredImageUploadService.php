<?php

namespace App\Services\Media;

use App\Jobs\OptimizeImageJob;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class DeferredImageUploadService
{
    private array $extensions = ['webp', 'jpg', 'jpeg', 'png'];

    public function store(TemporaryUploadedFile|UploadedFile $file, string $path, string $disk = 'public', array $options = []): string
    {
        $extension = $this->resolveOriginalExtension($file) ?? 'jpg';
        $extension = $this->normalizeExtension($extension);
        $targetPath = $this->normalizePathExtension($path, $extension);

        if (($options['cleanup_variants'] ?? true) === true) {
            $this->deleteExistingVariants($disk, $targetPath);
        }

        $directory = str_replace('\\', '/', dirname($targetPath));
        if ($directory === '.' || $directory === '/') {
            $directory = '';
        }

        $filename = basename($targetPath);
        $storedPath = $file->storeAs($directory, $filename, $disk);

        $this->dispatchOptimization($disk, $storedPath, $options);

        return $storedPath;
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

    private function normalizeExtension(string $extension): string
    {
        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    private function deleteExistingVariants(string $disk, string $path): void
    {
        $storage = Storage::disk($disk);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $basePath = $extension === '' ? $path : substr($path, 0, -strlen($extension) - 1);

        foreach ($this->extensions as $variant) {
            $candidate = $basePath . '.' . $variant;
            if ($storage->exists($candidate)) {
                $storage->delete($candidate);
            }
        }
    }

    private function dispatchOptimization(string $disk, string $path, array $options): void
    {
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $extension = $this->normalizeExtension($extension);

        if ($extension === '' || ! in_array($extension, $this->extensions, true)) {
            return;
        }

        $queue = $options['queue'] ?? null;

        $pending = OptimizeImageJob::dispatch($disk, $path, $options)
            ->afterCommit();

        if ($queue) {
            $pending->onQueue($queue);
        }

        $pending->afterResponse();
    }
}
