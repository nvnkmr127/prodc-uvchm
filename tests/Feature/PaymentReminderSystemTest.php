<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Student;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\FeeCategory;
use App\Models\Batch;
use App\Models\Course;
use App\Models\PaymentReminder;
use App\Models\PaymentDefaulter;
use App\Models\User;
use App\Services\PaymentReminderService;
use App\Services\PaymentAnalyticsService;
use App\Helpers\PaymentHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Carbon\Carbon;

class PaymentReminderSystemTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected $reminderService;
    protected $analyticsService;
    protected $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->reminderService = app(PaymentReminderService::class);
        $this->analyticsService = app(PaymentAnalyticsService::class);
        
        // Create a test user with permissions
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
        
        // Create basic test data
        $this->createTestData();
    }

    /**
     * Create basic test data for all tests
     */
    protected function createTestData(): void
    {
        // Create course and batch
        $course = Course::factory()->create(['name' => 'Computer Science']);
        $batch = Batch::factory()->create(['course_id' => $course->id, 'name' => 'CS-2024']);
        
        // Create fee categories
        FeeCategory::factory()->create(['name' => 'Tuition Fee', 'category_type' => 'tuition_fee']);
        FeeCategory::factory()->create(['name' => 'Uniform Fee', 'category_type' => 'uniform_fee']);
        FeeCategory::factory()->create(['name' => 'Library Fee', 'category_type' => 'library_fee']);
    }

    /** @test */
    public function it_can_identify_unpaid_students_by_fee_type()
    {
        // Create student with unpaid tuition fee
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        $feeCategory = FeeCategory::where('category_type', 'tuition_fee')->first();
        
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(10),
            'due_amount' => 5000
        ]);

        $unpaidStudents = $this->reminderService->getUnpaidStudentsByFeeType('tuition_fee');
        
        $this->assertCount(1, $unpaidStudents);
        $this->assertEquals($student->id, $unpaidStudents[0]['student']->id);
        $this->assertEquals(5000, $unpaidStudents[0]['unpaid_amount']);
        $this->assertEquals(10, $unpaidStudents[0]['overdue_days']);
    }

    /** @test */
    public function it_can_generate_comprehensive_defaulter_list()
    {
        // Create multiple students with different overdue scenarios
        $students = Student::factory()->count(3)->create(['batch_id' => Batch::first()->id]);
        
        // Mild defaulter (15 days overdue)
        Invoice::factory()->create([
            'student_id' => $students[0]->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(20),
            'due_amount' => 3000
        ]);
        
        // Moderate defaulter (35 days overdue)
        Invoice::factory()->create([
            'student_id' => $students[1]->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(35),
            'due_amount' => 8000
        ]);
        
        // Severe defaulter (65 days overdue)
        Invoice::factory()->create([
            'student_id' => $students[2]->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(65),
            'due_amount' => 15000
        ]);

        $defaulters = $this->reminderService->generateDefaultersList();
        
        $this->assertCount(3, $defaulters);
        
        // Check categorization
        $categories = array_column($defaulters, 'defaulter_category');
        $this->assertContains('mild', $categories);
        $this->assertContains('moderate', $categories);
        $this->assertContains('severe', $categories);
        
        // Check sorting by amount (highest first)
        $amounts = array_column($defaulters, 'total_overdue_amount');
        $this->assertEquals(15000, $amounts[0]); // Highest amount first
    }

    /** @test */
    public function it_can_categorize_defaulters_correctly()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Create chronic defaulter scenario (100+ days overdue, high amount)
        Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(100),
            'due_amount' => 25000
        ]);

        $defaulters = $this->reminderService->generateDefaultersList();
        
        $this->assertCount(1, $defaulters);
        $this->assertEquals('chronic', $defaulters[0]['defaulter_category']);
        $this->assertEquals(100, $defaulters[0]['overdue_days']);
        $this->assertEquals(25000, $defaulters[0]['total_overdue_amount']);
    }

    /** @test */
    public function it_can_calculate_collection_statistics()
    {
        $students = Student::factory()->count(3)->create(['batch_id' => Batch::first()->id]);
        
        // Create paid invoice
        $paidInvoice = Invoice::factory()->create([
            'student_id' => $students[0]->id,
            'status' => 'paid',
            'total_amount' => 10000,
            'paid_amount' => 10000,
            'due_amount' => 0
        ]);
        
        // Create unpaid invoice
        Invoice::factory()->create([
            'student_id' => $students[1]->id,
            'status' => 'unpaid',
            'total_amount' => 8000,
            'paid_amount' => 0,
            'due_amount' => 8000
        ]);
        
        // Create partially paid invoice
        Invoice::factory()->create([
            'student_id' => $students[2]->id,
            'status' => 'partial',
            'total_amount' => 12000,
            'paid_amount' => 5000,
            'due_amount' => 7000
        ]);

        $stats = $this->reminderService->getFeeCollectionStats();
        
        $this->assertEquals(30000, $stats['total_amount']); // Total invoiced
        $this->assertEquals(15000, $stats['collected_amount']); // Total collected
        $this->assertEquals(15000, $stats['pending_amount']); // Total pending
        $this->assertEquals(3, $stats['total_students']);
        $this->assertEquals(1, $stats['paid_students']);
        $this->assertEquals(1, $stats['unpaid_students']);
        $this->assertEquals(1, $stats['partial_paid_students']);
        $this->assertEquals(50.0, $stats['collection_percentage']);
    }

    /** @test */
    public function it_can_setup_reminder_schedule_for_new_invoice()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'unpaid',
            'due_date' => now()->addDays(10)
        ]);

        $this->reminderService->setupReminderSchedule($student, $invoice);

        $reminders = PaymentReminder::where('student_id', $student->id)
            ->where('invoice_id', $invoice->id)
            ->get();

        $this->assertGreaterThan(0, $reminders->count());
        
        // Check that reminders are scheduled for different dates
        $scheduledDates = $reminders->pluck('scheduled_date')->unique();
        $this->assertGreaterThan(1, $scheduledDates->count());
        
        // Check that upcoming due reminders are scheduled
        $upcomingReminders = $reminders->where('reminder_type', 'upcoming_due');
        $this->assertGreaterThan(0, $upcomingReminders->count());
    }

    /** @test */
    public function it_can_process_pending_reminders()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Create pending reminders scheduled for today
        PaymentReminder::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'pending',
            'scheduled_date' => now()->toDateString(),
            'channel' => 'email'
        ]);
        
        // Create future reminders (should not be processed)
        PaymentReminder::factory()->create([
            'student_id' => $student->id,
            'status' => 'pending',
            'scheduled_date' => now()->addDays(5)->toDateString(),
            'channel' => 'email'
        ]);

        $results = $this->reminderService->processPendingReminders();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('sent', $results);
        $this->assertArrayHasKey('failed', $results);
        
        // Should process 3 reminders scheduled for today
        $this->assertGreaterThanOrEqual(0, $results['sent'] + $results['failed']);
    }

    /** @test */
    public function it_can_calculate_payment_priority()
    {
        // Test tuition fee with high overdue days
        $priority = PaymentHelper::getPaymentPriority('tuition_fee', 70);
        $this->assertEquals('critical', $priority);
        
        // Test uniform fee with moderate overdue days
        $priority = PaymentHelper::getPaymentPriority('uniform_fee', 20);
        $this->assertEquals('low', $priority);
        
        // Test library fee with high overdue days
        $priority = PaymentHelper::getPaymentPriority('library_fee', 40);
        $this->assertEquals('medium', $priority);
    }

    /** @test */
    public function it_can_calculate_late_fees_correctly()
    {
        // Test within grace period
        $lateFee = PaymentHelper::calculateLateFee(10000, 5); // 5 days, within 7-day grace
        $this->assertEquals(0, $lateFee);
        
        // Test after grace period
        $lateFee = PaymentHelper::calculateLateFee(10000, 10); // 10 days overdue
        $this->assertEquals(500, $lateFee); // 5% of 10000
        
        // Test chronic defaulter multiplier
        $lateFee = PaymentHelper::calculateLateFee(10000, 100); // 100 days overdue
        $this->assertEquals(1000, $lateFee); // 5% * 2 (chronic multiplier)
    }

    /** @test */
    public function it_can_format_amounts_correctly()
    {
        $this->assertEquals('₹500.00', PaymentHelper::formatAmount(500));
        $this->assertEquals('₹5.0K', PaymentHelper::formatAmount(5000));
        $this->assertEquals('₹1.50 L', PaymentHelper::formatAmount(150000));
        $this->assertEquals('₹2.50 Cr', PaymentHelper::formatAmount(25000000));
    }

    /** @test */
    public function it_can_calculate_student_risk_score()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Create overdue invoices
        Invoice::factory()->count(2)->create([
            'student_id' => $student->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(30),
            'due_amount' => 5000
        ]);
        
        // Create paid invoice
        Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'paid',
            'total_amount' => 8000,
            'paid_amount' => 8000
        ]);

        $riskScore = PaymentHelper::getStudentRiskScore($student);
        
        $this->assertIsArray($riskScore);
        $this->assertArrayHasKey('score', $riskScore);
        $this->assertArrayHasKey('level', $riskScore);
        $this->assertArrayHasKey('factors', $riskScore);
        $this->assertArrayHasKey('recommendations', $riskScore);
        
        $this->assertGreaterThan(0, $riskScore['score']);
        $this->assertContains($riskScore['level'], ['minimal', 'low', 'medium', 'high', 'critical']);
    }

    /** @test */
    public function it_can_generate_reminder_messages()
    {
        $data = [
            'name' => 'John Doe',
            'fee_type' => 'Tuition Fee',
            'amount' => '₹10,000',
            'due_date' => '31-12-2024',
            'days_overdue' => 15,
            'college_name' => 'Test College'
        ];

        $message = PaymentHelper::generateReminderMessage('upcoming_due_sms', $data);
        
        $this->assertStringContainsString('John Doe', $message);
        $this->assertStringContainsString('Tuition Fee', $message);
        $this->assertStringContainsString('₹10,000', $message);
        $this->assertStringContainsString('31-12-2024', $message);
        $this->assertStringContainsString('Test College', $message);
    }

    /** @test */
    public function it_can_calculate_collection_efficiency()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        $startDate = now()->startOfMonth();
        $endDate = now()->endOfMonth();
        
        // Create invoices within the period
        Invoice::factory()->create([
            'student_id' => $student->id,
            'issue_date' => $startDate->addDays(5),
            'total_amount' => 10000,
            'status' => 'paid',
            'paid_amount' => 10000
        ]);
        
        Invoice::factory()->create([
            'student_id' => $student->id,
            'issue_date' => $startDate->addDays(10),
            'total_amount' => 5000,
            'status' => 'unpaid',
            'due_amount' => 5000
        ]);
        
        // Create corresponding payment
        Payment::factory()->create([
            'student_id' => $student->id,
            'payment_date' => $startDate->addDays(6),
            'amount' => 10000
        ]);

        $efficiency = PaymentHelper::getCollectionEfficiency($startDate, $endDate);
        
        $this->assertIsArray($efficiency);
        $this->assertArrayHasKey('amounts', $efficiency);
        $this->assertArrayHasKey('percentages', $efficiency);
        
        $this->assertEquals(15000, $efficiency['amounts']['total_invoiced']);
        $this->assertEquals(10000, $efficiency['amounts']['total_collected']);
        $this->assertEquals(5000, $efficiency['amounts']['total_pending']);
        $this->assertEquals(66.67, $efficiency['percentages']['efficiency_percentage']);
    }

    /** @test */
    public function it_can_get_payment_behavior_insights()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Create invoices with different payment patterns
        $invoices = Invoice::factory()->count(5)->create([
            'student_id' => $student->id,
            'due_date' => now()->subDays(30)
        ]);
        
        // Create payments - some early, some late
        Payment::factory()->create([
            'student_id' => $student->id,
            'invoice_id' => $invoices[0]->id,
            'payment_date' => now()->subDays(35), // 5 days early
            'amount' => 5000
        ]);
        
        Payment::factory()->create([
            'student_id' => $student->id,
            'invoice_id' => $invoices[1]->id,
            'payment_date' => now()->subDays(25), // 5 days late
            'amount' => 5000
        ]);
        
        Payment::factory()->create([
            'student_id' => $student->id,
            'invoice_id' => $invoices[2]->id,
            'payment_date' => now()->subDays(30), // On time
            'amount' => 5000
        ]);

        $insights = PaymentHelper::getPaymentBehaviorInsights($student);
        
        $this->assertIsArray($insights);
        $this->assertArrayHasKey('behavior_type', $insights);
        $this->assertArrayHasKey('statistics', $insights);
        $this->assertArrayHasKey('insights', $insights);
        $this->assertArrayHasKey('recommendations', $insights);
        
        $this->assertEquals(3, $insights['statistics']['total_payments']);
        $this->assertEquals(1, $insights['statistics']['early_payments']);
        $this->assertEquals(1, $insights['statistics']['on_time_payments']);
        $this->assertEquals(1, $insights['statistics']['late_payments']);
    }

    /** @test */
    public function it_can_get_payment_performance_score()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Create good payment history
        Invoice::factory()->count(3)->create([
            'student_id' => $student->id,
            'status' => 'paid',
            'total_amount' => 5000,
            'paid_amount' => 5000
        ]);
        
        // Create one overdue invoice
        Invoice::factory()->create([
            'student_id' => $student->id,
            'status' => 'unpaid',
            'due_date' => now()->subDays(10),
            'due_amount' => 3000
        ]);

        $performance = PaymentHelper::getPaymentPerformanceScore($student);
        
        $this->assertIsArray($performance);
        $this->assertArrayHasKey('score', $performance);
        $this->assertArrayHasKey('grade', $performance);
        $this->assertArrayHasKey('total_invoices', $performance);
        
        $this->assertEquals(4, $performance['total_invoices']);
        $this->assertEquals(3, $performance['paid_invoices']);
        $this->assertEquals(1, $performance['overdue_invoices']);
        $this->assertGreaterThan(0, $performance['score']);
        $this->assertLessThan(100, $performance['score']); // Should be less than 100 due to overdue
    }

    /** @test */
    public function it_can_generate_dashboard_statistics()
    {
        // Create test data for dashboard
        $students = Student::factory()->count(5)->create(['batch_id' => Batch::first()->id]);
        
        // Create some payments for today
        Payment::factory()->count(3)->create([
            'student_id' => $students[0]->id,
            'payment_date' => now(),
            'amount' => 2000
        ]);
        
        // Create some reminders for today
        PaymentReminder::factory()->count(2)->create([
            'student_id' => $students[1]->id,
            'sent_at' => now(),
            'status' => 'sent'
        ]);

        $stats = PaymentHelper::getDashboardStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('today', $stats);
        $this->assertArrayHasKey('this_month', $stats);
        $this->assertArrayHasKey('overview', $stats);
        
        $this->assertEquals(6000, $stats['today']['collections']); // 3 * 2000
        $this->assertEquals(2, $stats['today']['reminders_sent']);
    }

    /** @test */
    public function it_can_get_fee_type_statistics()
    {
        // Create invoices for different fee types
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        $tuitionCategory = FeeCategory::where('category_type', 'tuition_fee')->first();
        $uniformCategory = FeeCategory::where('category_type', 'uniform_fee')->first();
        
        // Create tuition fee invoice (paid)
        $tuitionInvoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'total_amount' => 10000,
            'status' => 'paid',
            'paid_amount' => 10000
        ]);
        
        // Create uniform fee invoice (unpaid)
        $uniformInvoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'total_amount' => 3000,
            'status' => 'unpaid',
            'due_amount' => 3000
        ]);

        $stats = PaymentHelper::getFeeTypeStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('tuition_fee', $stats);
        $this->assertArrayHasKey('uniform_fee', $stats);
        
        // Check tuition fee stats
        $this->assertEquals('Tuition Fee', $stats['tuition_fee']['name']);
        $this->assertEquals(100, $stats['tuition_fee']['collection_rate']); // 100% paid
        
        // Check uniform fee stats
        $this->assertEquals('Uniform Fee', $stats['uniform_fee']['name']);
        $this->assertEquals(0, $stats['uniform_fee']['collection_rate']); // 0% paid
    }

    /** @test */
    public function it_can_categorize_defaulters_using_helper()
    {
        // Test mild defaulter
        $category = PaymentHelper::categorizeDefaulter(3000, 20, 1);
        $this->assertEquals('mild', $category);
        
        // Test moderate defaulter by days
        $category = PaymentHelper::categorizeDefaulter(5000, 35, 2);
        $this->assertEquals('moderate', $category);
        
        // Test severe defaulter by amount
        $category = PaymentHelper::categorizeDefaulter(30000, 45, 3);
        $this->assertEquals('severe', $category);
        
        // Test chronic defaulter by days
        $category = PaymentHelper::categorizeDefaulter(20000, 95, 4);
        $this->assertEquals('chronic', $category);
    }

    /** @test */
    public function it_can_generate_csv_headers_for_different_reports()
    {
        $defaulterHeaders = PaymentHelper::generateCSVHeaders('defaulters');
        $this->assertContains('Student Name', $defaulterHeaders);
        $this->assertContains('Overdue Amount', $defaulterHeaders);
        $this->assertContains('Risk Score', $defaulterHeaders);
        
        $collectionHeaders = PaymentHelper::generateCSVHeaders('collections');
        $this->assertContains('Date', $collectionHeaders);
        $this->assertContains('Amount', $collectionHeaders);
        $this->assertContains('Payment Method', $collectionHeaders);
        
        $reminderHeaders = PaymentHelper::generateCSVHeaders('reminders');
        $this->assertContains('Reminder Type', $reminderHeaders);
        $this->assertContains('Channel', $reminderHeaders);
        $this->assertContains('Status', $reminderHeaders);
    }

    /** @test */
    public function it_can_format_csv_rows_correctly()
    {
        $defaulterData = [
            'student_name' => 'John Doe',
            'enrollment_number' => 'STD001',
            'course' => 'Computer Science',
            'batch' => 'CS-2024',
            'overdue_amount' => 15000,
            'days_overdue' => 45,
            'category' => 'moderate',
            'fee_types' => ['tuition_fee', 'library_fee'],
            'contact' => '9876543210',
            'last_payment_date' => '2024-01-15',
            'risk_score' => '67.5'
        ];
        
        $row = PaymentHelper::formatCSVRow($defaulterData, 'defaulters');
        
        $this->assertEquals('John Doe', $row[0]);
        $this->assertEquals('STD001', $row[1]);
        $this->assertEquals('₹15.0K', $row[4]); // Formatted amount
        $this->assertEquals('Moderate', $row[6]); // Capitalized category
        $this->assertEquals('tuition_fee, library_fee', $row[7]); // Joined fee types
    }

    /** @test */
    public function it_can_handle_empty_data_gracefully()
    {
        // Test with no invoices
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        $riskScore = PaymentHelper::getStudentRiskScore($student);
        $this->assertEquals(0, $riskScore['total_invoices']);
        $this->assertEquals('minimal', $riskScore['level']);
        
        $behavior = PaymentHelper::getPaymentBehaviorInsights($student);
        $this->assertEquals('no_payment_history', $behavior['behavior_type']);
        
        $performance = PaymentHelper::getPaymentPerformanceScore($student);
        $this->assertEquals(100, $performance['score']); // Perfect score with no data
        $this->assertEquals('A+', $performance['grade']);
    }

    /** @test */
    public function it_can_handle_edge_cases_in_calculations()
    {
        // Test division by zero scenarios
        $efficiency = PaymentHelper::getCollectionEfficiency(now(), now()->addDay());
        $this->assertEquals(0, $efficiency['percentages']['efficiency_percentage']);
        
        // Test negative amounts (should be treated as 0)
        $formatted = PaymentHelper::formatAmount(-1000);
        $this->assertStringStartsWith('₹', $formatted);
        
        // Test very large amounts
        $formatted = PaymentHelper::formatAmount(150000000); // 15 crores
        $this->assertStringContains('Cr', $formatted);
    }

    /** @test */
    public function it_can_process_batch_wise_performance()
    {
        // Create multiple batches with different performance
        $course1 = Course::factory()->create(['name' => 'Engineering']);
        $course2 = Course::factory()->create(['name' => 'Commerce']);
        
        $batch1 = Batch::factory()->create(['course_id' => $course1->id, 'name' => 'ENG-2024']);
        $batch2 = Batch::factory()->create(['course_id' => $course2->id, 'name' => 'COM-2024']);
        
        // Create students and invoices for each batch
        $student1 = Student::factory()->create(['batch_id' => $batch1->id]);
        $student2 = Student::factory()->create(['batch_id' => $batch2->id]);
        
        // Good performing batch (all paid)
        Invoice::factory()->create([
            'student_id' => $student1->id,
            'total_amount' => 10000,
            'status' => 'paid',
            'paid_amount' => 10000
        ]);
        
        Payment::factory()->create([
            'student_id' => $student1->id,
            'amount' => 10000
        ]);
        
        // Poor performing batch (unpaid)
        Invoice::factory()->create([
            'student_id' => $student2->id,
            'total_amount' => 10000,
            'status' => 'unpaid',
            'due_date' => now()->subDays(30),
            'due_amount' => 10000
        ]);

        $performance = PaymentHelper::getBatchWisePerformance();
        
        $this->assertIsArray($performance);
        $this->assertCount(2, $performance);
        
        // Check that results are sorted by collection rate (best first)
        $this->assertGreaterThan($performance[1]['collection_rate'], $performance[0]['collection_rate']);
        
        // Check performance grades
        $this->assertContains($performance[0]['performance_grade'], ['Excellent', 'Good', 'Average', 'Below Average', 'Poor']);
    }

    /** @test */
    public function it_can_generate_payment_forecast()
    {
        // Create historical payment data
        $students = Student::factory()->count(2)->create(['batch_id' => Batch::first()->id]);
        
        // Create payments for last 6 months with increasing trend
        for ($i = 6; $i >= 1; $i--) {
            Payment::factory()->create([
                'student_id' => $students[0]->id,
                'payment_date' => now()->subMonths($i),
                'amount' => 5000 + ($i * 1000) // Increasing trend
            ]);
        }

        $forecast = PaymentHelper::generatePaymentForecast(3);
        
        $this->assertIsArray($forecast);
        $this->assertArrayHasKey('historical_data', $forecast);
        $this->assertArrayHasKey('forecast', $forecast);
        $this->assertArrayHasKey('trend', $forecast);
        $this->assertArrayHasKey('confidence', $forecast);
        
        $this->assertCount(3, $forecast['forecast']); // 3 months forecast
        $this->assertContains($forecast['trend'], ['increasing', 'decreasing', 'stable']);
        $this->assertContains($forecast['confidence'], ['high', 'medium', 'low']);
    }

    /** @test */
    public function it_maintains_data_consistency_across_operations()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Create invoice
        $invoice = Invoice::factory()->create([
            'student_id' => $student->id,
            'total_amount' => 10000,
            'status' => 'unpaid',
            'due_date' => now()->addDays(5)
        ]);
        
        // Setup reminders
        $this->reminderService->setupReminderSchedule($student, $invoice);
        
        // Check that reminders are linked to correct student and invoice
        $reminders = PaymentReminder::where('student_id', $student->id)
            ->where('invoice_id', $invoice->id)
            ->get();
        
        $this->assertGreaterThan(0, $reminders->count());
        
        foreach ($reminders as $reminder) {
            $this->assertEquals($student->id, $reminder->student_id);
            $this->assertEquals($invoice->id, $reminder->invoice_id);
            $this->assertInstanceOf(Carbon::class, $reminder->scheduled_date);
        }
        
        // Process reminders and check status updates
        $results = $this->reminderService->processPendingReminders();
        
        $this->assertIsArray($results);
        $this->assertArrayHasKey('sent', $results);
        $this->assertArrayHasKey('failed', $results);
    }

    /** @test */
    public function it_handles_concurrent_operations_safely()
    {
        $student = Student::factory()->create(['batch_id' => Batch::first()->id]);
        
        // Simulate concurrent reminder processing
        $invoice1 = Invoice::factory()->create([
            'student_id' => $student->id,
            'due_date' => now()->subDays(1)
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'student_id' => $student->id,
            'due_date' => now()->subDays(2)
        ]);
        
        // Setup reminders for both invoices
        $this->reminderService->setupReminderSchedule($student, $invoice1);
        $this->reminderService->setupReminderSchedule($student, $invoice2);
        
        // Process reminders multiple times (simulating concurrent access)
        $results1 = $this->reminderService->processPendingReminders();
        $results2 = $this->reminderService->processPendingReminders();
        
        // Ensure no duplicate processing
        $processedReminders = PaymentReminder::where('student_id', $student->id)
            ->where('status', 'sent')
            ->get();
        
        // Each reminder should only be processed once
        $this->assertLessThanOrEqual(
            PaymentReminder::where('student_id', $student->id)->count(),
            $processedReminders->count()
        );
    }

    protected function tearDown(): void
    {
        // Clean up any test data if needed
        PaymentReminder::query()->delete();
        PaymentDefaulter::query()->delete();
        Payment::query()->delete();
        Invoice::query()->delete();
        Student::query()->delete();
        
        parent::tearDown();
    }
}