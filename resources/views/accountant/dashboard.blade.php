{{-- resources/views/accountant/dashboard.blade.php --}}
@extends('layouts.theme')

@section('title', 'Financial Dashboard')

@push('styles')
<style>
.financial-header {
    background: linear-gradient(135deg, #1cc88a 0%, #13855c 100%);
    color: white;
    padding: 2rem 0;
    margin-bottom: 2rem;
    border-radius: 15px;
    position: relative;
}

.financial-header::before {
    content: '';
    position: absolute;
    top: -10%;
    right: -5%;
    width: 150px;
    height: 150px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
}

.financial-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-bottom: 2rem;
}

.financial-card {
    background: white;
    border-radius: 15px;
    padding: 2rem;
    text-align: center;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    transition: all 0.3s ease;
    border-left: 5px solid;
    position: relative;
    overflow: hidden;
}

.financial-card::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 50px;
    height: 50px;
    border-radius: 0 15px 0 50px;
    opacity: 0.1;
}

.financial-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.15);
}

.financial-card.revenue { 
    border-left-color: #1cc88a; 
}
.financial-card.revenue::before { 
    background: #1cc88a; 
}

.financial-card.pending { 
    border-left-color: #f6c23e; 
}
.financial-card.pending::before { 
    background: #f6c23e; 
}

.financial-card.expenses { 
    border-left-color: #e74a3b; 
}
.financial-card.expenses::before { 
    background: #e74a3b; 
}

.financial-card.profit { 
    border-left-color: #36b9cc; 
}
.financial-card.profit::before { 
    background: #36b9cc; 
}

.financial-icon {
    width: 60px;
    height: 60px;
    margin: 0 auto 1rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: white;
    position: relative;
    z-index: 1;
}

