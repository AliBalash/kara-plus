<?php

namespace App\Observers;

use App\Models\AuditEvent;
use App\Services\Audit\AuditLogger;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\File;

class AuditModelObserver
{
    public function created(Model $model): void
    {
        if (! $this->shouldCapture($model)) {
            return;
        }

        app(AuditLogger::class)->logModel(
            'model_created',
            $model,
            null,
            $model->getAttributes(),
            array_keys($model->getAttributes())
        );
    }

    public function updated(Model $model): void
    {
        if (! $this->shouldCapture($model)) {
            return;
        }

        $changes = $model->getChanges();
        unset($changes['updated_at']);

        if ($changes === []) {
            return;
        }

        $before = [];
        $after = [];
        foreach (array_keys($changes) as $field) {
            $before[$field] = $model->getOriginal($field);
            $after[$field] = $model->getAttribute($field);
        }

        app(AuditLogger::class)->logModel(
            'model_updated',
            $model,
            $before,
            $after,
            array_keys($changes)
        );
    }

    public function deleted(Model $model): void
    {
        if (! $this->shouldCapture($model)) {
            return;
        }

        app(AuditLogger::class)->logModel(
            'model_deleted',
            $model,
            $model->getOriginal(),
            null,
            array_keys($model->getOriginal())
        );
    }

    public static function registerForAllModels(): void
    {
        foreach (File::files(app_path('Models')) as $file) {
            $class = 'App\\Models\\' . $file->getFilenameWithoutExtension();

            if (! class_exists($class)) {
                continue;
            }

            if ($class === AuditEvent::class) {
                continue;
            }

            if (! is_subclass_of($class, Model::class)) {
                continue;
            }

            $class::observe(self::class);
        }
    }

    private function shouldCapture(Model $model): bool
    {
        if (! (bool) config('audit.capture.model_events', true)) {
            return false;
        }

        if ($model instanceof AuditEvent) {
            return false;
        }

        if ($model->getTable() === 'jobs' || $model->getTable() === 'failed_jobs') {
            return false;
        }

        return true;
    }
}
