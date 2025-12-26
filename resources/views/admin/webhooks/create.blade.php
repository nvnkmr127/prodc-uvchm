@extends('layouts.theme')
@section('title', 'Add New Webhook')

@push('styles')
    <style>
        /* Premium Design System */
        :root {
            --primary-color: #4e73df; /* Adjust to match your theme's primary */
            --primary-light: #eef2ff;
            --border-color: #e3e6f0;
            --text-color: #5a5c69;
            --heading-color: #4e73df;
        }

        .card-premium {
            border: none;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            background: #fff;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .form-label-premium {
            font-weight: 700;
            color: #2d3748;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 0.75rem;
            display: block;
        }

        /* Modern Input Fields */
        .input-group-premium {
            background: #f8f9fc;
            border: 2px solid transparent;
            border-radius: 12px;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .input-group-premium:focus-within {
            background: #fff;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 4px rgba(78, 115, 223, 0.1);
        }

        .input-group-icon {
            display: flex;
            align-items: center;
            padding: 0 1.25rem;
            color: #a0aec0;
            font-size: 1.1rem;
        }

        .form-control-premium {
            border: none;
            background: transparent;
            padding: 1rem 0;
            font-size: 1rem;
            color: #4a5568;
            font-weight: 500;
            height: auto;
        }

        .form-control-premium:focus {
            box-shadow: none;
            background: transparent;
        }

        /* Visual Event Selector */
        .event-selector-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .event-card {
            cursor: pointer;
            border: 2px solid #edf2f7;
            border-radius: 12px;
            padding: 1.5rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            background: #fff;
            overflow: hidden;
        }

        .event-card:hover {
            transform: translateY(-2px);
            border-color: #cbd5e0;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }

        .event-card.selected {
            border-color: var(--primary-color);
            background: var(--primary-light);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.15);
        }

        .event-card.selected::after {
            content: '\f00c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--primary-color);
            font-size: 1.25rem;
        }

        .event-icon-wrapper {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
            background: #f1f5f9;
            color: #64748b;
            transition: all 0.2s;
        }

        .event-card.selected .event-icon-wrapper {
            background: #fff;
            color: var(--primary-color);
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .event-name {
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.25rem;
            display: block;
        }

        .event-desc {
            font-size: 0.85rem;
            color: #718096;
            line-height: 1.4;
        }

        /* Category Badge */
        .category-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 700;
            color: #a0aec0;
            margin-bottom: 0.5rem;
            display: block;
        }

        /* Guides */
        .guide-box {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 16px;
            padding: 2rem;
            position: sticky;
            top: 2rem;
        }

        .guide-item {
            margin-bottom: 1.5rem;
            border-left: 2px solid rgba(255,255,255,0.3);
            padding-left: 1rem;
        }

        .guide-item strong {
            display: block;
            font-size: 1.1rem;
            margin-bottom: 0.25rem;
        }

        .guide-item p {
            color: rgba(255,255,255,0.8);
            font-size: 0.9rem;
            margin: 0;
        }

    </style>
@endpush

@section('content')
    <div class="container-fluid pb-5">
        <!-- Header -->
        <div class="d-sm-flex align-items-center justify-content-between mb-4">
            <div>
                <h1 class="h3 mb-0 text-gray-800 font-weight-bold">Create Webhook</h1>
                <p class="mb-0 text-muted mt-1">Connect your application to external services with real-time events.</p>
            </div>
            <a href="{{ route('admin.webhooks.index') }}" class="btn btn-light shadow-sm text-secondary font-weight-bold">
                <i class="fas fa-times mr-2"></i>Cancel
            </a>
        </div>

        <div class="row">
            <!-- Main Form -->
            <div class="col-lg-8">
                <form action="{{ route('admin.webhooks.store') }}" method="POST" id="webhookForm">
                    @csrf
                    <input type="hidden" name="event_name" id="selected_event_input" required>
                    <input type="hidden" name="is_active" value="1">

                    <!-- 1. Endpoint Configuration -->
                    <div class="card card-premium mb-4">
                        <div class="card-body p-4">
                            <h5 class="font-weight-bold text-gray-800 mb-4"><i class="fas fa-satellite-dish mr-2 text-primary"></i>Endpoint Configuration</h5>

                            <div class="mb-4">
                                <label for="url" class="form-label-premium">Destination URL</label>
                                <div class="input-group-premium">
                                    <div class="input-group-icon">
                                        <i class="fas fa-globe"></i>
                                    </div>
                                    <input type="url" name="url" id="url" class="form-control form-control-premium" 
                                           placeholder="https://api.yourservice.com/webhooks/incoming" 
                                           value="{{ old('url') }}" required>
                                </div>
                                <small class="text-muted mt-2 d-block ml-1">
                                    <i class="fas fa-lock fa-xs mr-1 text-success"></i> We'll send a POST request with a JSON payload to this secure URL.
                                </small>
                                @error('url')
                                    <div class="text-danger small mt-1 font-weight-bold">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-0">
                                <label for="description" class="form-label-premium">Description (Optional)</label>
                                <div class="input-group-premium">
                                    <div class="input-group-icon">
                                        <i class="fas fa-quote-left"></i>
                                    </div>
                                    <textarea name="description" id="description" class="form-control form-control-premium" 
                                              rows="1" placeholder="e.g. Syncs daily attendance to Slack..." 
                                              style="height: auto; min-height: 3rem;">{{ old('description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Event Selector -->
                    <div class="mb-4">
                        <h5 class="font-weight-bold text-gray-800 mb-3 px-1">
                            <i class="fas fa-bolt mr-2 text-warning"></i>Select Trigger Event
                        </h5>

                        @error('event_name')
                            <div class="alert alert-danger shadow-sm rounded-lg mb-3 border-0">
                                <i class="fas fa-exclamation-circle mr-2"></i> Please select an event type below.
                            </div>
                        @enderror

                        @php
                            $iconMap = [
                                'payment.created' => 'fas fa-money-bill-wave',
                                'invoice.generated' => 'fas fa-file-invoice-dollar',
                                'student_fee.created' => 'fas fa-file-invoice',
                                'concession.applied' => 'fas fa-percent',
                                'student.created' => 'fas fa-user-plus',
                                'admission.approved' => 'fas fa-check-circle',
                                'attendance.daily_absent' => 'fas fa-user-clock',
                                'daily.summary' => 'fas fa-robot',
                                'enquiry.created' => 'fas fa-question-circle',
                            ];
                        @endphp

                        @if(isset($eventCategories) && count($eventCategories) > 0)
                            @foreach($eventCategories as $categoryName => $categoryData)
                                <div class="mb-3">
                                    <span class="category-badge pl-1">{{ $categoryName }}</span>
                                    <div class="event-selector-grid">
                                        @if(isset($categoryData['events']))
                                            @foreach($categoryData['events'] as $eventKey => $eventInfo)
                                                <div class="event-card" onclick="selectEvent('{{ $eventKey }}', this)">
                                                    <div class="event-icon-wrapper">
                                                        <i class="{{ $iconMap[$eventKey] ?? 'fas fa-bolt' }}"></i>
                                                    </div>
                                                    <span class="event-name">{{ $eventInfo['name'] ?? $eventKey }}</span>
                                                    <span class="event-desc">{{ $eventInfo['description'] ?? '' }}</span>
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <!-- Fallback if no categories are passed -->
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-circle mr-2"></i> No event categories available.
                            </div>
                        @endif
                    </div>

                    <div class="text-right">
                        <button type="submit" class="btn btn-primary btn-lg shadow-lg px-5 py-3 rounded-pill font-weight-bold" style="font-size: 1.1rem;">
                            Create Webhook <i class="fas fa-arrow-right ml-2"></i>
                        </button>
                    </div>
                </form>
            </div>

            <!-- Sidebar Guide -->
            <div class="col-lg-4">
                <div class="guide-box shadow-lg">
                    <div class="d-flex align-items-center mb-4">
                        <i class="fas fa-rocket fa-2x mr-3 text-white-50"></i>
                        <h4 class="font-weight-bold m-0">Quick Start</h4>
                    </div>

                    <div class="guide-item">
                        <strong>1. HTTP POST</strong>
                        <p>We send all events as POST requests. Make sure your endpoint accepts POST.</p>
                    </div>

                    <div class="guide-item">
                        <strong>2. Verify Signature</strong>
                        <p>Check the <code>X-Webhook-Signature</code> header to verify the request came from us.</p>
                    </div>

                    <div class="guide-item mb-0">
                        <strong>3. Automatic Retries</strong>
                        <p>If your server is down, we'll try again up to 5 times with exponential backoff.</p>
                    </div>

                    <div class="mt-4 pt-4 border-top border-light">
                        <small class="text-white-50 d-block mb-2">NEED HELP?</small>
                        <a href="#" class="btn btn-light btn-sm text-primary font-weight-bold shadow-sm">
                            <i class="fas fa-book mr-1"></i> Developer Docs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function selectEvent(eventName, cardElement) {
            // Update Hidden Input
            document.getElementById('selected_event_input').value = eventName;

            // Update UI Classes
            document.querySelectorAll('.event-card').forEach(el => el.classList.remove('selected'));
            cardElement.classList.add('selected');

            // Optional: Auto-fill description if empty
            const descInput = document.getElementById('description');
            if (!descInput.value) {
                const cardDesc = cardElement.querySelector('.event-desc').innerText;
                // Shorten for placeholder/value if needed, or just set it
                // descInput.value = "Webhook for " + cardElement.querySelector('.event-name').innerText;
            }

            // Add visual pulse effect
            cardElement.animate([
                { transform: 'scale(1)' },
                { transform: 'scale(0.98)' },
                { transform: 'scale(1)' }
            ], {
                duration: 200,
                easing: 'ease-out'
            });
        }

        // Pre-select if validation failed
        window.addEventListener('DOMContentLoaded', () => {
            const oldValue = "{{ old('event_name') }}";
            if(oldValue) {
                const card = document.querySelector(`[onclick="selectEvent('${oldValue}', this)"]`);
                if(card) selectEvent(oldValue, card);
            }
        });
    </script>
@endsection
