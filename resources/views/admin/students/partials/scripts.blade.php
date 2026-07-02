@push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        initializeActivityLogs();
    });

    function initializeActivityLogs() {
        // Activity filter functionality
        const filterLinks = document.querySelectorAll('.activity-filter');
        const timelineItems = document.querySelectorAll('.timeline-item');

        filterLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                const filterType = this.getAttribute('data-type');

                // Update active filter
                filterLinks.forEach(l => l.classList.remove('active'));
                this.classList.add('active');

                // Filter timeline items
                timelineItems.forEach(item => {
                    const itemType = item.getAttribute('data-type');

                    if (filterType === 'all' || itemType === filterType) {
                        item.classList.remove('filtered-out');
                    } else {
                        item.classList.add('filtered-out');
                    }
                });
            });
        });

        // Toggle details functionality
        const toggleButtons = document.querySelectorAll('.toggle-details');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function() {
                const icon = this.querySelector('i');
                const isExpanded = this.getAttribute('aria-expanded') === 'true';

                // Rotate icon
                if (isExpanded) {
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    icon.style.transform = 'rotate(0deg)';
                }
            });
        });

        // Load more functionality
        const loadMoreBtn = document.getElementById('loadMoreActivity');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', function() {
                const studentId = this.getAttribute('data-student-id');
                loadMoreActivities(studentId);
            });
        }

        // Auto-refresh activity logs every 30 seconds
        setInterval(function() {
            refreshActivityLogs();
        }, 30000);
    }

    function loadMoreActivities(studentId) {
        const loadMoreBtn = document.getElementById('loadMoreActivity');
        const originalText = loadMoreBtn.innerHTML;

        loadMoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Loading...';
        loadMoreBtn.disabled = true;

        fetch(`/admin/students/${studentId}/activity-logs?offset=${document.querySelectorAll('.timeline-item').length}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.activities.length > 0) {
                    appendActivities(data.activities);

                    if (data.activities.length < 10) {
                        loadMoreBtn.style.display = 'none';
                    }
                } else {
                    loadMoreBtn.innerHTML = 'No more activities';
                    loadMoreBtn.disabled = true;
                }
            })
            .catch(error => {
                console.error('Error loading activities:', error);
                showNotification('Failed to load more activities', 'error');
            })
            .finally(() => {
                if (!loadMoreBtn.disabled) {
                    loadMoreBtn.innerHTML = originalText;
                    loadMoreBtn.disabled = false;
                }
            });
    }

    function appendActivities(activities) {
        const timeline = document.getElementById('activityTimeline');
        const currentCount = document.querySelectorAll('.timeline-item').length;

        activities.forEach((activity, index) => {
            const timelineItem = createTimelineItem(activity, currentCount + index);
            timeline.appendChild(timelineItem);
        });
    }

    function createTimelineItem(activity, index) {
        const div = document.createElement('div');
        div.className = 'timeline-item';
        div.setAttribute('data-type', activity.type);

        div.innerHTML = `
            <div class="timeline-marker bg-${activity.color}">
                <i class="fas ${activity.icon}"></i>
            </div>
            <div class="timeline-content">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div class="timeline-header">
                        <h6 class="mb-1 font-weight-bold">${activity.title}</h6>
                        <p class="text-muted mb-1">${activity.description}</p>
                    </div>
                    <div class="timeline-meta text-right">
                        <small class="text-muted d-block">${formatDate(activity.timestamp)}</small>
                        <small class="text-muted">${formatTime(activity.timestamp)}</small>
                    </div>
                </div>

                ${activity.properties && Object.keys(activity.properties).length > 0 ? `
                    <div class="timeline-details">
                        <button class="btn btn-sm btn-outline-secondary toggle-details" type="button" data-toggle="collapse" data-target="#details-${index}">
                            <i class="fas fa-chevron-down"></i> Details
                        </button>
                        <div class="collapse mt-2" id="details-${index}">
                            <div class="card card-body bg-light">
                                ${formatProperties(activity.properties, activity.type)}
                            </div>
                        </div>
                    </div>
                ` : ''}

                <div class="timeline-footer mt-2">
                    <small class="text-muted">
                        <i class="fas fa-user mr-1"></i>${activity.user}
                        <span class="mx-2">•</span>
                        <i class="fas fa-clock mr-1"></i>${activity.timestamp_human}
                    </small>
                </div>
            </div>
        `;

        return div;
    }

    function formatProperties(properties, type) {
        if (type === 'payment') {
            return `
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Amount:</strong> ₹${Number(properties.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</small><br>
                        <small><strong>Method:</strong> ${properties.method}</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Receipt:</strong> ${properties.receipt}</small><br>
                        <small><strong>Components:</strong> ${properties.components} items</small>
                    </div>
                </div>
            `;
        } else if (type === 'concession') {
            return `
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Amount:</strong> ₹${Number(properties.amount).toLocaleString('en-IN', {minimumFractionDigits: 2})}</small><br>
                        <small><strong>Status:</strong> ${properties.status}</small>
                    </div>
                    <div class="col-md-6">
                        ${properties.reason ? `<small><strong>Reason:</strong> ${properties.reason}</small>` : ''}
                    </div>
                </div>
            `;
        } else {
            let html = '<div class="properties-list">';
            for (const [key, value] of Object.entries(properties)) {
                if (typeof value !== 'object') {
                    html += `<small><strong>${key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}:</strong> ${value}</small><br>`;
                }
            }
            html += '</div>';
            return html;
        }
    }

    function formatDate(timestamp) {
        return new Date(timestamp).toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }

    function formatTime(timestamp) {
        return new Date(timestamp).toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true 
        });
    }

    function refreshActivityLogs() {
        // Silently refresh the activity count or highlight new activities
        const studentId = document.querySelector('#loadMoreActivity')?.getAttribute('data-student-id');
        if (!studentId) return;

        fetch(`/admin/students/${studentId}/activity-logs/count`)
            .then(response => response.json())
            .then(data => {
                if (data.new_count > 0) {
                    showNotification(`${data.new_count} new activities available`, 'info');
                }
            })
            .catch(error => {
                console.error('Error checking for new activities:', error);
            });
    }

    function showNotification(message, type = 'info') {
        // Use your existing notification system
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: type,
                title: message,
                timer: 3000,
                showConfirmButton: false,
                toast: true,
                position: 'top-end'
            });
        } else {
            // Fallback to console
            console.log(`[${type.toUpperCase()}] ${message}`);
        }
    }
    </script>
    <script>
    // Enhanced Concession Modal JavaScript - ADD THIS TO YOUR EXISTING SCRIPT SECTION
    document.addEventListener('DOMContentLoaded', function () {
        initializeConcessionModal();
    });

    function initializeConcessionModal() {
        const concessionComponentSelect = document.getElementById('concessionComponentSelect');
        const concessionAmountInput = document.getElementById('concession_amount');
        const concessionAmountHint = document.getElementById('concession_amount_hint');
        const concessionForm = document.getElementById('concessionForm');
        const applyConcessionBtn = document.getElementById('applyConcessionBtn');
        const quickAmountButtons = document.getElementById('quickAmountButtons');

        let currentSelectedFee = null;

        function updateConcessionInput() {
            const selectedOption = concessionComponentSelect.options[concessionComponentSelect.selectedIndex];
            const remainingAmount = selectedOption.getAttribute('data-remaining');

            if (remainingAmount && parseFloat(remainingAmount) > 0) {
                const maxAmount = parseFloat(remainingAmount);
                currentSelectedFee = {
                    id: selectedOption.value,
                    name: selectedOption.text.split('(')[0].trim(),
                    remaining: maxAmount
                };

                concessionAmountInput.max = maxAmount;
                concessionAmountHint.innerHTML = `Maximum: {{ setting('currency_symbol', '₹') }}${maxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`;
                concessionAmountInput.disabled = false;
                applyConcessionBtn.disabled = false;

                // Generate quick amount buttons
                generateQuickAmountButtons(maxAmount);
            } else {
                currentSelectedFee = null;
                concessionAmountInput.max = '';
                concessionAmountInput.value = '';
                concessionAmountHint.textContent = 'Select a fee component first';
                concessionAmountInput.disabled = true;
                applyConcessionBtn.disabled = true;
                quickAmountButtons.innerHTML = '';
            }
        }

    document.addEventListener('DOMContentLoaded', function() {
        // Refresh statistics after successful concession
        @if(session('success') && str_contains(session('success'), 'Concession'))
            console.log('✅ Concession applied, statistics should reflect changes');

            // Update the amount counters with animation
            setTimeout(() => {
                updateAmountCounters();
            }, 1000);
        @endif
    });

    function updateAmountCounters() {
        // Re-animate the counters with new values
        $('.amount-counter').each(function() {
            const element = $(this);
            const finalValue = element.text().replace(/[₹,]/g, '');

            if (!isNaN(finalValue) && finalValue !== '') {
                element.text('₹0');
                $({ counter: 0 }).animate({ counter: parseFloat(finalValue) }, {
                    duration: 1000,
                    easing: 'swing',
                    step: function() {
                        element.text('₹' + Math.ceil(this.counter).toLocaleString());
                    },
                    complete: function() {
                        element.text('₹' + parseFloat(finalValue).toLocaleString());
                    }
                });
            }
        });
    }

    // Debug function to check current values
    function debugFinancialSummary() {
        const summary = {
            total_fee: {{ isset($financialSummary['total_amount']) ? $financialSummary['total_amount'] : 0 }},
            total_paid: {{ isset($financialSummary['paid_amount']) ? $financialSummary['paid_amount'] : 0 }},
            total_concession: {{ isset($financialSummary['concession_amount']) ? $financialSummary['concession_amount'] : 0 }},
            total_due: {{ isset($financialSummary['remaining_amount']) ? $financialSummary['remaining_amount'] : 0 }},
            completion_percentage: {{ isset($financialSummary['payment_percentage']) ? $financialSummary['payment_percentage'] : 0 }}
        };

        console.log('💰 Current Financial Summary:', summary);
        return summary;
    }


        function generateQuickAmountButtons(maxAmount) {
            const percentages = [10, 25, 50, 100];
            let buttonsHtml = '';

            percentages.forEach(percentage => {
                const amount = (maxAmount * percentage) / 100;
                if (amount <= maxAmount) {
                    buttonsHtml += `
                        <button type="button" class="btn btn-outline-secondary btn-sm mb-1" 
                                style="border-radius: 15px; font-size: 0.75rem;"
                                onclick="setQuickConcessionAmount(${amount})">
                            ${percentage}% (₹${amount.toLocaleString('en-IN', {minimumFractionDigits: 0})})
                        </button>
                    `;
                }
            });

            // Add gender-based amount if applicable
            @if($student->gender === 'Female' && setting('womens_discount_percentage', 0) > 0)
                const genderPercentage = {{ setting('womens_discount_percentage', 0) }};
                if (genderPercentage > 0) {
                    const genderAmount = (maxAmount * genderPercentage) / 100;
                    buttonsHtml += `
                        <button type="button" class="btn btn-outline-success btn-sm mb-1" 
                                style="border-radius: 15px; font-size: 0.75rem;"
                                onclick="setQuickConcessionAmount(${genderAmount})">
                            Gender ${genderPercentage}% (₹${genderAmount.toLocaleString('en-IN', {minimumFractionDigits: 0})})
                        </button>
                    `;
                }
            @endif

            quickAmountButtons.innerHTML = buttonsHtml;
        }

        // Initialize
        if (concessionComponentSelect) {
            updateConcessionInput();
            concessionComponentSelect.addEventListener('change', updateConcessionInput);
        }

        // Form validation and submission
        if (concessionForm) {
            concessionForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const selectedComponent = concessionComponentSelect.value;
                const amount = parseFloat(concessionAmountInput.value);
                const maxAmount = parseFloat(concessionAmountInput.max);

                // Validation
                if (!selectedComponent) {
                    showNotification('Please select a fee component.', 'error');
                    return;
                }

                if (!amount || amount <= 0) {
                    showNotification('Please enter a valid concession amount.', 'error');
                    return;
                }

                if (amount > maxAmount) {
                    showNotification(`Concession amount cannot exceed {{ setting('currency_symbol', '₹') }}${maxAmount.toLocaleString('en-IN', {minimumFractionDigits: 2})}`, 'error');
                    return;
                }

                // Show confirmation
                const componentName = currentSelectedFee ? currentSelectedFee.name : 'selected component';
                if (confirm(`Apply concession of {{ setting('currency_symbol', '₹') }}${amount.toLocaleString('en-IN', {minimumFractionDigits: 2})} to ${componentName}?\n\nThis action cannot be undone.`)) {

                    // Show loading state
                    applyConcessionBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Processing...';
                    applyConcessionBtn.disabled = true;

                    // Submit form
                    this.submit();
                }
            });
        }

        // Reset modal when hidden
        $('#applyConcessionModal').on('hidden.bs.modal', function () {
            if (concessionForm) {
                concessionForm.reset();
            }
            if (applyConcessionBtn) {
                applyConcessionBtn.innerHTML = '<i class="fas fa-percent"></i> Apply Concession';
                applyConcessionBtn.disabled = false;
            }
            updateConcessionInput();
        });
    }

    // Helper functions for concession
    function setQuickConcessionAmount(amount) {
        const concessionInput = document.getElementById('concession_amount');
        if (concessionInput) {
            concessionInput.value = amount.toFixed(2);
        }
    }

    function applyAutomaticGenderConcession() {
        if (!confirm('Apply automatic gender-based concession to all eligible fee components?\n\nThis action cannot be undone.')) {
            return;
        }

        // Show loading state
        const originalButton = document.querySelector('[onclick="applyAutomaticGenderConcession()"]');
        if (originalButton) {
            const originalContent = originalButton.innerHTML;
            originalButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            originalButton.disabled = true;

            fetch('{{ url("admin/students/" . $student->id . "/apply-auto-gender-concession") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    $('#applyConcessionModal').modal('hide');
                    setTimeout(() => location.reload(), 2000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Error applying automatic concession: ' + error.message, 'error');
            })
            .finally(() => {
                // Restore button
                if (originalButton) {
                    originalButton.innerHTML = originalContent;
                    originalButton.disabled = false;
                }
            });
        }
    }
    </script>
    <script>
    $(document).ready(function() {
        // Initialize payment modal functionality
        initializePaymentModal();

        // Tab switching animations
        $('#profileTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });

        // Enhanced hover effects
        $('.stat-card').hover(
            function() { $(this).find('.fa-3x').addClass('fa-spin'); },
            function() { $(this).find('.fa-3x').removeClass('fa-spin'); }
        );

        // Initialize tooltips
        $('[data-toggle="tooltip"]').tooltip();

        // Counter animation for amounts
        animateCounters();
    });

    function initializePaymentModal() {
        const paymentAmountInput = $('#payment_amount');
        const componentCheckboxes = $('.component-checkbox');
        const componentAmounts = $('.component-amount');
        const submitBtn = $('#submit-payment-btn');
        const validationMessage = $('#payment-validation-message');
        const paymentTotalDisplay = $('#payment-total-display');
        const allocatedTotalDisplay = $('#allocated-total-display');

        // Payment amount change handler
        paymentAmountInput.on('input', function() {
            updatePaymentSummary();
            distributePaymentAmount();
        });

        // Component checkbox change handler
        componentCheckboxes.on('change', function() {
            const amountInput = $(this).closest('.payment-component-item').find('.component-amount');
            amountInput.prop('disabled', !this.checked);

            if (this.checked) {
                amountInput.focus();
            } else {
                amountInput.val('');
            }

            updateComponentSelection();
            updatePaymentSummary();
        });

        // Component amount change handler
        componentAmounts.on('input', function() {
            updatePaymentSummary();
        });

        function updatePaymentSummary() {
            const paymentAmount = parseFloat(paymentAmountInput.val()) || 0;
            let allocatedAmount = 0;

            $('.component-checkbox:checked').each(function() {
                const amountInput = $(this).closest('.payment-component-item').find('.component-amount');
                allocatedAmount += parseFloat(amountInput.val()) || 0;
            });

            paymentTotalDisplay.text('₹' + paymentAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}));
            allocatedTotalDisplay.text('₹' + allocatedAmount.toLocaleString('en-IN', {minimumFractionDigits: 2}));

            // Validation
            if (paymentAmount === 0) {
                validationMessage.html('<i class="fas fa-info-circle mr-2"></i>Enter payment amount and select components.');
                validationMessage.removeClass('text-success text-danger').addClass('text-muted');
                submitBtn.prop('disabled', true);
            } else if (allocatedAmount === 0) {
                validationMessage.html('<i class="fas fa-exclamation-triangle mr-2"></i>Please select at least one component to allocate payment.');
                validationMessage.removeClass('text-success text-muted').addClass('text-warning');
                submitBtn.prop('disabled', true);
            } else if (allocatedAmount > paymentAmount) {
                validationMessage.html('<i class="fas fa-times-circle mr-2"></i>Allocated amount exceeds payment amount!');
                validationMessage.removeClass('text-success text-muted').addClass('text-danger');
                submitBtn.prop('disabled', true);
            } else if (allocatedAmount < paymentAmount) {
                const difference = paymentAmount - allocatedAmount;
                validationMessage.html(`<i class="fas fa-info-circle mr-2"></i>₹${difference.toFixed(2)} remaining to allocate.`);
                validationMessage.removeClass('text-success text-danger').addClass('text-info');
                submitBtn.prop('disabled', false);
            } else {
                validationMessage.html('<i class="fas fa-check-circle mr-2"></i>Payment allocation is perfect!');
                validationMessage.removeClass('text-danger text-muted').addClass('text-success');
                submitBtn.prop('disabled', false);
            }
        }

        function distributePaymentAmount() {
            const paymentAmount = parseFloat(paymentAmountInput.val()) || 0;
            const checkedComponents = $('.component-checkbox:checked');

            if (checkedComponents.length === 0 || paymentAmount === 0) return;

            const amountPerComponent = paymentAmount / checkedComponents.length;

            checkedComponents.each(function() {
                const componentItem = $(this).closest('.payment-component-item');
                const amountInput = componentItem.find('.component-amount');
                const maxAmount = parseFloat(componentItem.data('max-amount'));

                const allocatedAmount = Math.min(amountPerComponent, maxAmount);
                amountInput.val(allocatedAmount.toFixed(2));
            });

            updatePaymentSummary();
        }

        function updateComponentSelection() {
            $('.payment-component-item').each(function() {
                const checkbox = $(this).find('.component-checkbox');
                if (checkbox.is(':checked')) {
                    $(this).addClass('selected');
                } else {
                    $(this).removeClass('selected');
                }
            });
        }
    }

    function openPaymentModal(feeId = null, feeName = null, dueAmount = null) {
        $('#paymentModal').modal('show');

        // If specific fee component is selected
        if (feeId) {
            setTimeout(() => {
                $(`#component_${feeId}`).prop('checked', true).trigger('change');
                if (dueAmount) {
                    $('#payment_amount').val(dueAmount).trigger('input');
                }
            }, 500);
        }
    }

    function openConcessionModal(feeId = null, feeName = null, remainingAmount = null) {
        $('#applyConcessionModal').modal('show');
        
        if (feeId) {
            setTimeout(() => {
                $('#concessionComponentSelect').val(feeId).trigger('change');
            }, 500);
        }
    }

    function viewReceipt(paymentId) {
        // Implement receipt viewing
        alert('Receipt viewing for payment ID: ' + paymentId);
    }

    function animateCounters() {
        $('.amount-counter').each(function() {
            const element = $(this);
            const finalValue = element.text().replace(/[₹,]/g, '');
            if (!isNaN(finalValue) && finalValue !== '') {
                element.text('₹0');
                $({ counter: 0 }).animate({ counter: parseFloat(finalValue) }, {
                    duration: 1500,
                    easing: 'swing',
                    step: function() {
                        element.text('₹' + Math.ceil(this.counter).toLocaleString());
                    },
                    complete: function() {
                        element.text('₹' + parseFloat(finalValue).toLocaleString());
                    }
                });
            }
        });
    }

    // Payment form validation
    $('#paymentForm').on('submit', function(e) {
        const paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        let allocatedAmount = 0;
        let hasSelectedComponents = false;

        $('.component-checkbox:checked').each(function() {
            hasSelectedComponents = true;
            const amountInput = $(this).closest('.payment-component-item').find('.component-amount');
            const amount = parseFloat(amountInput.val()) || 0;
            const maxAmount = parseFloat($(this).closest('.payment-component-item').data('max-amount'));

            if (amount <= 0) {
                e.preventDefault();
                alert('Please enter valid amounts for all selected components.');
                amountInput.focus();
                return false;
            }

            if (amount > maxAmount) {
                e.preventDefault();
                alert(`Amount for ${$(this).closest('.payment-component-item').find('.font-weight-bold').first().text()} cannot exceed ₹${maxAmount}`);
                amountInput.focus();
                return false;
            }

            allocatedAmount += amount;
        });

        if (!hasSelectedComponents) {
            e.preventDefault();
            alert('Please select at least one fee component.');
            return false;
        }

        if (allocatedAmount > paymentAmount) {
            e.preventDefault();
            alert('Total allocated amount cannot exceed the payment amount.');
            return false;
        }

        // Show loading state
        $('#submit-payment-btn').html('<i class="fas fa-spinner fa-spin mr-2"></i>Processing Payment...').prop('disabled', true);

        return true;
    });

    // Payment method change handler
    $('#payment_method').on('change', function() {
        const method = $(this).val();
        const transactionField = $('#transaction_id');
        const transactionLabel = $('label[for="transaction_id"]');

        if (method === 'cash') {
            transactionField.attr('placeholder', 'Cash receipt number (optional)');
            transactionLabel.html('Transaction Reference <small class="text-muted">(Optional)</small>');
        } else if (['upi', 'online', 'bank_transfer'].includes(method)) {
            transactionField.attr('placeholder', 'Transaction ID or reference number');
            transactionLabel.html('Transaction Reference <small class="text-danger">*</small>');
        } else {
            transactionField.attr('placeholder', 'Reference number');
            transactionLabel.html('Transaction Reference');
        }
    });

    // Auto-fill payment amount when component is selected
    $(document).on('change', '.component-checkbox', function() {
        const isChecked = $(this).is(':checked');
        const componentItem = $(this).closest('.payment-component-item');
        const maxAmount = parseFloat(componentItem.data('max-amount'));
        const amountInput = componentItem.find('.component-amount');

        if (isChecked && $('#payment_amount').val() === '') {
            $('#payment_amount').val(maxAmount).trigger('input');
        }
    });

    // Quick payment buttons for common amounts
    function addQuickPaymentButtons() {
        const quickAmounts = [500, 1000, 2000, 5000, 10000];
        let buttonsHtml = '<div class="mt-2"><small class="text-muted">Quick amounts: </small>';

        quickAmounts.forEach(amount => {
            buttonsHtml += `<button type="button" class="btn btn-outline-primary btn-sm mr-1 mb-1" onclick="setPaymentAmount(${amount})">₹${amount}</button>`;
        });

        buttonsHtml += '</div>';
        $('#payment_amount').after(buttonsHtml);
    }

    function setPaymentAmount(amount) {
        $('#payment_amount').val(amount).trigger('input');
    }

    // Initialize quick payment buttons
    setTimeout(addQuickPaymentButtons, 100);

    // Enhanced notification system
    function showNotification(message, type = 'info', duration = 5000) {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        };

        const icon = {
            'success': 'fa-check-circle',
            'error': 'fa-times-circle',
            'warning': 'fa-exclamation-triangle',
            'info': 'fa-info-circle'
        };

        const notification = $(`
            <div class="alert ${alertClass[type]} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);">
                <i class="fas ${icon[type]} mr-2"></i>
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `);

        $('body').append(notification);

        setTimeout(() => {
            notification.alert('close');
        }, duration);
    }

    // Check for success messages from localStorage
    if (localStorage.getItem('payment_success')) {
        showNotification(localStorage.getItem('payment_success'), 'success');
        localStorage.removeItem('payment_success');
    }

    // Keyboard shortcuts
    $(document).on('keydown', function(e) {
        // Ctrl + P for payment modal
        if (e.ctrlKey && e.key === 'p') {
            e.preventDefault();
            openPaymentModal();
        }

        // Ctrl + E for edit
        if (e.ctrlKey && e.key === 'e') {
            e.preventDefault();
            window.location.href = "{{ route('admin.students.edit', $student) }}";
        }

        // Ctrl + D for dashboard
        if (e.ctrlKey && e.key === 'd') {
            e.preventDefault();
            window.location.href = "{{ route('admin.payments.component-dashboard', $student) }}";
        }
    });

    // Progressive enhancement for mobile
    if (window.innerWidth <= 768) {
        $('.action-btn').addClass('btn-block mb-2');
        $('.quick-action-card').addClass('mb-3');
    }

    // Auto-save form data to localStorage (draft)
    let formDraftTimer;
    $('#paymentForm input, #paymentForm select, #paymentForm textarea').on('input change', function() {
        clearTimeout(formDraftTimer);
        formDraftTimer = setTimeout(saveFormDraft, 1000);
    });

    function saveFormDraft() {
        const formData = {
            payment_amount: $('#payment_amount').val(),
            payment_method: $('#payment_method').val(),
            payment_date: $('#payment_date').val(),
            transaction_id: $('#transaction_id').val(),
            notes: $('#notes').val(),
            selected_components: []
        };

        $('.component-checkbox:checked').each(function() {
            const componentItem = $(this).closest('.payment-component-item');
            formData.selected_components.push({
                fee_id: componentItem.data('fee-id'),
                amount: componentItem.find('.component-amount').val()
            });
        });

        localStorage.setItem('payment_form_draft', JSON.stringify(formData));
    }

    function loadFormDraft() {
        const draft = localStorage.getItem('payment_form_draft');
        if (draft) {
            try {
                const formData = JSON.parse(draft);
                $('#payment_amount').val(formData.payment_amount);
                $('#payment_method').val(formData.payment_method);
                $('#payment_date').val(formData.payment_date);
                $('#transaction_id').val(formData.transaction_id);
                $('#notes').val(formData.notes);

                formData.selected_components.forEach(component => {
                    $(`input[data-fee-id="${component.fee_id}"]`).prop('checked', true).trigger('change');
                    $(`.payment-component-item[data-fee-id="${component.fee_id}"] .component-amount`).val(component.amount);
                });

                showNotification('Previous draft loaded', 'info', 3000);
            } catch (e) {
                console.warn('Could not load form draft:', e);
            }
        }
    }

    // Clear draft on successful submission
    $('#paymentForm').on('submit', function() {
        localStorage.removeItem('payment_form_draft');
    });

    // Load draft when modal opens
    $('#paymentModal').on('shown.bs.modal', function() {
        setTimeout(loadFormDraft, 100);
    });

    // Clear draft button
    $('#paymentModal .modal-header').append(`
        <button type="button" class="btn btn-sm btn-outline-light mr-2" onclick="clearFormDraft()" title="Clear Draft">
            <i class="fas fa-eraser"></i>
        </button>
    `);

    function clearFormDraft() {
        localStorage.removeItem('payment_form_draft');
        $('#paymentForm')[0].reset();
        $('.component-checkbox').prop('checked', false).trigger('change');
        showNotification('Form draft cleared', 'info', 2000);
    }

    // Enhanced print functionality
    window.addEventListener('beforeprint', function() {
        document.title = 'Student Profile - {{ $student->name }}';
        $('.action-btn, .quick-action-card, .col-lg-4').hide();
        $('.col-lg-8').removeClass('col-lg-8').addClass('col-12');
        $('body').addClass('printing');
    });

    window.addEventListener('afterprint', function() {
        $('.action-btn, .quick-action-card, .col-lg-4').show();
        $('.col-12').removeClass('col-12').addClass('col-lg-8');
        $('body').removeClass('printing');
    });


    // Enhanced payment filtering
    function filterPayments() {
        $('#paymentFilterModal').modal('show');
    }

    function applyPaymentFilter() {
        const method = $('#filterMethod').val();
        const startDate = $('#filterStartDate').val();
        const endDate = $('#filterEndDate').val();
        const minAmount = $('#filterMinAmount').val();
        const maxAmount = $('#filterMaxAmount').val();
        const createdBy = $('#filterCreatedBy').val();

        const table = document.getElementById('payment-history-table');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');

        rows.forEach(row => {
            let showRow = true;
            const cells = row.querySelectorAll('td');

            // Filter by payment method
            if (method && showRow) {
                const methodCell = cells[3];
                if (methodCell) {
                    const rowMethod = methodCell.textContent.toLowerCase().trim();
                    if (!rowMethod.includes(method.toLowerCase())) {
                        showRow = false;
                    }
                }
            }

            // Filter by date range
            if ((startDate || endDate) && showRow && cells[1]) {
                const dateCell = cells[1];
                const dateSpan = dateCell.querySelector('span');
                if (dateSpan) {
                    const dateText = dateSpan.textContent;
                    const rowDate = new Date(dateText);

                    if (startDate && rowDate < new Date(startDate)) {
                        showRow = false;
                    }
                    if (endDate && rowDate > new Date(endDate)) {
                        showRow = false;
                    }
                }
            }

            // Filter by amount range
            if ((minAmount || maxAmount) && showRow && cells[2]) {
                const amountCell = cells[2];
                const amountText = amountCell.textContent.replace(/[₹,]/g, '');
                const rowAmount = parseFloat(amountText);

                if (minAmount && rowAmount < parseFloat(minAmount)) {
                    showRow = false;
                }
                if (maxAmount && rowAmount > parseFloat(maxAmount)) {
                    showRow = false;
                }
            }

            // Filter by created by
            if (createdBy && showRow && cells[5]) {
                const createdByCell = cells[5];
                const creatorElement = createdByCell.querySelector('strong');
                const creator = creatorElement ? creatorElement.textContent : '';
                if (!creator.toLowerCase().includes(createdBy.toLowerCase())) {
                    showRow = false;
                }
            }

            row.style.display = showRow ? '' : 'none';
        });

        $('#paymentFilterModal').modal('hide');
        updateFilteredSummary();
    }

    function clearPaymentFilter() {
        const form = document.getElementById('paymentFilterForm');
        if (form) {
            form.reset();
        }

        const rows = document.querySelectorAll('#payment-history-table tbody tr');
        rows.forEach(row => {
            row.style.display = '';
        });

        $('#paymentFilterModal').modal('hide');
        updateFilteredSummary();
    }

    function updateFilteredSummary() {
        const visibleRows = document.querySelectorAll('#payment-history-table tbody tr:not([style*="display: none"])');
        let totalAmount = 0;

        visibleRows.forEach(row => {
            const amountCell = row.querySelectorAll('td')[2];
            if (amountCell) {
                const amountText = amountCell.textContent.replace(/[₹,]/g, '');
                totalAmount += parseFloat(amountText) || 0;
            }
        });

        // Update summary if elements exist
        const totalCountElements = document.querySelectorAll('.text-primary h4');
        const totalAmountElements = document.querySelectorAll('.text-success h4');

        if (totalCountElements.length > 0) {
            totalCountElements[totalCountElements.length - 1].textContent = visibleRows.length;
        }
        if (totalAmountElements.length > 0) {
            totalAmountElements[totalAmountElements.length - 1].textContent = '₹' + totalAmount.toLocaleString('en-IN', {minimumFractionDigits: 2});
        }
    }

    // Export payment history
    function exportPaymentHistory() {
        const table = document.getElementById('payment-history-table');
        if (!table) {
            showNotification('Payment table not found', 'error');
            return;
        }

        const rows = table.querySelectorAll('tbody tr:not([style*="display: none"])');

        const csvData = [];
        csvData.push(['Receipt Number', 'Date', 'Amount', 'Method', 'Components', 'Created By', 'Status']);

        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) {
                const rowData = [
                    cells[0].querySelector('strong')?.textContent || '',
                    cells[1].querySelector('span')?.textContent || '',
                    cells[2].textContent.replace(/[₹,]/g, '').trim(),
                    cells[3].textContent.trim(),
                    cells[4].textContent.replace(/\n/g, '; ').trim(),
                    cells[5].querySelector('strong')?.textContent || '',
                    cells[6].textContent.trim()
                ];
                csvData.push(rowData);
            }
        });

        let csvContent = "data:text/csv;charset=utf-8,";
        csvData.forEach(function(rowArray) {
            let row = rowArray.map(field => '"' + field.replace(/"/g, '""') + '"').join(",");
            csvContent += row + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "payment_history_{{ $student->enrollment_number }}_" + new Date().toISOString().split('T')[0] + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        showNotification('Payment history exported successfully!', 'success');
    }

    // Print payment history
    function printPaymentHistory() {
        const printContent = generatePrintablePaymentHistory();

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    function generatePrintablePaymentHistory() {
        const table = document.getElementById('payment-history-table');
        if (!table) {
            return '<html><body><h1>Payment table not found</h1></body></html>';
        }

        const visibleRows = table.querySelectorAll('tbody tr:not([style*="display: none"])');
        let tableContent = '';

        visibleRows.forEach(row => {
            const cells = row.querySelectorAll('td');
            if (cells.length >= 7) {
                tableContent += `
                    <tr>
                        <td>${cells[0].querySelector('strong')?.textContent || ''}</td>
                        <td>${cells[1].querySelector('span')?.textContent || ''}</td>
                        <td>${cells[2].textContent.trim()}</td>
                        <td>${cells[3].textContent.trim()}</td>
                        <td>${cells[4].textContent.replace(/\s+/g, ' ').trim()}</td>
                        <td>${cells[5].querySelector('strong')?.textContent || ''}</td>
                        <td>${cells[6].textContent.trim()}</td>
                    </tr>
                `;
            }
        });

        return `
            <html>
            <head>
                <title>Payment History - {{ $student->name }}</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    .summary { margin-top: 20px; padding: 15px; background: #f9f9f9; border-radius: 5px; }
                    @media print { 
                        body { margin: 0; }
                        .no-print { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <h1>Payment History Report</h1>
                    <h3>{{ $student->name }} ({{ $student->enrollment_number }})</h3>
                    <p>{{ $student->batch->course->name ?? 'N/A' }} • {{ $student->batch->name ?? 'N/A' }}</p>
                    <p>Generated on: ${new Date().toLocaleString()}</p>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Method</th>
                            <th>Components</th>
                            <th>Created By</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableContent}
                    </tbody>
                </table>

                <div class="summary">
                    <h3>Summary</h3>
                    <p><strong>Total Payments:</strong> ${visibleRows.length}</p>
                    <p><strong>Report Generated By:</strong> {{ auth()->user()->name ?? 'System' }}</p>
                    <p><strong>Generated At:</strong> ${new Date().toLocaleString()}</p>
                </div>
            </body>
            </html>
        `;
    }

    // Enhanced refresh functionality
    function refreshPaymentHistory() {
        const refreshBtn = document.querySelector('button[onclick="refreshPaymentHistory()"]');
        if (!refreshBtn) return;

        const originalContent = refreshBtn.innerHTML;

        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        refreshBtn.disabled = true;

        // Simply reload the page for now - you can implement AJAX later
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    }

    // Enhanced payment details view
    function viewPaymentDetails(paymentId) {
        // Create a modal to show payment details
        const payment = findPaymentById(paymentId);

        if (!payment) {
            showNotification('Payment details not found', 'error');
            return;
        }

        const modalHtml = `
            <div class="modal fade" id="paymentDetailsModal" tabindex="-1" role="dialog">
                <div class="modal-dialog modal-lg" role="document">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">
                                <i class="fas fa-receipt mr-2"></i>Payment Details
                            </h5>
                            <button type="button" class="close text-white" data-dismiss="modal">
                                <span>&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="paymentDetailsContent">
                            ${generatePaymentDetailsHtml(payment)}
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            <button type="button" class="btn btn-primary" onclick="printPaymentModal()">
                                <i class="fas fa-print mr-2"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;

        // Remove existing modal if any
        $('#paymentDetailsModal').remove();

        // Add modal to body
        $('body').append(modalHtml);

        // Show modal
        $('#paymentDetailsModal').modal('show');
    }

    // Find payment by ID from the table data
    function findPaymentById(paymentId) {
        const paymentRow = $(`#payment-history-table tbody tr[data-payment-id="${paymentId}"]`);

        if (paymentRow.length === 0) {
            return null;
        }

        // Extract data from the row
        const cells = paymentRow.find('td');

        return {
            id: paymentId,
            receipt_number: $(cells[0]).find('strong').text(),
            transaction_id: $(cells[0]).find('small').text().replace('TXN: ', '') || '',
            date: $(cells[1]).find('span').text(),
            time: $(cells[1]).find('small').text(),
            amount: $(cells[2]).find('.badge').text(),
            method: $(cells[3]).find('.badge').text(),
            components: extractComponentsFromCell(cells[4]),
            created_by: $(cells[5]).find('strong').text(),
            created_time: $(cells[5]).find('small').text(),
            status: $(cells[6]).find('.badge').text(),
            notes: $(cells[7]).find('[data-original-title]').attr('data-original-title') || ''
        };
    }

    // Extract components from the table cell
    function extractComponentsFromCell(cell) {
        const components = [];
        $(cell).find('.component-breakdown small').each(function() {
            const text = $(this).text();
            const parts = text.split(':');
            if (parts.length === 2) {
                components.push({
                    name: parts[0].trim(),
                    amount: parts[1].trim()
                });
            }
        });
        return components;
    }

    // Generate HTML for payment details
    function generatePaymentDetailsHtml(payment) {
        return `
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-info-circle mr-2"></i>Payment Information</h6>
                        </div>
                        <div class="card-body">
                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="font-weight-bold">Receipt Number:</td>
                                    <td>${payment.receipt_number}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Amount:</td>
                                    <td class="text-success font-weight-bold">${payment.amount}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Payment Date:</td>
                                    <td>${payment.date} ${payment.time}</td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Payment Method:</td>
                                    <td><span class="badge badge-info">${payment.method}</span></td>
                                </tr>
                                <tr>
                                    <td class="font-weight-bold">Status:</td>
                                    <td><span class="badge badge-success">${payment.status}</span></td>
                                </tr>
                                ${payment.transaction_id ? `
                                <tr>
                                    <td class="font-weight-bold">Transaction ID:</td>
                                    <td><code>${payment.transaction_id}</code></td>
                                </tr>
                                ` : ''}
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-list mr-2"></i>Fee Components</h6>
                        </div>
                        <div class="card-body">
                            ${payment.components.length > 0 ? `
                                <table class="table table-borderless table-sm">
                                    ${payment.components.map(component => `
                                        <tr>
                                            <td>${component.name}</td>
                                            <td class="text-right font-weight-bold">${component.amount}</td>
                                        </tr>
                                    `).join('')}
                                </table>
                            ` : '<p class="text-muted">No component breakdown available</p>'}
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h6 class="mb-0"><i class="fas fa-user mr-2"></i>Audit Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Created By:</strong> ${payment.created_by}<br>
                                    <small class="text-muted">${payment.created_time}</small>
                                </div>
                                <div class="col-md-6">
                                    ${payment.notes ? `
                                        <strong>Notes:</strong><br>
                                        <div class="alert alert-info">${payment.notes}</div>
                                    ` : '<em class="text-muted">No notes</em>'}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    // Print modal content
    function printPaymentModal() {
        const modalContent = document.getElementById('paymentDetailsContent');
        if (!modalContent) return;

        const printContent = `
            <html>
            <head>
                <title>Payment Details</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <style>
                    body { margin: 20px; }
                    @media print { 
                        body { margin: 0; }
                        .btn { display: none; }
                    }
                </style>
            </head>
            <body>
                <div class="container-fluid">
                    <h2 class="text-center mb-4">Payment Details</h2>
                    ${modalContent.innerHTML}
                </div>
            </body>
            </html>
        `;

        const printWindow = window.open('', '_blank');
        printWindow.document.write(printContent);
        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }

    // Initialize payment history enhancements
    $(document).ready(function() {
        // Initialize tooltips for payment history
        $('[data-toggle="tooltip"]').tooltip();

        // Add hover effect to payment rows
        $('#payment-history-table tbody tr').hover(
            function() {
                $(this).addClass('table-active');
            },
            function() {
                $(this).removeClass('table-active');
            }
        );

        // Enhanced payment row click functionality
        $('#payment-history-table tbody tr').on('click', function(e) {
            // Don't trigger on button clicks
            if (!$(e.target).closest('.btn-group').length) {
                const paymentId = $(this).data('payment-id');
                if (paymentId) {
                    viewPaymentDetails(paymentId);
                }
            }
        });

        // Keyboard shortcuts for payment history
        $(document).on('keydown', function(e) {
            // Ctrl + F for filter
            if (e.ctrlKey && e.key === 'f') {
                e.preventDefault();
                filterPayments();
            }

            // Ctrl + E for export  
            if (e.ctrlKey && e.key === 'e') {
                e.preventDefault();
                exportPaymentHistory();
            }

            // Ctrl + R for refresh
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                refreshPaymentHistory();
            }
        });
    });

    // Success/error notification after form submissions
    @if(session('success'))
        showNotification('{{ session('success') }}', 'success');
    @endif

    @if(session('error'))
        showNotification('{{ session('error') }}', 'error');
    @endif

    // Payment history debug info (only in development)
    @if(config('app.debug'))
        console.log('💰 Payment History Debug:', {
            total_payments: {{ isset($paymentHistory) ? $paymentHistory->count() : 0 }},
            student_id: {{ $student->id }},
            @if(isset($paymentHistory) && $paymentHistory->count() > 0)
                recent_payments: [
                    @foreach($paymentHistory->take(3) as $payment)
                        {
                            id: {{ $payment->id }},
                            receipt: '{{ $payment->receipt_number }}',
                            amount: {{ $payment->amount }},
                            created_by: '{{ $payment->createdBy ? $payment->createdBy->name : 'null' }}'
                        }{{ $loop->last ? '' : ',' }}
                    @endforeach
                ]
            @endif
        });
    @endif


    // Add print styles
    $('<style>').prop('type', 'text/css').html(`
        @media print {
            .printing .modern-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
            .printing .profile-header {
                background: #f8f9fa !important;
                color: #333 !important;
            }
            .printing .nav-tabs-modern {
                display: none !important;
            }
            .printing .tab-content .tab-pane {
                display: block !important;
                opacity: 1 !important;
            }
            .printing .payment-progress-bar {
                background: #6c757d !important;
            }
        }
    `).appendTo('head');
    </script>
    <script>

    // Attendance functionality
    function loadAttendanceData() {
        const month = $('#attendanceMonth').val();

        // UI Updates
        $('#attendanceLoading').show();
        $('#attendanceContent').hide();
        $('#attendanceError').hide();

        $.ajax({
            url: `{{ route('admin.students.attendance-data', $student) }}`,
            method: 'GET',
            data: { month: month },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    populateAttendanceData(response.data);
                    $('#attendanceLoading').hide();
                    $('#attendanceContent').fadeIn();
                } else {
                    showAttendanceError(response.message);
                }
            },
            error: function(xhr) {
                console.error('Attendance Load Error:', xhr);
                let msg = 'Failed to load data.';
                if(xhr.responseJSON && xhr.responseJSON.message) {
                    msg = xhr.responseJSON.message;
                }
                showAttendanceError(msg);
            }
        });
    }

    function populateAttendanceData(data) {
        // Debug logging
        console.log('Received attendance data:', data);

        // Calculate Counts from Calendar Data
        let holidayCount = 0;
        let workingDaysCount = 0;

        if (data.calendar) {
            Object.values(data.calendar).forEach(day => {
                const s = day.status;

                // Count Holidays
                if (s === 'holiday') {
                    holidayCount++;
                }

                // Count Working Days (Till Date)
                // We count days that are NOT holidays, NOT weekends, and NOT 'none' (future/before joining)
                // This effectively counts: present, absent, late, excused, internship
                if (s !== 'holiday' && s !== 'weekend' && s !== 'none') {
                    workingDaysCount++;
                }
            });
        }

        // Update summary cards
        const percentage = (data.summary?.overall_percentage || 0) + '%';

        $('#headerAttendancePercentage').text(percentage);
        $('#tabAttendancePercentage').text(percentage);

        // Update Counters
        $('#totalWorkingDays').text(workingDaysCount); // New Counter
        $('#presentDays').text(data.monthly?.present_days || 0);
        $('#absentDays').text(data.monthly?.absent_days || 0);
        $('#lateDays').text(data.monthly?.late_days || 0);
        $('#holidayDays').text(holidayCount);

        // Update status alert
        updateAttendanceStatus(data.summary || {});

        // Update calendar title
        $('#calendarTitle').text((data.monthly?.month_name || 'Current Month') + ' Attendance');

        // Generate calendar with biometric data
        generateAttendanceCalendar(data.calendar || {}, data.monthly?.month_name || 'Current Month', data.biometric_summary || {}, data.attendance_patterns || {});

        // Populate recent records table
        populateAttendanceTable(data.monthly?.records || []);

        // Re-initialize tooltips
        if (typeof $ !== 'undefined' && $.fn.tooltip) {
            $('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').tooltip('dispose').tooltip();
        }
    }

    function updateAttendanceStatus(summary) {
        const statusConfig = {
            'excellent': {
                class: 'alert-success',
                title: 'Excellent Attendance!',
                message: 'Keep up the great work! Your attendance is outstanding.'
            },
            'good': {
                class: 'alert-info', 
                title: 'Good Attendance',
                message: 'Your attendance is good. Try to maintain this level.'
            },
            'satisfactory': {
                class: 'alert-warning',
                title: 'Satisfactory Attendance',
                message: 'Your attendance meets minimum requirements but could be improved.'
            },
            'needs_improvement': {
                class: 'alert-danger',
                title: 'Attendance Needs Improvement',
                message: 'Your attendance is below the required minimum. Please attend classes regularly.'
            }
        };

        const config = statusConfig[summary.status || 'needs_improvement'] || statusConfig['needs_improvement'];

        $('#attendanceStatusAlert')
            .removeClass('alert-success alert-info alert-warning alert-danger')
            .addClass(config.class)
            .show();

        $('#attendanceStatusTitle').text(config.title);
        $('#attendanceStatusMessage').text(config.message);
    }

    function generateAttendanceCalendar(calendarData, monthName, biometricSummary) {
        const selectedMonthDate = new Date(monthName);
        const year = selectedMonthDate.getFullYear();
        const month = selectedMonthDate.getMonth();
        const daysInMonth = new Date(year, month + 1, 0).getDate();
        const firstDay = new Date(year, month, 1).getDay();

        // Generate monthly summary
        const summaryHtml = generateMonthlySummary(biometricSummary, monthName);

        // Generate calendar header
        let calendarHtml = `
            <div class="comprehensive-calendar">
                ${summaryHtml}
                <div class="calendar-header text-center mb-3">
                    <h5 class="mb-0">${monthName}</h5>
                    <div class="calendar-legend mt-2">
                        <span class="legend-item"><span class="badge badge-success">Present</span></span>
                        <span class="legend-item"><span class="badge badge-warning">Late</span></span>
                        <span class="legend-item"><span class="badge badge-danger">Absent</span></span>
                        <span class="legend-item"><span class="badge badge-info">Excused</span></span>
                    </div>
                </div>
                <div class="calendar-grid">
                    <div class="calendar-weekdays">
                        <div class="weekday">Sun</div>
                        <div class="weekday">Mon</div>
                        <div class="weekday">Tue</div>
                        <div class="weekday">Wed</div>
                        <div class="weekday">Thu</div>
                        <div class="weekday">Fri</div>
                        <div class="weekday">Sat</div>
                    </div>
                    <div class="calendar-days">
        `;

        // Add empty cells for days before the first day of the month
        for (let i = 0; i < firstDay; i++) {
            calendarHtml += '<div class="calendar-day empty"></div>';
        }

        // Generate calendar days
        for (let day = 1; day <= daysInMonth; day++) {
            const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            const attendance = calendarData[dateStr];

            calendarHtml += generateCalendarDay(day, dateStr, attendance);
        }

        calendarHtml += `
                    </div>
                </div>
            </div>
        `;

        $('#attendanceCalendar').html(calendarHtml);

        // Initialize tooltips for the new calendar
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            // Dispose existing tooltips first (both BS4 and BS5 selectors)
            const tooltipSelectors = '[data-bs-toggle="tooltip"], [data-toggle="tooltip"]';
            document.querySelectorAll(tooltipSelectors).forEach(el => {
                try {
                    // Try to get and dispose existing tooltip
                    if (typeof bootstrap.Tooltip.getInstance === 'function') {
                        const tooltip = bootstrap.Tooltip.getInstance(el);
                        if (tooltip) tooltip.dispose();
                    } else {
                        // Fallback for older Bootstrap versions
                        $(el).tooltip('dispose');
                    }
                } catch (e) {
                    // Ignore errors during disposal
                }
            });

            // Initialize new tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll(tooltipSelectors));
            tooltipTriggerList.map(function (tooltipTriggerEl) {
                try {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                } catch (e) {
                    // Fallback to jQuery tooltip for compatibility
                    $(tooltipTriggerEl).tooltip();
                }
            });
        } else if (typeof $ !== 'undefined' && $.fn.tooltip) {
            // jQuery fallback for both BS4 and BS5 attributes
            $('[data-bs-toggle="tooltip"], [data-toggle="tooltip"]').tooltip();
        }
    }

    function generateMonthlySummary(biometricSummary, monthName) {
        if (!biometricSummary) return '';

        return `

        `;
    }

    function generateCalendarDay(day, dateStr, attendance) {
        console.log(`Generating calendar day for ${dateStr}:`, attendance);
        const statusColors = {
            'present': 'success',
            'absent': 'danger', 
            'late': 'warning',
            'excused': 'info'
        };

        const isToday = dateStr === new Date().toISOString().split('T')[0];
        const todayClass = isToday ? 'today' : '';

        if (!attendance) {
            return `
                <div class="calendar-day no-record ${todayClass}" data-date="${dateStr}">
                    <div class="day-number">${day}</div>
                    <div class="attendance-status">No Record</div>
                </div>
            `;
        }

        const workingHours = attendance.working_hours ? `${attendance.working_hours}h` : 'N/A';
        const checkInTime = attendance.check_in_time || 'N/A';
        const checkOutTime = attendance.check_out_time || 'N/A';

        const tooltipData = {
            date: dateStr,
            status: attendance.status,
            checkIn: checkInTime,
            checkOut: checkOutTime,
            workingHours: workingHours,
            isLate: attendance.is_late_arrival,
            isEarly: attendance.is_early_departure,
            subject: attendance.subject || 'General',
            remarks: attendance.remarks || ''
        };

        return `
            <div class="calendar-day status-${attendance.status} ${todayClass}" 
                 data-date="${dateStr}">
                <div class="day-number">${day}</div>
                <div class="attendance-status ${attendance.status}">${attendance.status.charAt(0).toUpperCase() + attendance.status.slice(1)}</div>
                ${attendance.is_late_arrival ? '<i class="fas fa-clock text-warning" style="font-size: 0.7rem;"></i>' : ''}
                ${attendance.is_early_departure ? '<i class="fas fa-sign-out-alt text-info" style="font-size: 0.7rem;"></i>' : ''}
            </div>
        `;
    }

    function populateAttendanceTable(records, showAll = false) {
        // console.log('Populating attendance table with records:', records);
        let tableHtml = '';
        const limit = 5;

        // Determine records to display: first 5 or all
        const displayRecords = (records && records.length > 0) 
            ? (showAll ? records : records.slice(0, limit)) 
            : [];

        if (displayRecords.length > 0) {
            displayRecords.forEach(record => {
                const statusBadge = getStatusBadge(record.status);

                // Format the date properly
                const formattedDate = record.attendance_date ? 
                    new Date(record.attendance_date).toLocaleDateString('en-US', {
                        year: 'numeric',
                        month: 'short',
                        day: 'numeric'
                    }) : 'N/A';

                // Format check-in time
                let checkInTime = 'N/A';
                if (record.check_in_time) {
                    if (typeof record.check_in_time === 'string') {
                        const timeParts = record.check_in_time.split(':');
                        if (timeParts.length >= 2) {
                            const hour = parseInt(timeParts[0]);
                            const minute = timeParts[1];
                            const ampm = hour >= 12 ? 'PM' : 'AM';
                            const displayHour = hour % 12 || 12;
                            checkInTime = `${displayHour}:${minute} ${ampm}`;
                        }
                    } else {
                        checkInTime = new Date('1970-01-01T' + record.check_in_time).toLocaleTimeString('en-US', {
                            hour: '2-digit',
                            minute: '2-digit',
                            hour12: true
                        });
                    }
                }

                tableHtml += `
                    <tr>
                        <td>${formattedDate}</td>
                        <td>${statusBadge}</td>
                    </tr>
                `;
            });
        } else {
            tableHtml = '<tr><td colspan="2" class="text-center text-muted">No attendance records found</td></tr>';
        }

        $('#attendanceRecordsTable tbody').html(tableHtml);

        // Handle "Load More" Button Logic
        const container = $('#attendanceRecordsTable').closest('.table-responsive');

        // Remove existing button first to prevent duplicates
        $('#btnLoadMoreAttendanceContainer').remove();

        if (!showAll && records && records.length > limit) {
            const remaining = records.length - limit;
            const loadMoreHtml = `
                <div class="text-center mt-2" id="btnLoadMoreAttendanceContainer">
                    <button class="btn btn-sm btn-outline-primary shadow-sm" id="btnLoadMoreAttendance">
                        <i class="fas fa-chevron-down mr-1"></i> View Full History (${remaining} more)
                    </button>
                </div>
            `;

            container.after(loadMoreHtml);

            // Reset container styles (no scroll)
            container.css({
                'max-height': '',
                'overflow-y': ''
            });

            // Bind Click Event
            $('#btnLoadMoreAttendance').off('click').on('click', function() {
                // Remove the button
                $('#btnLoadMoreAttendanceContainer').remove();

                // Show all records
                populateAttendanceTable(records, true);

                // Apply scroll styles to container
                container.css({
                    'max-height': '400px',
                    'overflow-y': 'auto',
                    'border': '1px solid #e9ecef',
                    'border-radius': '8px'
                });
            });
        } else if (showAll) {
             // If showing all, ensure container maintains scroll style
             container.css({
                'max-height': '400px',
                'overflow-y': 'auto',
                'border': '1px solid #e9ecef',
                'border-radius': '8px'
            });
        }
    }

    // Filtering and Export Functions
    function applyFilters() {
        const dateRange = $('#dateRangeFilter').val();
        const viewType = $('#viewTypeFilter').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        // Show loading state
        $('#attendanceLoading').show();
        $('#attendanceContent').hide();

        // Prepare filter parameters
        const filterParams = {
            date_range: dateRange,
            view_type: viewType
        };

        if (dateRange === 'custom' && startDate && endDate) {
            filterParams.start_date = startDate;
            filterParams.end_date = endDate;
        }

        // Make AJAX request with filters
        $.ajax({
            url: `{{ route('admin.students.attendance-data', $student) }}`,
            method: 'GET',
            data: filterParams,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    populateAttendanceData(response.data);
                    $('#attendanceLoading').hide();
                    $('#attendanceContent').show();
                } else {
                    showAttendanceError('Failed to load filtered attendance data');
                }
            },
            error: function(xhr) {
                const errorMessage = xhr.responseJSON?.message || 'Server error occurred';
                showAttendanceError(errorMessage);
            }
        });
    }

    function exportToPDF() {
        const filterParams = getFilterParams();
        const exportUrl = `{{ route('admin.students.attendance-export', ['student' => $student, 'format' => 'pdf']) }}`;

        // Create form and submit for PDF download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = exportUrl;
        form.target = '_blank';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = $('meta[name="csrf-token"]').attr('content');
        form.appendChild(csrfInput);

        // Add filter parameters
        Object.keys(filterParams).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filterParams[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function exportToExcel() {
        const filterParams = getFilterParams();
        const exportUrl = `{{ route('admin.students.attendance-export', ['student' => $student, 'format' => 'excel']) }}`;

        // Create form and submit for Excel download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = exportUrl;
        form.target = '_blank';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = $('meta[name="csrf-token"]').attr('content');
        form.appendChild(csrfInput);

        // Add filter parameters
        Object.keys(filterParams).forEach(key => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = filterParams[key];
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    }

    function getFilterParams() {
        const dateRange = $('#dateRangeFilter').val();
        const viewType = $('#viewTypeFilter').val();
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();

        const params = {
            date_range: dateRange,
            view_type: viewType
        };

        if (dateRange === 'custom' && startDate && endDate) {
            params.start_date = startDate;
            params.end_date = endDate;
        }

        return params;
    }

    // Event listeners for filter controls
    $(document).ready(function() {
        // Show/hide custom date range inputs
        $('#dateRangeFilter').on('change', function() {
            if ($(this).val() === 'custom') {
                $('#customDateRange').show();
            } else {
                $('#customDateRange').hide();
            }
        });

        // Auto-apply filters when date range changes (except custom)
        $('#dateRangeFilter, #viewTypeFilter').on('change', function() {
            if ($('#dateRangeFilter').val() !== 'custom') {
                applyFilters();
            }
        });

        // Apply filters when custom dates are selected
        $('#startDate, #endDate').on('change', function() {
            if ($('#dateRangeFilter').val() === 'custom' && $('#startDate').val() && $('#endDate').val()) {
                applyFilters();
            }
        });
    });

    // Helper function to get status badge HTML
    function getStatusBadge(status) {
        const badges = {
            'present': '<span class="badge badge-success">Present</span>',
            'absent': '<span class="badge badge-danger">Absent</span>',
            'late': '<span class="badge badge-warning">Late</span>',
            'excused': '<span class="badge badge-info">Excused</span>'
        };
        return badges[status] || '<span class="badge badge-secondary">Unknown</span>';
    }

    // Function to show attendance error
    function showAttendanceError(message) {
        $('#attendanceLoading').hide();
        $('#attendanceContent').hide();
        $('#attendanceError').show();
        $('#attendanceErrorMessage').text(message);
    }

    // Initialize calendar on page load
    $(document).ready(function() {
        loadAttendanceData();
    });





    // End of attendance calendar functionality
    </script>

    <script>
    $(document).ready(function() {
        // Load unassigned fee components when modal opens
        $('#addFeeComponentModal').on('show.bs.modal', function() {
            console.log('Modal opening...');
            loadUnassignedFeeComponents();
        });
    });

    function loadUnassignedFeeComponents() {
        console.log('Loading unassigned fee components...');

        $('#loadingFeeComponents').show();
        $('#feeComponentsList').html('');
        $('#noFeeComponents').hide();

        var url = "{{ route('admin.students.unassigned-fee-components', $student->id) }}";
        console.log('Fetching from:', url);

        $.ajax({
            url: url,
            type: 'GET',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                'Accept': 'application/json'
            },
            success: function(response) {
                console.log('=== FULL RESPONSE ===');
                console.log(JSON.stringify(response, null, 2));
                console.log('=== END RESPONSE ===');

                $('#loadingFeeComponents').hide();

                // Check if response has components
                if (!response.success) {
                    $('#feeComponentsList').html('<div class="alert alert-danger">Error: ' + response.message + '</div>');
                    return;
                }

                if (!response.components || response.components.length === 0) {
                    console.log('No components in response');
                    $('#noFeeComponents').show();
                    return;
                }

                console.log('Processing ' + response.components.length + ' components');

                var html = '<div class="list-group">';

                $.each(response.components, function(index, component) {
                    console.log('Component ' + index + ':', component);

                    var description = component.description ? '<p class="mb-1 text-muted small">' + component.description + '</p>' : '';
                    var amount = component.amount > 0 ? parseFloat(component.amount).toLocaleString('en-IN', {minimumFractionDigits: 2}) : '0.00';
                    var warning = component.warning ? '<br><small class="text-warning"><i class="fas fa-exclamation-triangle"></i> ' + component.warning + '</small>' : '';
                    var btnDisabled = component.amount <= 0 ? 'disabled title="No fee structure defined"' : '';
                    var btnClass = component.amount <= 0 ? 'btn-secondary' : 'btn-success';

                    html += '<div class="list-group-item list-group-item-action fee-component-select-item" ' +
                            'style="border-radius: 10px; margin-bottom: 10px; transition: all 0.3s ease;" ' +
                            'data-component-id="' + component.id + '">' +
                            '<div class="d-flex justify-content-between align-items-center">' +
                                '<div class="flex-grow-1">' +
                                    '<h6 class="mb-1 font-weight-bold">' + component.name + '</h6>' +
                                    description +
                                    warning +
                                '</div>' +
                                '<div class="text-right ml-3">' +
                                    '<div class="h5 mb-0 text-primary font-weight-bold">₹' + amount + '</div>' +
                                    '<button class="btn btn-sm ' + btnClass + ' mt-2 assign-component-btn" ' +
                                            'data-component-id="' + component.id + '" ' +
                                            'data-component-name="' + component.name + '" ' +
                                            'data-component-amount="' + component.amount + '" ' +
                                            btnDisabled + '>' +
                                        '<i class="fas fa-plus mr-1"></i> Assign' +
                                    '</button>' +
                                '</div>' +
                            '</div>' +
                        '</div>';
                });

                html += '</div>';

                console.log('Setting HTML, length:', html.length);
                $('#feeComponentsList').html(html);

                // Add hover effects
                $('.fee-component-select-item').hover(
                    function() {
                        $(this).css({'background-color': '#f8f9fc', 'box-shadow': '0 5px 15px rgba(0,0,0,0.1)'});
                    },
                    function() {
                        $(this).css({'background-color': '', 'box-shadow': ''});
                    }
                );

                console.log('Components rendered successfully');
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    responseText: xhr.responseText,
                    error: error
                });

                $('#loadingFeeComponents').hide();
                var errorMessage = 'Failed to load fee components';

                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                $('#feeComponentsList').html(
                    '<div class="alert alert-danger">' +
                        '<i class="fas fa-exclamation-triangle mr-2"></i>' +
                        errorMessage +
                        '<br><small class="mt-2 d-block">Status: ' + xhr.status + '</small>' +
                        '<br><small>Response: ' + xhr.responseText + '</small>' +
                    '</div>'
                );
            }
        });
    }

    // Handle component assignment
    $(document).on('click', '.assign-component-btn', function(e) {
        e.stopPropagation();
        var componentId = $(this).data('component-id');
        var componentName = $(this).data('component-name');
        var componentAmount = $(this).data('component-amount');

        // If amount is 0 or not set, prompt for it
        if (!componentAmount || componentAmount == 0) {
            var amount = prompt('Enter the fee amount for ' + componentName + ':');
            if (amount === null || amount === '' || isNaN(amount) || parseFloat(amount) < 0) {
                showNotification('Please enter a valid amount', 'error');
                return;
            }
            componentAmount = parseFloat(amount);
        }

        if (confirm('Assign "' + componentName + '" (₹' + parseFloat(componentAmount).toLocaleString('en-IN') + ') to this student?')) {
            assignFeeComponent(componentId, componentName, componentAmount);
        }
    });

    function assignFeeComponent(componentId, componentName, amount) {
        var button = $('.assign-component-btn[data-component-id="' + componentId + '"]');
        var originalHtml = button.html();

        button.html('<i class="fas fa-spinner fa-spin"></i> Assigning...').prop('disabled', true);

        $.ajax({
            url: "{{ route('admin.students.assign-fee-component', $student->id) }}",
            type: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                fee_category_id: componentId,
                amount: amount  // ← Make sure this is being sent
            },
            success: function(response) {
                if (response.success) {
                    showNotification(componentName + ' has been assigned successfully!', 'success');
                    $('#addFeeComponentModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotification(response.message || 'Failed to assign fee component', 'error');
                    button.html(originalHtml).prop('disabled', false);
                }
            },
            error: function(xhr) {
                var errorMessage = 'Error assigning fee component';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showNotification(errorMessage, 'error');
                button.html(originalHtml).prop('disabled', false);
            }
        });
    }
    </script>
@endpush