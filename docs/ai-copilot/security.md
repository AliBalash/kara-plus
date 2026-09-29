# Security and reliability

No AI endpoint writes a business record. Prepared context excludes direct customer identifiers and notes. Raw prompts, provider keys, and responses are not logged. `ai_runs` records safe metadata only; `ai_insights` is an expiring cache, not operational truth.

Ajil uses its supported fallback chain for 429 and temporary provider failures. Laravel uses bounded timeouts and shows the deterministic fact list when model analysis is unavailable. It does not rotate provider keys or retry provider calls itself. Livewire locks the feature, entity and referenced insight identifiers against client-side mutation.
