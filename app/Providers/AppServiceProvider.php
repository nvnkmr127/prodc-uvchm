<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Services\PaymentReminderService;
use App\Services\PaymentAnalyticsService;
use App\Services\SMSService;
use App\Services\WhatsAppService;
use App\Helpers\PaymentHelper;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Register Payment Reminder Service as Singleton
        $this->app->singleton(PaymentReminderService::class, function ($app) {
            return new PaymentReminderService(
                $app->make(\App\Services\NotificationService::class),
                $app->make(SMSService::class),
                $app->make(WhatsAppService::class)
            );
        });

        // Register Payment Analytics Service as Singleton
        $this->app->singleton(PaymentAnalyticsService::class, function ($app) {
            return new PaymentAnalyticsService();
        });

        // Register SMS Service
        $this->app->singleton(SMSService::class, function ($app) {
            return new SMSService();
        });

        // Register WhatsApp Service
        $this->app->singleton(WhatsAppService::class, function ($app) {
            return new WhatsAppService();
        });

        // Register helper classes
        $this->app->singleton('payment.helper', function ($app) {
            return new PaymentHelper();
        });

        // Register development-specific services
        if ($this->app->environment('local', 'testing')) {
            // Register debugging tools or mock services
            $this->registerDevelopmentServices();
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default string length for older MySQL versions
        Schema::defaultStringLength(191);

        // Use Bootstrap 4 for pagination
        Paginator::useBootstrap();

        // Register custom Blade directives
        $this->registerBladeDirectives();

        // Register view composers
        $this->registerViewComposers();

        // Share global data with views
        $this->shareGlobalData();

        // Register custom validation rules
        $this->registerValidationRules();

        // Register event listeners
        $this->registerEventListeners();

        // Register macros
        $this->registerMacros();
    }

    /**
     * Register custom Blade directives for payment system
     */
    private function registerBladeDirectives(): void
    {
        // Payment status badge directive
        Blade::directive('paymentStatus', function ($status) {
            return "<?php echo get_payment_status_badge($status); ?>";
        });

        // Defaulter category badge directive
        Blade::directive('defaulterCategory', function ($category) {
            return "<?php echo get_defaulter_category_badge($category); ?>";
        });

        // Format currency directive
        Blade::directive('currency', function ($amount) {
            return "<?php echo \\App\\Helpers\\PaymentHelper::formatAmount($amount); ?>";
        });

        // Days overdue formatting directive
        Blade::directive('overdueDays', function ($days) {
            return "<?php echo format_overdue_days($days); ?>";
        });

        // Fee type color directive
        Blade::directive('feeTypeColor', function ($feeType) {
            return "<?php echo get_fee_type_color($feeType); ?>";
        });

        // Risk score badge directive
        Blade::directive('riskScore', function ($student) {
            return "<?php 
                \$risk = \\App\\Helpers\\PaymentHelper::getStudentRiskScore($student);
                \$colors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger'];
                \$color = \$colors[\$risk['level']] ?? 'secondary';
                echo '<span class=\"badge badge-' . \$color . '\">' . ucfirst(\$risk['level']) . ' Risk (' . \$risk['score'] . '%)</span>';
            ?>";
        });

        // Payment priority directive
        Blade::directive('paymentPriority', function ($feeType, $overdueDays) {
            return "<?php 
                \$priority = \\App\\Helpers\\PaymentHelper::getPaymentPriority($feeType, $overdueDays);
                \$colors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                \$color = \$colors[\$priority] ?? 'secondary';
                echo '<span class=\"badge badge-' . \$color . '\">' . ucfirst(\$priority) . '</span>';
            ?>";
        });

        // Late fee calculation directive
        Blade::directive('lateFee', function ($amount, $overdueDays) {
            return "<?php 
                \$lateFee = \\App\\Helpers\\PaymentHelper::calculateLateFee($amount, $overdueDays);
                echo \\App\\Helpers\\PaymentHelper::formatAmount(\$lateFee);
            ?>";
        });

        // Next reminder date directive
        Blade::directive('nextReminderDate', function ($reminderCount, $dueDate) {
            return "<?php 
                \$nextDate = \\App\\Helpers\\PaymentHelper::getNextReminderDate($reminderCount, \\Carbon\\Carbon::parse($dueDate));
                echo \$nextDate->format('d-m-Y');
            ?>";
        });

        // Collection efficiency directive
        Blade::directive('collectionEfficiency', function ($startDate, $endDate) {
            return "<?php 
                \$efficiency = \\App\\Helpers\\PaymentHelper::getCollectionEfficiency(
                    \\Carbon\\Carbon::parse($startDate), 
                    \\Carbon\\Carbon::parse($endDate)
                );
                echo \$efficiency['efficiency_percentage'] . '%';
            ?>";
        });

        // Student contact info directive
        Blade::directive('studentContact', function ($student) {
            return "<?php 
                \$contacts = [];
                if ($student->student_mobile) {
                    \$contacts[] = '<a href=\"tel:' . $student->student_mobile . '\" class=\"btn btn-sm btn-outline-primary\"><i class=\"fas fa-phone\"></i></a>';
                }
                if ($student->email) {
                    \$contacts[] = '<a href=\"mailto:' . $student->email . '\" class=\"btn btn-sm btn-outline-info\"><i class=\"fas fa-envelope\"></i></a>';
                }
                if ($student->father_mobile) {
                    \$contacts[] = '<a href=\"tel:' . $student->father_mobile . '\" class=\"btn btn-sm btn-outline-secondary\" title=\"Father\"><i class=\"fas fa-phone\"></i></a>';
                }
                echo '<div class=\"btn-group btn-group-sm\">' . implode('', \$contacts) . '</div>';
            ?>";
        });

        // Fee type icon directive
        Blade::directive('feeTypeIcon', function ($feeType) {
            return "<?php 
                \$icons = [
                    'tuition_fee' => 'fas fa-graduation-cap',
                    'uniform_fee' => 'fas fa-tshirt',
                    'library_fee' => 'fas fa-book',
                    'lab_fee' => 'fas fa-flask',
                    'exam_fee' => 'fas fa-clipboard-check',
                    'transport_fee' => 'fas fa-bus',
                    'hostel_fee' => 'fas fa-bed',
                    'sports_fee' => 'fas fa-football-ball'
                ];
                \$icon = \$icons[$feeType] ?? 'fas fa-money-bill';
                echo '<i class=\"' . \$icon . '\"></i>';
            ?>";
        });

        // Reminder channel icon directive
        Blade::directive('reminderChannelIcon', function ($channel) {
            return "<?php 
                \$icons = [
                    'email' => 'fas fa-envelope',
                    'sms' => 'fas fa-sms',
                    'whatsapp' => 'fab fa-whatsapp',
                    'phone_call' => 'fas fa-phone',
                    'physical_notice' => 'fas fa-file-alt'
                ];
                \$colors = [
                    'email' => 'primary',
                    'sms' => 'success',
                    'whatsapp' => 'success',
                    'phone_call' => 'warning',
                    'physical_notice' => 'secondary'
                ];
                \$icon = \$icons[$channel] ?? 'fas fa-bell';
                \$color = \$colors[$channel] ?? 'secondary';
                echo '<i class=\"' . \$icon . ' text-' . \$color . '\"></i>';
            ?>";
        });

        // Dashboard metric card directive
        Blade::directive('metricCard', function ($title, $value, $icon, $color = 'primary') {
            return "<?php 
                echo '<div class=\"card border-left-' . $color . ' shadow h-100 py-2\">
                    <div class=\"card-body\">
                        <div class=\"row no-gutters align-items-center\">
                            <div class=\"col mr-2\">
                                <div class=\"text-xs font-weight-bold text-' . $color . ' text-uppercase mb-1\">' . $title . '</div>
                                <div class=\"h5 mb-0 font-weight-bold text-gray-800\">' . $value . '</div>
                            </div>
                            <div class=\"col-auto\">
                                <i class=\"' . $icon . ' fa-2x text-gray-300\"></i>
                            </div>
                        </div>
                    </div>
                </div>';
            ?>";
        });
    }

    /**
     * Register view composers for payment system
     */
    private function registerViewComposers(): void
    {
        // Share payment statistics with all admin views
        View::composer('admin.*', function ($view) {
            if (auth()->check() && auth()->user()->can('view financials')) {
                $paymentStats = [
                    'total_defaulters' => \App\Models\Student::whereHas('invoices', function($q) {
                        $q->where('due_date', '<', now())->where('status', 'unpaid');
                    })->count(),
                    'overdue_amount' => \App\Models\Invoice::where('due_date', '<', now())
                        ->where('status', 'unpaid')->sum('due_amount'),
                    'reminders_sent_today' => \App\Models\PaymentReminder::whereDate('sent_at', today())->count(),
                ];
                
                $view->with('paymentStats', $paymentStats);
            }
        });

        // Share fee categories with payment views
        View::composer('admin.payment*', function ($view) {
            $feeCategories = \App\Models\FeeCategory::all();
            $view->with('feeCategories', $feeCategories);
        });

        // Share batches and courses with defaulter views
        View::composer('admin.payment-defaulters.*', function ($view) {
            $batches = \App\Models\Batch::with('course')->get();
            $courses = \App\Models\Course::all();
            $view->with('batches', $batches);
            $view->with('courses', $courses);
        });
    }

    /**
     * Share global data with all views
     */
   private function shareGlobalData(): void
{
    // Share payment reminder configuration
    View::share('paymentConfig', config('payment_reminders'));

    // Share currency settings
    View::share('currency', setting('currency', 'INR'));
    View::share('currencySymbol', match (setting('currency', 'INR')) {
        'INR' => '₹',
        'USD' => '$',
        'EUR' => '€',
        'GBP' => '£',
        default => '₹'
    });

    // Share date format settings
    View::share('dateFormat', setting('date_format', 'd-m-Y'));
    
    // Share app information
    View::share('appName', setting('app_name', 'College Management System'));
    View::share('appTagline', setting('app_tagline', 'Empowering Education Excellence'));
}

    /**
     * Register custom validation rules
     */
    private function registerValidationRules(): void
    {
        // Validate fee type
        \Validator::extend('valid_fee_type', function ($attribute, $value, $parameters, $validator) {
            return \App\Models\FeeCategory::where('category_type', $value)->exists();
        });

        // Validate reminder channel
        \Validator::extend('valid_reminder_channel', function ($attribute, $value, $parameters, $validator) {
            $validChannels = ['email', 'sms', 'whatsapp', 'phone_call', 'physical_notice'];
            return in_array($value, $validChannels);
        });

        // Validate defaulter category
        \Validator::extend('valid_defaulter_category', function ($attribute, $value, $parameters, $validator) {
            $validCategories = ['mild', 'moderate', 'severe', 'chronic'];
            return in_array($value, $validCategories);
        });

        // Validate payment status
        \Validator::extend('valid_payment_status', function ($attribute, $value, $parameters, $validator) {
            $validStatuses = ['paid', 'unpaid', 'partial', 'overdue', 'waived'];
            return in_array($value, $validStatuses);
        });

        // Validate phone number (Indian format)
        \Validator::extend('valid_phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[6-9]\d{9}$/', $value);
        });

        // Validate amount (positive number with up to 2 decimal places)
        \Validator::extend('valid_amount', function ($attribute, $value, $parameters, $validator) {
            return is_numeric($value) && $value >= 0 && preg_match('/^\d+(\.\d{1,2})?$/', $value);
        });
    }

    /**
     * Register event listeners for payment system
     */
    private function registerEventListeners(): void
    {
        // Listen for payment received events
        \Event::listen(\App\Events\PaymentReceived::class, \App\Listeners\PaymentReminderListener::class . '@handlePaymentReceived');

        // Listen for invoice generated events
        \Event::listen(\App\Events\InvoiceGenerated::class, \App\Listeners\PaymentReminderListener::class . '@handleInvoiceGenerated');

        // Listen for student status changes
        \Event::listen(\App\Events\StudentStatusChanged::class, function ($event) {
            if ($event->newStatus === 'inactive' || $event->newStatus === 'graduated') {
                // Cancel pending reminders for inactive/graduated students
                \App\Models\PaymentReminder::where('student_id', $event->student->id)
                    ->where('status', 'pending')
                    ->update(['status' => 'cancelled']);
            }
        });

        // Listen for reminder sent events
        \Event::listen(\App\Events\ReminderSent::class, function ($event) {
            // Update invoice reminder count
            if ($event->reminder->invoice_id) {
                \App\Models\Invoice::where('id', $event->reminder->invoice_id)
                    ->increment('reminder_sent_count');
                
                \App\Models\Invoice::where('id', $event->reminder->invoice_id)
                    ->update(['last_reminder_sent_at' => now()]);
            }
        });
    }

    /**
     * Register useful macros
     */
    private function registerMacros(): void
    {
        // Collection macro for payment statistics
        \Illuminate\Support\Collection::macro('paymentStats', function () {
            return [
                'total_amount' => $this->sum('amount'),
                'average_amount' => $this->avg('amount'),
                'min_amount' => $this->min('amount'),
                'max_amount' => $this->max('amount'),
                'count' => $this->count(),
            ];
        });

        // Collection macro for grouping by payment method
        \Illuminate\Support\Collection::macro('groupByPaymentMethod', function () {
            return $this->groupBy('payment_method')->map(function ($payments) {
                return [
                    'count' => $payments->count(),
                    'total' => $payments->sum('amount'),
                    'average' => $payments->avg('amount'),
                ];
            });
        });

        // Collection macro for overdue analysis
        \Illuminate\Support\Collection::macro('overdueAnalysis', function () {
            $now = now();
            return [
                'total' => $this->count(),
                'overdue' => $this->filter(function ($item) use ($now) {
                    return $item->due_date < $now && $item->status === 'unpaid';
                })->count(),
                'upcoming' => $this->filter(function ($item) use ($now) {
                    return $item->due_date >= $now && $item->due_date <= $now->addDays(7);
                })->count(),
                'overdue_amount' => $this->filter(function ($item) use ($now) {
                    return $item->due_date < $now && $item->status === 'unpaid';
                })->sum('due_amount'),
            ];
        });

        // Request macro for payment filters
        \Illuminate\Http\Request::macro('getPaymentFilters', function () {
            return [
                'fee_type' => $this->input('fee_type'),
                'batch_id' => $this->input('batch_id'),
                'course_id' => $this->input('course_id'),
                'status' => $this->input('status'),
                'start_date' => $this->input('start_date'),
                'end_date' => $this->input('end_date'),
                'defaulter_category' => $this->input('defaulter_category'),
                'reminder_channel' => $this->input('reminder_channel'),
            ];
        });
    }

    /**
     * Register development-specific services
     */
    private function registerDevelopmentServices(): void
    {
        // Mock SMS service for testing
        if (config('payment_reminders.development.mock_sms_service', false)) {
            $this->app->singleton(SMSService::class, function ($app) {
                return new \App\Services\MockSMSService();
            });
        }

        // Mock WhatsApp service for testing
        if (config('payment_reminders.development.mock_whatsapp_service', false)) {
            $this->app->singleton(WhatsAppService::class, function ($app) {
                return new \App\Services\MockWhatsAppService();
            });
        }

        // Debug toolbar integration
        if (class_exists(\Barryvdh\Debugbar\ServiceProvider::class)) {
            $this->app->register(\Barryvdh\Debugbar\ServiceProvider::class);
        }

        // Query logging for development
        if (config('payment_reminders.development.show_sql_queries', false)) {
            \DB::listen(function ($query) {
                \Log::info('SQL Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            });
        }
    }
}