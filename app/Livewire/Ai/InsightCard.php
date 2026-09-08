<?php

namespace App\Livewire\Ai;

use App\AI\AiInsightService;
use App\Models\AiFeedback;
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

    public ?bool $feedbackHelpful = null;

    public ?int $insightId = null;

    public ?string $insightEntityType = null;

    public ?string $generatedAt = null;

    public ?string $expiresAt = null;

    public function mount(string $feature, ?int $entityId = null): void
    {
        abort_unless(auth()->check(), 403);
        abort_unless(array_key_exists($feature, config('ai.features', [])), 404);
        $this->feature = $feature;
        $this->entityId = $entityId;
    }

    public function load(): void
    {
        $this->applyResult(app(AiInsightService::class)->generate($this->feature, $this->entityId));
    }

    public function regenerate(): void
    {
        $this->applyResult(app(AiInsightService::class)->generate($this->feature, $this->entityId, true));
    }

    public function feedback(bool $helpful): void
    {
        if ($this->state !== 'ready' || $this->feedbackHelpful !== null) {
            return;
        }

        AiFeedback::create([
            'ai_insight_id' => $this->insightId,
            'user_id' => auth()->id(),
            'feature' => $this->feature,
            'entity_type' => $this->insightEntityType,
            'entity_id' => $this->entityId,
            'helpful' => $helpful,
        ]);

        $this->feedbackHelpful = $helpful;
    }

    private function applyResult(array $result): void
    {
        $this->state = $result['state'];
        $this->insight = $result['data'] ?? [];
        $this->facts = $result['facts'] ?? [];
        $this->meta = $result['meta'] ?? [];
        $this->cached = (bool) ($result['cached'] ?? false);
        $this->insightId = $result['insight_id'] ?? null;
        $this->insightEntityType = $result['entity_type'] ?? null;
        $this->generatedAt = $result['generated_at'] ?? null;
        $this->expiresAt = $result['expires_at'] ?? null;
        $this->feedbackHelpful = null;
    }

    public function render()
    {
        return view('livewire.ai.insight-card');
    }
}
