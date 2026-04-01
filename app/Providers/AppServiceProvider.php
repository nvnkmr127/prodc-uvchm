<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Models\StudentFee;
use Illuminate\Support\Facades\DB;
use Spatie\Health\Facades\Health;
use Spatie\Health\Checks\Checks\UsedDiskSpaceCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\DebugModeCheck;
use Spatie\Health\Checks\Checks\PingCheck;
use Spatie\Health\Checks\Checks\CacheCheck;



class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ✅ SAFE: Only register services that actually exist

        // Register ComponentPaymentService if it exists
        if (class_exists('\App\Services\ComponentPaymentService')) {
            $this->app->singleton(\App\Services\ComponentPaymentService::class, function ($app) {
                return new \App\Services\ComponentPaymentService();
            });
        }

        // Register DashboardService if it exists
        if (class_exists('\App\Services\DashboardService')) {
            $this->app->singleton(\App\Services\DashboardService::class, function ($app) {
                return new \App\Services\DashboardService();
            });
        }

        // Register DashboardDataService if it exists
        if (class_exists('\App\Services\DashboardDataService')) {
            $this->app->singleton(\App\Services\DashboardDataService::class, function ($app) {
                $componentPaymentService = $app->has(\App\Services\ComponentPaymentService::class)
                    ? $app->make(\App\Services\ComponentPaymentService::class)
                    : null;

                return new \App\Services\DashboardDataService($componentPaymentService);
            });
        }

        // Register ComponentPaymentReminderService if it exists
        if (class_exists('\App\Services\ComponentPaymentReminderService')) {
            $this->app->singleton(\App\Services\ComponentPaymentReminderService::class, function ($app) {
                return new \App\Services\ComponentPaymentReminderService();
            });
        }

        // Register ComponentPaymentAnalyticsService if it exists
        if (class_exists('\App\Services\ComponentPaymentAnalyticsService')) {
            $this->app->singleton(\App\Services\ComponentPaymentAnalyticsService::class, function ($app) {
                return new \App\Services\ComponentPaymentAnalyticsService();
            });
        }

        // Register SMS and WhatsApp services if they exist
        if (class_exists('\App\Services\SMSService')) {
            $this->app->singleton(\App\Services\SMSService::class, function ($app) {
                return new \App\Services\SMSService();
            });
        }

        if (class_exists('\App\Services\WhatsAppService')) {
            $this->app->singleton(\App\Services\WhatsAppService::class, function ($app) {
                return new \App\Services\WhatsAppService();
            });
        }

        // Register helper if it exists
        if (class_exists('\App\Helpers\PaymentHelper')) {
            $this->app->singleton('payment.helper', function ($app) {
                return new \App\Helpers\PaymentHelper();
            });
        }

        // Register development-specific services
        if ($this->app->environment('local', 'testing')) {
            $this->registerDevelopmentServices();
        }

        // ✅ FIXED: Register Attendance Services with proper dependency injection

        // Register NotificationService first (no dependencies)
        if (class_exists('\App\Services\Attendance\NotificationService')) {
            $this->app->singleton(\App\Services\Attendance\NotificationService::class, function ($app) {
                return new \App\Services\Attendance\NotificationService(
                    $app->make(\App\Services\NotificationService::class)
                );
            });
        }

        // Register ValidationService (no dependencies)
        if (class_exists('\App\Services\Attendance\ValidationService')) {
            $this->app->singleton(\App\Services\Attendance\ValidationService::class, function ($app) {
                return new \App\Services\Attendance\ValidationService();
            });
        }

        // Register AttendanceService (requires NotificationService)
        if (class_exists('\App\Services\Attendance\AttendanceService')) {
            $this->app->singleton(\App\Services\Attendance\AttendanceService::class, function ($app) {
                $notificationService = $app->make(\App\Services\Attendance\NotificationService::class);
                return new \App\Services\Attendance\AttendanceService($notificationService);
            });
        }

        // Register AnalyticsService for Attendance (no dependencies)
        if (class_exists('\App\Services\Attendance\AnalyticsService')) {
            $this->app->singleton(\App\Services\Attendance\AnalyticsService::class, function ($app) {
                return new \App\Services\Attendance\AnalyticsService();
            });
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

        // Register Observers
        if (class_exists('\App\Models\Payment') && class_exists('\App\Observers\PaymentObserver')) {
            \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
        }

        // Register macros
        $this->registerMacros();

        // Share global data with views
        $this->shareGlobalViewData();

        // Disable ONLY_FULL_GROUP_BY for this application
        // Disable ONLY_FULL_GROUP_BY for this application
        try {
            if (config('database.default') === 'mysql') {
                DB::statement("SET sql_mode=(SELECT REPLACE(@@sql_mode,'ONLY_FULL_GROUP_BY',''))");
            }
        } catch (\Exception $e) {
            // Database might not be available yet (e.g. during compilation)
            // We suppress this error to allow commands like route:cache to run
        }

        // Register Health Checks
        if (class_exists(Health::class)) {
            Health::checks([
                UsedDiskSpaceCheck::new(),
                DatabaseCheck::new(),
                DebugModeCheck::new(),
                CacheCheck::new(),
                PingCheck::new()->name('Internet Connection')->url('https://google.com'),
            ]);
        }
    }

    /**
     * Register custom Blade directives for payment system
     */
    private function registerBladeDirectives(): void
    {
        // Payment status badge directive
        Blade::directive('paymentStatus', function ($status) {
            return "<?php echo function_exists('get_payment_status_badge') ? get_payment_status_badge($status) : '<span class=\"badge badge-secondary\">N/A</span>'; ?>";
        });



        // Format currency directive
        Blade::directive('currency', function ($amount) {
            return "<?php echo class_exists('\\App\\Helpers\\PaymentHelper') ? \\App\\Helpers\\PaymentHelper::formatAmount($amount) : '₹' . number_format($amount, 2); ?>";
        });

        // Days overdue formatting directive
        Blade::directive('overdueDays', function ($days) {
            return "<?php echo function_exists('format_overdue_days') ? format_overdue_days($days) : $days . ' days'; ?>";
        });

        // Fee type color directive
        Blade::directive('feeTypeColor', function ($feeType) {
            return "<?php echo function_exists('get_fee_type_color') ? get_fee_type_color($feeType) : 'primary'; ?>";
        });

        // Risk score badge directive
        Blade::directive('riskScore', function ($student) {
            return "<?php 
                if (class_exists('\\App\\Helpers\\PaymentHelper')) {
                    \$risk = \\App\\Helpers\\PaymentHelper::getStudentRiskScore(\$student);
                    \$colors = ['low' => 'success', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                    \$color = \$colors[\$risk['level']] ?? 'secondary';
                    echo '<span class=\"badge badge-' . \$color . '\">' . ucfirst(\$risk['level']) . ' Risk (' . \$risk['score'] . '%)</span>';
                } else {
                    echo '<span class=\"badge badge-secondary\">N/A</span>';
                }
            ?>";
        });

        // Payment priority directive
        Blade::directive('paymentPriority', function ($feeType, $overdueDays) {
            return "<?php 
                if (class_exists('\\App\\Helpers\\PaymentHelper')) {
                    \$priority = \\App\\Helpers\\PaymentHelper::getPaymentPriority(\$feeType, \$overdueDays);
                    \$colors = ['low' => 'info', 'medium' => 'warning', 'high' => 'danger', 'critical' => 'dark'];
                    \$color = \$colors[\$priority] ?? 'secondary';
                    echo '<span class=\"badge badge-' . \$color . '\">' . ucfirst(\$priority) . '</span>';
                } else {
                    echo '<span class=\"badge badge-secondary\">N/A</span>';
                }
            ?>";
        });

        // Late fee calculation directive
        Blade::directive('lateFee', function ($amount, $overdueDays) {
            return "<?php 
                if (class_exists('\\App\\Helpers\\PaymentHelper')) {
                    \$lateFee = \\App\\Helpers\\PaymentHelper::calculateLateFee(\$amount, \$overdueDays);
                    echo \\App\\Helpers\\PaymentHelper::formatAmount(\$lateFee);
                } else {
                    echo '₹0.00';
                }
            ?>";
        });

        // Status badge directive for attendance
        Blade::directive('attendanceStatus', function ($status) {
            return "<?php 
                \$statusColors = [
                    'present' => 'success',
                    'absent' => 'danger', 
                    'late' => 'warning',
                    'excused' => 'info'
                ];
                \$color = \$statusColors[{$status}] ?? 'secondary';
                echo '<span class=\"badge badge-' . \$color . '\">' . ucfirst({$status}) . '</span>';
            ?>";
        });

        // Permission directive
        Blade::directive('permission', function ($permission) {
            return "<?php if(auth()->check() && auth()->user()->can({$permission})): ?>";
        });

        Blade::directive('endpermission', function () {
            return '<?php endif; ?>';
        });

        // Role directive
        Blade::directive('role', function ($role) {
            return "<?php if(auth()->check() && auth()->user()->hasRole({$role})): ?>";
        });

        Blade::directive('endrole', function () {
            return '<?php endif; ?>';
        });

        // Next reminder date directive
        Blade::directive('nextReminderDate', function ($reminderCount, $dueDate) {
            return "<?php 
                if (class_exists('\\App\\Helpers\\PaymentHelper')) {
                    \$nextDate = \\App\\Helpers\\PaymentHelper::getNextReminderDate(\$reminderCount, \\Carbon\\Carbon::parse(\$dueDate));
                    echo \$nextDate->format('d-m-Y');
                } else {
                    echo '-';
                }
            ?>";
        });

        // Collection efficiency directive
        Blade::directive('collectionEfficiency', function ($startDate, $endDate) {
            return "<?php 
                if (class_exists('\\App\\Helpers\\PaymentHelper')) {
                    \$efficiency = \\App\\Helpers\\PaymentHelper::getCollectionEfficiency(
                        \\Carbon\\Carbon::parse(\$startDate), 
                        \\Carbon\\Carbon::parse(\$endDate)
                    );
                    echo \$efficiency['efficiency_percentage'] . '%';
                } else {
                    echo '0%';
                }
            ?>";
        });

        // Student contact info directive
        Blade::directive('studentContact', function ($student) {
            return "<?php 
                \$contacts = [];
                if (isset(\$student->student_mobile) && \$student->student_mobile) {
                    \$contacts[] = '<a href=\"tel:' . \$student->student_mobile . '\" class=\"btn btn-sm btn-outline-primary\"><i class=\"fas fa-phone\"></i></a>';
                }
                if (isset(\$student->email) && \$student->email) {
                    \$contacts[] = '<a href=\"mailto:' . \$student->email . '\" class=\"btn btn-sm btn-outline-info\"><i class=\"fas fa-envelope\"></i></a>';
                }
                if (isset(\$student->father_mobile) && \$student->father_mobile) {
                    \$contacts[] = '<a href=\"tel:' . \$student->father_mobile . '\" class=\"btn btn-sm btn-outline-secondary\" title=\"Father\"><i class=\"fas fa-phone\"></i></a>';
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
                \$icon = \$icons[\$feeType] ?? 'fas fa-money-bill';
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
                \$icon = \$icons[\$channel] ?? 'fas fa-bell';
                \$color = \$colors[\$channel] ?? 'secondary';
                echo '<i class=\"' . \$icon . ' text-' . \$color . '\"></i>';
            ?>";
        });

        // Dashboard metric card directive
        Blade::directive('metricCard', function ($title, $value, $icon, $color = 'primary') {
            return "<?php 
                echo '<div class=\"card border-left-' . \$color . ' shadow h-100 py-2\">
                    <div class=\"card-body\">
                        <div class=\"row no-gutters align-items-center\">
                            <div class=\"col mr-2\">
                                <div class=\"text-xs font-weight-bold text-' . \$color . ' text-uppercase mb-1\">' . \$title . '</div>
                                <div class=\"h5 mb-0 font-weight-bold text-gray-800\">' . \$value . '</div>
                            </div>
                            <div class=\"col-auto\">
                                <i class=\"' . \$icon . ' fa-2x text-gray-300\"></i>
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


        // Share fee categories with payment views (with safety checks)
        View::composer('admin.payment*', function ($view) {
            try {
                if (class_exists('\App\Models\FeeCategory')) {
                    $feeCategories = Cache::remember('fee_categories_all', 300, function () {
                        return \App\Models\FeeCategory::orderBy('name')->get();
                    });
                    $view->with('feeCategories', $feeCategories);
                }
            } catch (\Exception $e) {
                $view->with('feeCategories', collect([]));
            }
        });


    }

    /**
     * Share global data with all views
     */
    private function shareGlobalData(): void
    {
        try {
            // Share payment reminder configuration (with fallback)
            $paymentConfig = config('payment_reminders', []);
            View::share('paymentConfig', $paymentConfig);

            // Share currency settings
            $currency = function_exists('setting') ? setting('currency', 'INR') : 'INR';
            View::share('currency', $currency);

            $currencySymbol = match ($currency) {
                'INR' => '₹',
                'USD' => '$',
                'EUR' => '€',
                'GBP' => '£',
                default => '₹'
            };
            View::share('currencySymbol', $currencySymbol);

            // Share date format settings
            $dateFormat = function_exists('setting') ? setting('date_format', 'd-m-Y') : 'd-m-Y';
            View::share('dateFormat', $dateFormat);

            // Share app information
            $appName = function_exists('setting') ? setting('app_name', 'College Management System') : 'College Management System';
            $appTagline = function_exists('setting') ? setting('app_tagline', 'Empowering Education Excellence') : 'Empowering Education Excellence';

            View::share('appName', $appName);
            View::share('appTagline', $appTagline);
        } catch (\Exception $e) {
            // Suppress error during boot if DB is unavailable
            \Log::warning('AppServiceProvider: Failed to share global data (DB might be offline): ' . $e->getMessage());
        }
    }

    /**
     * Share global data with views
     */
    private function shareGlobalViewData(): void
    {
        View::composer('*', function ($view) {
            // Share attendance statuses globally
            $view->with('attendanceStatuses', [
                'present' => ['label' => 'Present', 'color' => 'success', 'icon' => 'check-circle'],
                'absent' => ['label' => 'Absent', 'color' => 'danger', 'icon' => 'x-circle'],
                'late' => ['label' => 'Late', 'color' => 'warning', 'icon' => 'clock'],
                'excused' => ['label' => 'Excused', 'color' => 'info', 'icon' => 'info-circle'],
            ]);
        });

        // Share navigation data for attendance modules
        View::composer(['layouts.app', 'layouts.admin'], function ($view) {
            if (auth()->check()) {
                $user = auth()->user();
                $attendanceNavData = [
                    'can_view_attendance' => $user->can('view attendance'),
                    'can_take_attendance' => $user->can('take attendance'),
                    'can_manage_attendance' => $user->can('manage attendance'),
                    'is_faculty' => $user->hasRole(['faculty', 'staff']),
                    'is_student' => $user->hasRole('student'),
                    'is_admin' => $user->hasRole(['admin', 'super-admin']),
                ];

                $view->with('attendanceNav', $attendanceNavData);
            }
        });
    }

    /**
     * Register custom validation rules
     */
    private function registerValidationRules(): void
    {
        // Validate fee type
        \Validator::extend('valid_fee_type', function ($attribute, $value, $parameters, $validator) {
            return class_exists('\App\Models\FeeCategory') ?
                \App\Models\FeeCategory::where('category_type', $value)->exists() : true;
        });

        // Validate reminder channel
        \Validator::extend('valid_reminder_channel', function ($attribute, $value, $parameters, $validator) {
            $validChannels = ['email', 'sms', 'whatsapp', 'phone_call', 'physical_notice'];
            return in_array($value, $validChannels);
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
        // ✅ SAFE: Only register event listeners if events and listeners exist

        // Listen for student fee paid events
        if (class_exists('\App\Events\StudentFeePaid')) {
            \Event::listen(\App\Events\StudentFeePaid::class, function ($event) {
                if (class_exists('\App\Services\ComponentPaymentReminderService')) {
                    try {
                        $reminderService = app(\App\Services\ComponentPaymentReminderService::class);
                        if (method_exists($reminderService, 'cancelRemindersForStudentFee')) {
                            $reminderService->cancelRemindersForStudentFee($event->studentFee);
                        }
                    } catch (\Exception $e) {
                        // Log error but don't break the application
                        \Log::error('Error canceling reminders for paid fee: ' . $e->getMessage());
                    }
                }
            });
        }

        // Listen for new student fee creation
        if (class_exists('\App\Events\StudentFeeCreated')) {
            \Event::listen(\App\Events\StudentFeeCreated::class, function ($event) {
                if (class_exists('\App\Services\ComponentPaymentReminderService')) {
                    try {
                        $reminderService = app(\App\Services\ComponentPaymentReminderService::class);
                        if (method_exists($reminderService, 'setupComponentReminderSchedule')) {
                            $reminderService->setupComponentReminderSchedule($event->student, $event->studentFee);
                        }
                    } catch (\Exception $e) {
                        // Log error but don't break the application
                        \Log::error('Error setting up reminder schedule: ' . $e->getMessage());
                    }
                }
            });
        }

        // Listen for student status changes
        if (class_exists('\App\Events\StudentStatusChanged')) {
            \Event::listen(\App\Events\StudentStatusChanged::class, function ($event) {
                if ($event->newStatus === 'inactive' || $event->newStatus === 'graduated') {
                    try {
                        if (class_exists('\App\Models\PaymentReminder')) {
                            \App\Models\PaymentReminder::where('student_id', $event->student->id)
                                ->where('status', 'pending')
                                ->update(['status' => 'cancelled']);
                        }
                    } catch (\Exception $e) {
                        \Log::error('Error canceling reminders for status change: ' . $e->getMessage());
                    }
                }
            });
        }

        // Listen for reminder sent events
        if (class_exists('\App\Events\ReminderSent')) {
            \Event::listen(\App\Events\ReminderSent::class, function ($event) {
                try {
                    if (isset($event->reminder->student_fee_id) && $event->reminder->student_fee_id) {
                        StudentFee::where('id', $event->reminder->student_fee_id)
                            ->increment('reminder_sent_count');

                        StudentFee::where('id', $event->reminder->student_fee_id)
                            ->update(['last_reminder_sent_at' => now()]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Error updating reminder count: ' . $e->getMessage());
                }
            });
        }
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
                    return $item instanceof \App\Models\StudentFee &&
                        isset($item->due_date) && $item->due_date < $now &&
                        in_array($item->status ?? 'unpaid', ['unpaid', 'partial', 'overdue']);
                })->count(),
                'upcoming' => $this->filter(function ($item) use ($now) {
                    return $item instanceof \App\Models\StudentFee &&
                        isset($item->due_date) && $item->due_date >= $now &&
                        $item->due_date <= $now->copy()->addDays(7);
                })->count(),
                'overdue_amount' => $this->filter(function ($item) use ($now) {
                    return $item instanceof \App\Models\StudentFee &&
                        isset($item->due_date) && $item->due_date < $now &&
                        in_array($item->status ?? 'unpaid', ['unpaid', 'partial', 'overdue']);
                })->sum(function ($item) {
                    return method_exists($item, 'getRemainingAmount') ?
                        $item->getRemainingAmount() :
                        (($item->amount ?? 0) - ($item->paid_amount ?? 0));
                }),
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
            if (class_exists('\App\Services\SMSService')) {
                $this->app->singleton(\App\Services\SMSService::class, function ($app) {
                    return class_exists('\App\Services\MockSMSService') ?
                        new \App\Services\MockSMSService() :
                        new \App\Services\SMSService();
                });
            }
        }

        // Mock WhatsApp service for testing
        if (config('payment_reminders.development.mock_whatsapp_service', false)) {
            if (class_exists('\App\Services\WhatsAppService')) {
                $this->app->singleton(\App\Services\WhatsAppService::class, function ($app) {
                    return class_exists('\App\Services\MockWhatsAppService') ?
                        new \App\Services\MockWhatsAppService() :
                        new \App\Services\WhatsAppService();
                });
            }
        }

        // Debug toolbar integration
        if (class_exists('\Barryvdh\Debugbar\ServiceProvider')) {
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
