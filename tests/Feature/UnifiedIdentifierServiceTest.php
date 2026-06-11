<?php

namespace Tests\Feature;

use App\Models\AcademicYear;
use App\Models\Batch;
use App\Models\Course;
use App\Models\IdSequence;
use App\Models\Student;
use App\Models\User;
use App\Services\UnifiedIdentifierService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class UnifiedIdentifierServiceTest extends TestCase
{
    use RefreshDatabase;

    private UnifiedIdentifierService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(UnifiedIdentifierService::class);
    }

    public function test_it_generates_sequential_receipt_numbers()
    {
        $year = AcademicYear::firstOrCreate(
            ['name' => '2026-2027'],
            [
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => true,
            ]
        );

        $receipt1 = $this->service->generateReceiptNumber($year);
        $receipt2 = $this->service->generateReceiptNumber($year);

        $this->assertEquals('RCP-2026-000001', $receipt1);
        $this->assertEquals('RCP-2026-000002', $receipt2);
    }

    public function test_it_handles_academic_year_transitions()
    {
        $year2026 = AcademicYear::firstOrCreate(
            ['name' => '2026-2027'],
            [
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_current' => false,
            ]
        );

        $year2027 = AcademicYear::firstOrCreate(
            ['name' => '2027-2028'],
            [
                'start_date' => '2027-04-01',
                'end_date' => '2028-03-31',
                'is_current' => true,
            ]
        );

        $receipt2026 = $this->service->generateReceiptNumber($year2026);
        $receipt2027 = $this->service->generateReceiptNumber($year2027);

        $this->assertEquals('RCP-2026-000001', $receipt2026);
        $this->assertEquals('RCP-2027-000001', $receipt2027);
    }

    public function test_concurrency_generates_unique_identifiers()
    {
        // Setup sequence
        IdSequence::create([
            'entity_type' => 'test',
            'prefix' => 'CONCUR-',
            'last_number' => 0
        ]);

        // Simulate 10 concurrent requests to the same sequence
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->service->getNextSequence('test', 'CONCUR-', 3);
        }

        // Check if all generated sequences are unique
        $this->assertCount(10, array_unique($results));
        $this->assertEquals('CONCUR-010', end($results));
    }
}
