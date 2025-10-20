@props([
    'success' => session('message'),
    'info' => session('info'),
    'error' => session('error'),
    'warning' => session('warning'),
    'status' => session('status'),
    'validationErrors' => $errors ?? null,
])

@php
    $variants = [
        'success' => [
            'title' => 'Success',
            'icon' => 'bi-check-circle-fill',
            'class' => 'text-bg-success kara-toast-success',
            'delay' => 4000,
            'autohide' => true,
            'close_class' => 'btn-close btn-close-white',
        ],
        'info' => [
            'title' => 'Information',
            'icon' => 'bi-info-circle-fill',
            'class' => 'text-bg-info kara-toast-info',
            'delay' => 5000,
            'autohide' => true,
            'close_class' => 'btn-close btn-close-white',
        ],
        'warning' => [
            'title' => 'Warning',
            'icon' => 'bi-exclamation-triangle-fill',
            'class' => 'text-bg-warning kara-toast-warning',
            'delay' => 6000,
            'autohide' => true,
            'close_class' => 'btn-close',
        ],
        'error' => [
            'title' => 'Error',
            'icon' => 'bi-x-circle-fill',
            'class' => 'text-bg-danger kara-toast-error',
            'delay' => 8000,
            'autohide' => false,
            'close_class' => 'btn-close btn-close-white',
        ],
        'status' => [
            'title' => 'Status',
            'icon' => 'bi-bell-fill',
            'class' => 'text-bg-secondary kara-toast-status',
            'delay' => 5000,
            'autohide' => true,
            'close_class' => 'btn-close btn-close-white',
        ],
        'validation' => [
            'title' => 'Validation Error',
            'icon' => 'bi-exclamation-octagon-fill',
            'class' => 'text-bg-danger kara-toast-error',
            'delay' => 8000,
            'autohide' => false,
            'close_class' => 'btn-close btn-close-white',
        ],
    ];

    $normalizedErrors = [];

    if ($validationErrors instanceof \Illuminate\Support\MessageBag) {
        $normalizedErrors = $validationErrors->all();
    } elseif ($validationErrors instanceof \Illuminate\Contracts\Support\MessageBag) {
        $normalizedErrors = $validationErrors->all();
    } elseif (is_array($validationErrors)) {
        $normalizedErrors = $validationErrors;
    }

    $normalizedErrors = array_values(array_filter($normalizedErrors, fn ($message) => ! empty($message)));

    $toasts = [];

    $sessionMessages = [
        'success' => $success,
        'info' => $info,
        'warning' => $warning,
        'error' => $error,
        'status' => $status,
    ];

    foreach ($sessionMessages as $type => $message) {
        if (empty($message)) {
            continue;
        }

        $config = $variants[$type] ?? $variants['info'];

        $toasts[] = [
            'id' => uniqid('toast-'),
            'title' => $config['title'],
            'message' => $message,
            'icon' => $config['icon'],
            'class' => $config['class'],
            'delay' => $config['delay'],
            'autohide' => $config['autohide'],
            'close_class' => $config['close_class'] ?? 'btn-close',
        ];
    }

    if (! empty($normalizedErrors)) {
        $firstError = $normalizedErrors[0];
        $config = $variants['validation'];

        $toasts[] = [
            'id' => uniqid('toast-'),
            'title' => $config['title'],
            'message' => $firstError,
            'icon' => $config['icon'],
            'class' => $config['class'],
            'delay' => $config['delay'],
            'autohide' => $config['autohide'],
            'close_class' => $config['close_class'] ?? 'btn-close',
        ];
    }
@endphp

@if (! empty($toasts))
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;" data-kara-toast-container>
        @foreach ($toasts as $toast)
            <div class="toast align-items-stretch border-0 shadow-lg overflow-hidden {{ $toast['class'] }}"
                role="alert" aria-live="assertive" aria-atomic="true"
                data-bs-delay="{{ $toast['delay'] }}" data-bs-autohide="{{ $toast['autohide'] ? 'true' : 'false' }}"
                data-kara-toast-ready="true" id="{{ $toast['id'] }}" wire:key="{{ $toast['id'] }}">
                <div class="toast-body p-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="flex-shrink-0 display-6 lh-1"><i class="bi {{ $toast['icon'] }}"></i></span>
                        <div class="flex-grow-1">
                            <p class="fw-semibold mb-1">{{ $toast['title'] }}</p>
                            <p class="mb-0 small">{{ $toast['message'] }}</p>
                        </div>
                        <button type="button" class="{{ $toast['close_class'] }} ms-2" data-bs-dismiss="toast"
                            aria-label="Close"></button>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif

