@push('styles')
    <style>
        /* Dashboard Color Scheme */
        :root {
            --primary-color: #4f46e5;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --card-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --card-shadow-hover: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        /* Dashboard Header */
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #7c3aed 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            border-radius: 12px;
            position: relative;
            overflow: hidden;
        }

        .dashboard-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="white" opacity="0.1"/><circle cx="80" cy="80" r="1.5" fill="white" opacity="0.1"/></svg>');
            opacity: 0.3;
        }

        .dashboard-header .content {
            position: relative;
            z-index: 1;
        }

        /* Stats Grid */
        .stats-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--card-shadow);
            transition: all 0.3s ease;
            border: 1px solid rgba(0, 0, 0, 0.05);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--primary-color);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .stat-card:hover::before {
            transform: scaleX(1);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--card-shadow-hover);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            color: white;
        }

        .stat-icon.enrollment {
            background: var(--primary-color);
        }

        .stat-icon.payments {
            background: var(--success-color);
        }

        .stat-icon.attendance {
            background: var(--info-color);
        }

        .stat-icon.activity {
            background: var(--warning-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-trend {
            margin-top: 0.75rem;
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
        }

        .trend-positive {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .trend-negative {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        /* Time Period Buttons */
        .period-selector {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }

        .period-btn {
            padding: 0.5rem 1rem;
            border: 1px solid #d1d5db;
            background: white;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .period-btn.active {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .period-btn:hover:not(.active) {
            border-color: var(--primary-color);
            background: #f8faff;
        }

        /* Dashboard Grid Layout */
        .dashboard-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        /* Widget Cards */
        .widget-card {
            background: white;
            border-radius: 12px;
            box-shadow: var(--card-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
        }

        .widget-header {
            padding: 1.25rem 1.5rem 1rem;
            border-bottom: 1px solid #f1f3f4;
            background: #fafbfc;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .widget-title {
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .widget-title i {
            margin-right: 0.75rem;
            color: var(--primary-color);
        }

        .widget-body {
            padding: 1.5rem;
        }

        /* Payment Stats Grid */
        .payment-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .payment-stat {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
            border: 1px solid #e9ecef;
        }

        .payment-stat-amount {
            font-size: 1.375rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .payment-stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
        }

        /* Chart Container */
        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        /* Activity Log */
        .activity-log {
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem 0;
            border-bottom: 1px solid #f1f3f4;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background-color: #f9fafb;
            margin: 0 -1rem;
            padding-left: 1rem;
            padding-right: 1rem;
            border-radius: 6px;
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            margin-right: 0.75rem;
            flex-shrink: 0;
        }

        .activity-icon.payment {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
        }

        .activity-icon.login {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
        }

        .activity-icon.update {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
        }

        .activity-icon.delete {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 0.875rem;
            color: #374151;
            margin-bottom: 0.25rem;
            font-weight: 500;
        }

        .activity-meta {
            font-size: 0.75rem;
            color: #6b7280;
        }

        .activity-time {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-left: auto;
            flex-shrink: 0;
        }

        /* Attendance Overview */
        .attendance-overview {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .attendance-stat {
            text-align: center;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .attendance-percentage {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .attendance-percentage.excellent {
            color: var(--success-color);
        }

        .attendance-percentage.good {
            color: var(--info-color);
        }

        .attendance-percentage.average {
            color: var(--warning-color);
        }

        .attendance-percentage.poor {
            color: var(--danger-color);
        }

        .attendance-label {
            font-size: 0.75rem;
            color: #6b7280;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-overview {
                grid-template-columns: repeat(2, 1fr);
            }

            .payment-stats {
                grid-template-columns: 1fr;
            }

            .period-selector {
                justify-content: center;
            }

            .period-btn {
                font-size: 0.8rem;
                padding: 0.375rem 0.75rem;
            }
        }

        /* Custom Scrollbar */
        .activity-log::-webkit-scrollbar {
            width: 6px;
        }

        .activity-log::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }

        .activity-log::-webkit-scrollbar-thumb {
            background: #c1c1c1;
            border-radius: 3px;
        }

        .activity-log::-webkit-scrollbar-thumb:hover {
            background: #a1a1a1;
        }

        /* Loading States */
        .loading-spinner {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 200px;
            flex-direction: column;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid #f3f4f6;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }
    </style>
@endpush

