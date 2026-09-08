<?php

namespace App\Livewire\Ai;

use App\AI\AiInsightService;
use Livewire\Component;

class InsightCard extends Component
{
    public string $feature;
    public ?int $entityId = null;
    public string $state = 'idle';
    public array $insight = [];
    public array $facts = [];
    public array $meta = [];
    public bool $cached = false;

    public function mount(string $feature, ?int $entityId = null): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(array_key_exists($feature, config('ai.features', [])), 404);
        $this->feature = $feature;
        $this->entityId = $entityId;
    }

    public function load(): void
    {
        $this->state = 'loading';
        $result = app(AiInsightService::class)->generate($this->feature, $this->entityId);
        $this->state = $result['state']; $this->insight = $result['data'] ?? []; $this->facts = $result['facts'] ?? []; $this->meta = $result['meta'] ?? []; $this->cached = (bool) ($result['cached'] ?? false);
    }

    public function render() { return view('livewire.ai.insight-card'); }
}