.financial-card.revenue .financial-icon { background: linear-gradient(45deg, #1cc88a, #17a673); }
.financial-card.pending .financial-icon { background: linear-gradient(45deg, #f6c23e, #dda20a); }
.financial-card.expenses .financial-icon { background: linear-gradient(45deg, #e74a3b, #c0392b); }
.financial-card.profit .financial-icon { background: linear-gradient(45deg, #36b9cc, #2e8b9b); }

.financial-amount {
    font-size: 2.2rem;
    font-weight: 700;
    margin-bottom: 0.5rem;
    color: #2c3e50;
    position: relative;
    z-index: 1;
}

.financial-label {
    color: #7f8c8d;
    font-size: 0.95rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: relative;
    z-index: 1;
}

.financial-trend {
    margin-top: 1rem;
    font-size: 0.85rem;
    padding: 0.4rem 1rem;
    border-radius: 20px;
    display: inline-block;
    position: relative;
    z-index: 1;
}

.trend-up {
    background: #d4edda;
    color: #155724;
}

.trend-down {
    background: #f8d7da;
    color: #721c24;
}

.trend-stable {
    background: #e2e3e5;
    color: #383d41;
}

.dashboard-layout {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 2rem;
    margin-bottom: 2rem;
}

.main-financial-widgets {
    display: grid;
    gap: 1.5rem;
}

.sidebar-financial-widgets {
    display: grid;
    gap: 1.5rem;
}

.payment-status-indicator {
    display: inline-block;
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 8px;
}

.status-paid { background-color: #28a745; }
.status-pending { background-color: #ffc107; }
.status-overdue { background-color: #dc3545; }
.status-partial { background-color: #17a2b8; }

.defaulter-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    margin-bottom: 0.8rem;
    background: white;
    border-radius: 10px;
    border-left: 4px solid #dc3545;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: all 0.2s ease;
}

.defaulter-item:hover {
    transform: translateX(5px);
    box-shadow: 0 4px 15px rgba(0,0,0,0.15);
}

.defaulter-info h6 {
    margin: 0 0 0.3rem 0;
    color: #2c3e50;
}

.defaulter-amount {
    font-weight: bold;
    color: #dc3545;
}

.defaulter-days {
    font-size: 0.8rem;
    color: #6c757d;
}

.expense-category {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0.8rem 0;
    border-bottom: 1px solid #f1f3f4;
}

.expense-category:last-child {
    border-bottom: none;
}

.expense-name {
    font-weight: 600;
    color: #2c3e50;
}

.expense-amount {
    font-weight: bold;
    color: #e74a3b;
}

.expense-percentage {
    font-size: 0.8rem;
    color: #6c757d;
}

.financial-summary-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
}

.summary-item {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 10px;
    text-align: center;
    border-top: 3px solid #dee2e6;
}

.summary-item.positive {
    border-top-color: #28a745;
    background: #f8fff9;
}

.summary-item.negative {
    border-top-color: #dc3545;
    background: #fff8f8;
}

.summary-value {
    font-size: 1.8rem;
    font-weight: bold;
    margin-bottom: 0.5rem;
}

.summary-label {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 600;
}

@media (max-width: 768px) {
    .dashboard-layout {
        grid-template-columns: 1fr;
    }
    
    .financial-overview {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .financial-amount {
        font-size: 1.8rem;
    }
    
    .financial-icon {
        width: 50px;
        height: 50px;
        font-size: 1.2rem;
    }
    
    .financial-summary-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Financial Header -->
    <div class="financial-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="mb-2">
                        <i class="fas fa-chart-line mr-3"></i>Financial Dashboard
                    </h1>
                    <p class="mb-0 opacity-75">
                        Complete financial overview and management center
                    </p>
                </div>
                <div class="col-md-4 text-md-right">
                    <div class="financial-period">
                        <div class="text-sm opacity-75">Financial Year</div>
                        <div class="h5 mb-0">{{ $dashboard_data['financial_year'] ?? '2024-25' }}</div>
                        <div class="text-sm opacity-75">{{ $dashboard_data['current_month'] ?? date('F Y') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Financial Overview Cards -->
    <div class="financial-overview">
        <div class="financial-card revenue">
            <div class="financial-icon">
                <i class="fas fa-rupee-sign"></i>
            </div>
            <div class="financial-amount">₹{{ number_format($dashboard_data['total_revenue'] ?? 0) }}</div>
            <div class="financial-label">Total Revenue</div>
            <div class="financial-trend trend-{{ ($dashboard_data['revenue_trend'] ?? 0) >= 0 ? 'up' : 'down' }}">
                <i class="fas fa-arrow-{{ ($dashboard_data['revenue_trend'] ?? 0) >= 0 ? 'up' : 'down' }} mr-1"></i>
                {{ abs($dashboard_data['revenue_trend'] ?? 0) }}% this month
            </div>
        </div>

        <div class="financial-card pending">
            <div class="financial-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="financial-amount">₹{{ number_format($dashboard_data['pending_amount'] ?? 0) }}</div>
            <div class="financial-label">Pending Collections</div>
            <div class="financial-trend trend-{{ ($dashboard_data['pending_trend'] ?? 0) <= 0 ? 'up' : 'down' }}">
                <i class="fas fa-users mr-1"></i>
                {{ $dashboard_data['pending_invoices'] ?? 0 }} invoices pending
            </div>
        </div>

        <div class="financial-card expenses">
            <div class="financial-icon">
                <i class="fas fa-money-bill-wave"></i>
            </div>
            <div class="financial-amount">₹{{ number_format($dashboard_data['total_expenses'] ?? 0) }}</div>
            <div class="financial-label">Total Expenses</div>
            <div class="financial-trend trend-{{ ($dashboard_data['expense_trend'] ?? 0) <= 0 ? 'up' : 'down' }}">
                <i class="fas fa-arrow-{{ ($dashboard_data['expense_trend'] ?? 0) <= 0 ? 'down' : 'up' }} mr-1"></i>
                {{ abs($dashboard_data['expense_trend'] ?? 0) }}% vs last month
            </div>
        </div>

        <div class="financial-card profit">
            <div class="financial-icon">
                <i class="fas fa-chart-pie"></i>
            </div>
            <div class="financial-amount">₹{{ number_format(($dashboard_data['total_revenue'] ?? 0) - ($dashboard_data['total_expenses'] ?? 0)) }}</div>
            <div class="financial-label">Net Profit</div>
            <div class="financial-trend trend-{{ (($dashboard_data['total_revenue'] ?? 0) - ($dashboard_data['total_expenses'] ?? 0)) >= 0 ? 'up' : 'down' }}">
                <i class="fas fa-percentage mr-1"></i>
                {{ round((($dashboard_data['total_revenue'] ?? 1) > 0 ? ((($dashboard_data['total_revenue'] ?? 0) - ($dashboard_data['total_expenses'] ?? 0)) / ($dashboard_data['total_revenue'] ?? 1)) * 100 : 0), 1) }}% margin
            </div>
        </div>
    </div>

    <!-- Main Dashboard Layout -->
    <div class="dashboard-layout">
        <!-- Main Financial Widgets -->
        <div class="main-financial-widgets">
            <!-- Revenue Chart Widget -->
            @include('dashboard.widgets.revenue-chart', [
                'widget' => (object)['instance_id' => 'accountant-revenue'],
                'data' => $dashboard_data['revenue_chart'] ?? []
            ])

            <!-- Fee Collection Status Widget -->
            @include('dashboard.widgets.fee-collection-status', [
                'widget' => (object)['instance_id' => 'accountant-fees'],
                'data' => $dashboard_data['fee_collection'] ?? []
            ])

            <!-- Monthly Financial Summary -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-calendar-alt mr-2"></i>Monthly Financial Summary
                    </h6>
                    <div class="dropdown">
                        <button class="btn btn-outline-primary btn-sm dropdown-toggle" type="button" data-toggle="dropdown">
                            {{ $dashboard_data['current_month'] ?? date('F Y') }}
                        </button>
                        <div class="dropdown-menu">
                            <a class="dropdown-item" href="#">This Month</a>
                            <a class="dropdown-item" href="#">Last Month</a>
                            <a class="dropdown-item" href="#">Last 3 Months</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <canvas id="monthlyRevenueChart" height="200"></canvas>
                        </div>
                        <div class="col-md-6">
                            <div class="monthly-breakdown">
                                <h6 class="text-muted mb-3">Revenue Breakdown</h6>
                                
                                @if(isset($dashboard_data['revenue_breakdown']) && count($dashboard_data['revenue_breakdown']) > 0)
                                    @foreach($dashboard_data['revenue_breakdown'] as $breakdown)
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <div class="d-flex align-items-center">
                                                <div class="payment-status-indicator status-{{ $breakdown['status'] ?? 'paid' }}"></div>
                                                <span class="font-weight-bold">{{ $breakdown['category'] }}</span>
                                            </div>
                                            <div class="text-right">
                                                <div class="font-weight-bold text-success">₹{{ number_format($breakdown['amount']) }}</div>
                                                <small class="text-muted">{{ $breakdown['percentage'] ?? 0 }}%</small>
                                            </div>
                                        </div>
                                    @endforeach
                                @else
                                    <div class="text-center py-3">
                                        <i class="fas fa-chart-pie fa-2x text-gray-300 mb-2"></i>
                                        <div class="text-muted">No revenue data</div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Financial Widgets -->
        <div class="sidebar-financial-widgets">
            <!-- Quick Financial Actions -->
            @include('dashboard.widgets.quick-actions', [
                'widget' => (object)['instance_id' => 'accountant-actions'],
                'data' => [
                    'actions' => [
                        ['name' => 'Create Invoice', 'icon' => 'file-invoice', 'route' => 'invoices.create', 'color' => 'primary', 'permission' => 'create invoices'],
                        ['name' => 'Record Payment', 'icon' => 'money-bill', 'route' => 'payments.create', 'color' => 'success', 'permission' => 'record payments'],
                        ['name' => 'Expense Entry', 'icon' => 'receipt', 'route' => 'expenses.create', 'color' => 'warning', 'permission' => 'manage expenses'],
                        ['name' => 'Financial Report', 'icon' => 'chart-bar', 'route' => 'reports.financial', 'color' => 'info', 'permission' => 'view reports'],
                        ['name' => 'Bank Reconciliation', 'icon' => 'university', 'route' => 'banking.reconcile', 'color' => 'secondary', 'permission' => 'bank reconciliation'],
                        ['name' => 'Send Reminders', 'icon' => 'bell', 'route' => 'reminders.send', 'color' => 'danger', 'permission' => 'send reminders']
                    ]
                ]
            ])

            <!-- Payment Defaulters -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Payment Defaulters
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($dashboard_data['defaulters']) && count($dashboard_data['defaulters']) > 0)
                        <div class="defaulters-list" style="max-height: 300px; overflow-y: auto;">
                            @foreach($dashboard_data['defaulters'] as $defaulter)
                                <div class="defaulter-item">
                                    <div class="defaulter-info">
                                        <h6>{{ $defaulter['name'] }}</h6>
                                        <div class="defaulter-days">
                                            {{ $defaulter['days_overdue'] }} days overdue
                                        </div>
                                    </div>
                                    <div class="defaulter-amount">
                                        ₹{{ number_format($defaulter['amount']) }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('payment-defaulters.index') }}" class="btn btn-outline-danger btn-sm">
                                <i class="fas fa-list mr-1"></i>
                                Manage All Defaulters
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-check-circle fa-2x text-success mb-2"></i>
                            <div class="text-muted">No payment defaulters</div>
                            <div class="text-sm text-success">All payments are up to date!</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Expense Categories -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-tags mr-2"></i>Expense Categories
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($dashboard_data['expense_categories']) && count($dashboard_data['expense_categories']) > 0)
                        @foreach($dashboard_data['expense_categories'] as $category)
                            <div class="expense-category">
                                <div>
                                    <div class="expense-name">{{ $category['name'] }}</div>
                                    <div class="expense-percentage">{{ $category['percentage'] ?? 0 }}% of total</div>
                                </div>
                                <div class="expense-amount">₹{{ number_format($category['amount']) }}</div>
                            </div>
                        @endforeach
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('expenses.categories') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-cog mr-1"></i>
                                Manage Categories
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-tags fa-2x text-gray-300 mb-2"></i>
                            <div class="text-muted">No expense data</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-history mr-2"></i>Recent Transactions
                    </h6>
                </div>
                <div class="card-body">
                    @if(isset($dashboard_data['recent_transactions']) && count($dashboard_data['recent_transactions']) > 0)
                        <div class="transactions-list" style="max-height: 250px; overflow-y: auto;">
                            @foreach($dashboard_data['recent_transactions'] as $transaction)
                                <div class="transaction-item d-flex justify-content-between align-items-center py-2 border-bottom">
                                    <div>
                                        <div class="font-weight-bold text-sm">{{ $transaction['description'] }}</div>
                                        <div class="text-muted text-xs">
                                            {{ \Carbon\Carbon::parse($transaction['date'])->format('M j, Y') }}
                                            <span class="ml-2">{{ $transaction['method'] ?? 'Cash' }}</span>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <div class="font-weight-bold text-{{ $transaction['type'] === 'income' ? 'success' : 'danger' }}">
                                            {{ $transaction['type'] === 'income' ? '+' : '-' }}₹{{ number_format($transaction['amount']) }}
                                        </div>
                                        <span class="badge badge-{{ $transaction['status'] === 'completed' ? 'success' : ($transaction['status'] === 'pending' ? 'warning' : 'danger') }} badge-sm">
                                            {{ ucfirst($transaction['status']) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <div class="text-center mt-3">
                            <a href="{{ route('transactions.index') }}" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-list mr-1"></i>
                                View All Transactions
                            </a>
                        </div>
                    @else
                        <div class="text-center py-3">
                            <i class="fas fa-receipt fa-2x text-gray-300 mb-2"></i>
                            <div class="text-muted">No recent transactions</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Section - Financial Summary -->
    <div class="financial-summary-grid">
        <div class="summary-item positive">
            <div class="summary-value text-success">{{ $dashboard_data['collection_rate'] ?? 0 }}%</div>
            <div class="summary-label">Collection Rate</div>
        </div>
        
        <div class="summary-item">
            <div class="summary-value text-primary">{{ $dashboard_data['avg_payment_time'] ?? 0 }}</div>
            <div class="summary-label">Avg Payment Days</div>
        </div>
        
        <div class="summary-item {{ ($dashboard_data['outstanding_ratio'] ?? 0) > 20 ? 'negative' : 'positive' }}">
            <div class="summary-value text-{{ ($dashboard_data['outstanding_ratio'] ?? 0) > 20 ? 'danger' : 'success' }}">{{ $dashboard_data['outstanding_ratio'] ?? 0 }}%</div>
            <div class="summary-label">Outstanding Ratio</div>
        </div>
        
        <div class="summary-item positive">
            <div class="summary-value text-info">₹{{ number_format($dashboard_data['avg_transaction'] ?? 0) }}</div>
            <div class="summary-label">Avg Transaction</div>
        </div>
        
        <div class="summary-item">
            <div class="summary-value text-warning">{{ $dashboard_data['total_invoices'] ?? 0 }}</div>
            <div class="summary-label">Total Invoices</div>
        </div>
        
        <div class="summary-item positive">
            <div class="summary-value text-success">{{ $dashboard_data['paid_invoices'] ?? 0 }}</div>
            <div class="summary-label">Paid Invoices</div>
        </div>
    </div>

    <!-- Financial Alerts -->
    @if(isset($dashboard_data['financial_alerts']) && count($dashboard_data['financial_alerts']) > 0)
    <div class="row mt-4">
        <div class="col-12">
            <div class="card border-left-warning shadow">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h5 class="mb-2 text-warning">
                                <i class="fas fa-exclamation-triangle mr-2"></i>Financial Alerts
                            </h5>
                            <div class="alerts-list">
                                @foreach(array_slice($dashboard_data['financial_alerts'], 0, 3) as $alert)
                                    <div class="alert-item mb-1">
                                        <i class="fas fa-{{ $alert['icon'] ?? 'info-circle' }} mr-2 text-{{ $alert['type'] ?? 'warning' }}"></i>
                                        {{ $alert['message'] }}
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="col-md-4 text-md-right">
                            <a href="{{ route('financial.alerts') }}" class="btn btn-warning">
                                <i class="fas fa-eye mr-1"></i>
                                View All Alerts
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Monthly Revenue Chart
    const monthlyRevenueCtx = document.getElementById('monthlyRevenueChart');
    if (monthlyRevenueCtx) {
        const monthlyData = @json($dashboard_data['monthly_chart'] ?? []);
        
        new Chart(monthlyRevenueCtx, {
            type: 'doughnut',
            data: {
                labels: monthlyData.labels || ['Tuition Fees', 'Admission Fees', 'Other Fees', 'Penalties'],
                datasets: [{
                    data: monthlyData.data || [60, 25, 10, 5],
                    backgroundColor: [
                        '#1cc88a',
                        '#36b9cc',
                        '#f6c23e',
                        '#e74a3b'
                    ],
                    borderWidth: 3,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ₹' + context.parsed.toLocaleString() + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '60%'
            }
        });
    }

    // Animate financial cards
    const financialCards = document.querySelectorAll('.financial-card');
    financialCards.forEach((card, index) => {
        card.style.opacity = '0';
        card.style.transform = 'translateY(30px)';
        
        setTimeout(() => {
            card.style.transition = 'all 0.6s ease';
            card.style.opacity = '1';
            card.style.transform = 'translateY(0)';
        }, index * 150);
    });

    // Auto-refresh financial data every 3 minutes
    setInterval(function() {
        refreshFinancialData();
    }, 180000);
});

function refreshFinancialData() {
    fetch('/api/dashboard/financial-summary', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        updateFinancialCards(data);
        console.log('Financial data refreshed');
    })
    .catch(error => {
        console.error('Error refreshing financial data:', error);
    });
}

function updateFinancialCards(data) {
    // Update revenue
    const revenueAmount = document.querySelector('.financial-card.revenue .financial-amount');
    if (revenueAmount && data.total_revenue !== undefined) {
        revenueAmount.textContent = '₹' + data.total_revenue.toLocaleString();
    }
    
    // Update pending amount
    const pendingAmount = document.querySelector('.financial-card.pending .financial-amount');
    if (pendingAmount && data.pending_amount !== undefined) {
        pendingAmount.textContent = '₹' + data.pending_amount.toLocaleString();
    }
    
    // Update expenses
    const expensesAmount = document.querySelector('.financial-card.expenses .financial-amount');
    if (expensesAmount && data.total_expenses !== undefined) {
        expensesAmount.textContent = '₹' + data.total_expenses.toLocaleString();
    }
    
    // Update profit
    const profitAmount = document.querySelector('.financial-card.profit .financial-amount');
    if (profitAmount && data.total_revenue !== undefined && data.total_expenses !== undefined) {
        const profit = data.total_revenue - data.total_expenses;
        profitAmount.textContent = '₹' + profit.toLocaleString();
    }
}

// Utility function for financial notifications
function showFinancialNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} notification-toast`;
    notification.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 250px; animation: slideInRight 0.3s ease;';
    notification.innerHTML = `
        <button type="button" class="close" onclick="this.parentElement.remove()">
            <span>&times;</span>
        </button>
        ${message}
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
}

// Export financial data
function exportFinancialData(format = 'excel') {
    const button = event.target;
    const originalText = button.textContent;
    
    button.textContent = 'Exporting...';
    button.disabled = true;
    
    fetch(`/api/financial/export?format=${format}`, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.blob())
    .then(blob => {
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `financial-report-${new Date().toISOString().split('T')[0]}.${format === 'excel' ? 'xlsx' : 'pdf'}`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        window.URL.revokeObjectURL(url);
        
        showFinancialNotification('Financial report exported successfully', 'success');
    })
    .catch(error => {
        console.error('Export error:', error);
        showFinancialNotification('Failed to export financial report', 'error');
    })
    .finally(() => {
        button.textContent = originalText;
        button.disabled = false;
    });
}
</script>
@endpush