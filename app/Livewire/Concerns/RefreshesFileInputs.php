<?php

namespace App\Livewire\Concerns;

trait RefreshesFileInputs
{
    public int $fileInputVersion = 0;

    protected function refreshFileInputs(): void
    {
        $this->fileInputVersion++;
    }
}