@once
    @push('styles')
        <style>
            .toast-container {
                width: 360px;
            }

            .toast-container .toast {
                min-width: 320px;
                border-radius: 1rem;
                backdrop-filter: blur(10px);
                border: 1px solid rgba(255, 255, 255, 0.08);
                box-shadow: 0 16px 40px rgba(15, 23, 42, 0.18);
                overflow: hidden;
                position: relative;
            }

            .toast-container .toast::after {
                content: '';
                position: absolute;
                inset: 0;
                background: linear-gradient(120deg, rgba(255, 255, 255, 0.22), transparent 65%);
                opacity: 0.85;
                pointer-events: none;
            }

            .toast-container .toast .toast-body {
                position: relative;
                z-index: 2;
            }

            .toast-container .toast .display-6 {
                font-size: 1.75rem;
                border-radius: 0.85rem;
                padding: 0.45rem 0.6rem;
                box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
                background: rgba(255, 255, 255, 0.25);
            }

            .toast-container .toast .fw-semibold {
                font-size: 0.95rem;
                text-transform: uppercase;
                letter-spacing: 0.08em;
            }

            .toast-container .toast .mb-0.small {
                font-size: 0.85rem;
            }

            .toast-container .toast.kara-toast-success {
                background: linear-gradient(135deg, #20bf6b, #01baef);
                color: #fff;
            }

            .toast-container .toast.kara-toast-success .display-6,
            .toast-container .toast.kara-toast-success .fw-semibold,
            .toast-container .toast.kara-toast-success .mb-0.small {
                color: #fff;
            }

            .toast-container .toast.kara-toast-info {
                background: linear-gradient(135deg, #5c6cff, #4fd1c5);
                color: #f8fafc;
            }

            .toast-container .toast.kara-toast-info .display-6,
            .toast-container .toast.kara-toast-info .fw-semibold,
            .toast-container .toast.kara-toast-info .mb-0.small {
                color: #f8fafc;
            }

            .toast-container .toast.kara-toast-warning {
                background: linear-gradient(135deg, #ff9f1c, #ff5714);
                color: #2f1100;
            }

            .toast-container .toast.kara-toast-warning .display-6 {
                background: rgba(255, 255, 255, 0.35);
                color: #2f1100;
            }

            .toast-container .toast.kara-toast-warning .fw-semibold {
                color: #2f1100;
            }

            .toast-container .toast.kara-toast-warning .mb-0.small {
                color: rgba(47, 17, 0, 0.85);
            }

            .toast-container .toast.kara-toast-error {
                background: linear-gradient(135deg, #ff4d6d, #c9184a);
                color: #fff;
            }

            .toast-container .toast.kara-toast-error .display-6,
            .toast-container .toast.kara-toast-error .fw-semibold,
            .toast-container .toast.kara-toast-error .mb-0.small {
                color: #fff;
            }

            .toast-container .toast.kara-toast-status {
                background: linear-gradient(135deg, #667eea, #764ba2);
                color: #f8fafc;
            }

            .toast-container .toast.kara-toast-status .display-6,
            .toast-container .toast.kara-toast-status .fw-semibold,
            .toast-container .toast.kara-toast-status .mb-0.small {
                color: #f8fafc;
            }

            @media (max-width: 575.98px) {
                .toast-container {
                    width: 100%;
                    left: 0;
                    right: 0;
                    top: 1rem;
                }

                .toast-container .toast {
                    margin-inline: auto;
                    width: calc(100% - 2rem);
                    min-width: 0;
                }
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            (() => {
                const variantMap = window.karaToastVariants || @json($variants);
                window.karaToastVariants = variantMap;

                const resolveVariant = (type) => {
                    const fallback = variantMap['info'] ?? {
                        title: 'Notice',
                        icon: 'bi-info-circle-fill',
                        class: 'text-bg-info',
                        delay: 5000,
                        autohide: true,
                        close_class: 'btn-close btn-close-white',
                    };

                    if (!type) {
                        return { ...fallback };
                    }

                    const normalizedType = type.toString().toLowerCase();
                    const candidate = variantMap[normalizedType];

                    if (!candidate) {
                        return { ...fallback };
                    }

                    return { ...fallback, ...candidate };
                };

                const escapeSelector = (value) => {
                    if (window.CSS && typeof window.CSS.escape === 'function') {
                        return window.CSS.escape(value);
                    }

                    return value.replace(/[^a-zA-Z0-9_-]/g, '_');
                };

                const ensureContainer = () => {
                    let container = document.querySelector('[data-kara-toast-container]');
                    if (container) {
                        return container;
                    }

                    container = document.createElement('div');
                    container.className = 'toast-container position-fixed top-0 end-0 p-3';
                    container.style.zIndex = '1080';
                    container.setAttribute('data-kara-toast-container', 'true');
                    document.body.appendChild(container);

                    return container;
                };

                const createToastElement = (variant, options) => {
                    const toastId = options.id ?? `toast-${Date.now()}-${Math.random().toString(16).slice(2, 8)}`;
                    const toast = document.createElement('div');

                    toast.className = `toast align-items-stretch border-0 shadow-lg overflow-hidden ${variant.class}`.trim();
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');
                    toast.setAttribute('data-bs-delay', Number(options.delay ?? variant.delay) || variant.delay || 5000);
                    toast.setAttribute('data-bs-autohide', (options.autohide ?? variant.autohide) === false ? 'false' : 'true');
                    toast.setAttribute('data-kara-toast-ready', 'true');
                    toast.id = toastId;
                    if (options.key) {
                        toast.setAttribute('data-kara-toast-key', options.key);
                    }

                    const body = document.createElement('div');
                    body.className = 'toast-body p-3';

                    const layout = document.createElement('div');
                    layout.className = 'd-flex align-items-start gap-3';

                    const iconWrap = document.createElement('span');
                    iconWrap.className = 'flex-shrink-0 display-6 lh-1';
                    const icon = document.createElement('i');
                    icon.className = `bi ${variant.icon}`;
                    iconWrap.appendChild(icon);

                    const content = document.createElement('div');
                    content.className = 'flex-grow-1';

                    const titleEl = document.createElement('p');
                    titleEl.className = 'fw-semibold mb-1';
                    titleEl.textContent = options.title ?? variant.title;

                    const messageEl = document.createElement('p');
                    messageEl.className = 'mb-0 small';
                    messageEl.textContent = options.message ?? '';

                    const closeButton = document.createElement('button');
                    closeButton.type = 'button';
                    closeButton.className = `${variant.close_class ?? 'btn-close'} ms-2`;
                    closeButton.setAttribute('data-bs-dismiss', 'toast');
                    closeButton.setAttribute('aria-label', 'Close');

                    content.appendChild(titleEl);
                    content.appendChild(messageEl);

                    layout.appendChild(iconWrap);
                    layout.appendChild(content);
                    layout.appendChild(closeButton);

                    body.appendChild(layout);
                    toast.appendChild(body);

                    return toast;
                };

                const queue = (payload = {}) => {
                    const message = (payload.message ?? '').toString().trim();
                    if (!message) {
                        return;
                    }

                    const type = (payload.type ?? 'info').toString().toLowerCase();
                    const variant = resolveVariant(type);
                    const toastKey = `${type}:${message}`;

                    if (typeof payload.delay !== 'undefined') {
                        const normalizedDelay = Number(payload.delay);
                        if (!Number.isNaN(normalizedDelay) && normalizedDelay > 0) {
                            variant.delay = normalizedDelay;
                        }
                    }

                    if (typeof payload.autohide !== 'undefined') {
                        variant.autohide = Boolean(payload.autohide);
                    }

                    const container = ensureContainer();
                    const selector = `[data-kara-toast-key="${escapeSelector(toastKey)}"]`;
                    const existing = container.querySelector(selector);
                    if (existing) {
                        const instance = window.bootstrap?.Toast?.getInstance(existing);
                        if (instance) {
                            instance.hide();
                        }
                        existing.remove();
                    }

                    const toast = createToastElement(variant, {
                        id: payload.id,
                        title: payload.title ?? variant.title,
                        message,
                        delay: variant.delay,
                        autohide: variant.autohide,
                        key: toastKey,
                    });

                    container.appendChild(toast);

                    const api = window.karaToastManagerApi;
                    if (api && typeof api.observeContainer === 'function') {
                        api.observeContainer();
                    }

                    requestAnimationFrame(() => {
                        const updatedApi = window.karaToastManagerApi;
                        if (updatedApi && typeof updatedApi.showToasts === 'function') {
                            updatedApi.showToasts();
                        }
                    });
                };

                const registerDispatchListeners = () => {
                    if (window.__karaToastDispatchRegistered) {
                        return;
                    }

                    const handler = (event) => queue(event.detail || {});

                    document.addEventListener('kara-toast', handler);

                    window.__karaToastDispatchRegistered = true;
                };

                window.karaQueueToast = queue;

                window.karaInitToastManager = window.karaInitToastManager || function () {
                    const hasBootstrapToast = () =>
                        typeof window.bootstrap !== 'undefined' &&
                        typeof window.bootstrap.Toast !== 'undefined';

                    const showToasts = () => {
                        const readyToasts = document.querySelectorAll('.toast[data-kara-toast-ready="true"]:not([data-kara-toast-shown])');

                        if (!readyToasts.length) {
                            return;
                        }

                        readyToasts.forEach((toastEl) => {
                            toastEl.setAttribute('data-kara-toast-shown', 'true');

                            if (hasBootstrapToast()) {
                                const delay = parseInt(toastEl.getAttribute('data-bs-delay'), 10) || 5000;
                                const shouldAutohide = toastEl.getAttribute('data-bs-autohide') !== 'false';
                                const toast = window.bootstrap.Toast.getOrCreateInstance(toastEl, {
                                    autohide: shouldAutohide,
                                    delay,
                                });
                                toast.show();
                            } else {
                                toastEl.classList.add('show');
                                toastEl.style.opacity = '1';

                                const shouldAutohide = toastEl.getAttribute('data-bs-autohide') !== 'false';
                                const delay = parseInt(toastEl.getAttribute('data-bs-delay'), 10) || 5000;

                                if (shouldAutohide) {
                                    setTimeout(() => {
                                        toastEl.classList.remove('show');
                                        toastEl.style.opacity = '';
                                    }, delay);
                                }
                            }
                        });
                    };

                    const observeContainer = () => {
                        const container = document.querySelector('[data-kara-toast-container]');
                        if (!container || container.__karaToastObserved) {
                            return;
                        }

                        const observer = new MutationObserver(() => showToasts());
                        observer.observe(container, { childList: true });
                        container.__karaToastObserved = true;
                    };

                    const registerLivewireHook = () => {
                        if (window.__karaToastLivewireHooked) {
                            return;
                        }

                        const livewire = window.Livewire || window.livewire;

                        if (livewire && typeof livewire.on === 'function') {
                            livewire.on('processed', () => showToasts());
                            window.__karaToastLivewireHooked = true;
                            return;
                        }

                        if (livewire && typeof livewire.hook === 'function') {
                            livewire.hook('message.processed', () => showToasts());
                            window.__karaToastLivewireHooked = true;
                        }
                    };

                    showToasts();
                    observeContainer();
                    registerLivewireHook();

                    return { showToasts, observeContainer, registerLivewireHook };
                };

                const boot = () => {
                    const api = window.karaInitToastManager();
                    if (api) {
                        window.karaToastManagerApi = api;
                        api.observeContainer?.();
                        api.registerLivewireHook?.();
                        api.showToasts?.();
                    }
                };

                const registerLifecycleListeners = () => {
                    if (window.__karaToastLifecycleRegistered) {
                        return;
                    }

                    window.__karaToastLifecycleRegistered = true;

                    const events = ['livewire:init', 'livewire:load', 'livewire:navigated'];
                    events.forEach((eventName) => {
                        document.addEventListener(eventName, boot);
                    });
                };

                const bootWhenReady = () => {
                    if (document.readyState !== 'loading') {
                        boot();
                    } else {
                        document.addEventListener('DOMContentLoaded', boot, { once: true });
                    }
                };

                registerDispatchListeners();
                registerLifecycleListeners();
                bootWhenReady();
            })();
        </script>
    @endpush
@endonce
