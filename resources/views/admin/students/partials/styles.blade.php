@push('styles')
    <style>
        /* Modern Card Styling */
        .modern-card {
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border: none;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .modern-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
        }

        /* Modern Profile Header Styles */
        .profile-cover {
            height: 130px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
        }

        .profile-cover::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50%;
            background: linear-gradient(to top, rgba(0, 0, 0, 0.1), transparent);
        }

        .student-avatar-container {
            margin-top: -65px;
            position: relative;
            display: inline-block;
        }

        .student-avatar-img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid #ffffff;
            object-fit: cover;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background: #fff;
        }

        .status-indicator {
            position: absolute;
            bottom: 10px;
            right: 10px;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            border: 3px solid #fff;
        }

        .meta-pill {
            background: #f8f9fc;
            border: 1px solid #e3e6f0;
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 0.85rem;
            color: #5a5c69;
            display: inline-flex;
            align-items: center;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .meta-pill i {
            margin-right: 6px;
            opacity: 0.7;
        }

        .biometric-badge {
            background: #fff3cd;
            color: #856404;
            border: 1px solid #ffeeba;
            font-family: 'Courier New', monospace;
            letter-spacing: 1px;
        }

        /* Enhanced Profile Header */
        .profile-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
            border-radius: 8px 8px 0 0;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            transform: rotate(45deg);
        }

        .profile-content {
            position: relative;
            z-index: 2;
        }

        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.05);
        }

        /* Action Button Enhancements */
        .action-btn {
            border-radius: 6px;
            padding: 10px 20px;
            font-weight: 600;
            border: none;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            margin: 0.25rem;
            text-decoration: none;
            display: inline-block;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: left 0.3s ease;
        }

        .action-btn:hover::before {
            left: 100%;
        }

        .btn-primary-modern {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-success-modern {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #2d5a27;
        }

        .btn-info-modern {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-warning-modern {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #8b4513;
        }

        .btn-secondary-modern {
            background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            color: #495057;
        }

        /* Statistics Cards */
        .stat-card {
            border-left: 4px solid;
            border-radius: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, .15);
        }

        .stat-card.border-left-primary {
            border-left-color: #4e73df;
        }

        .stat-card.border-left-success {
            border-left-color: #1cc88a;
        }

        .stat-card.border-left-danger {
            border-left-color: #e74a3b;
        }

        .stat-card.border-left-info {
            border-left-color: #36b9cc;
        }

        /* Navigation Tabs */
        .nav-tabs-modern {
            border-bottom: 3px solid #e9ecef;
            margin-bottom: 2rem;
        }

        .nav-tabs-modern .nav-link {
            border: none;
            border-radius: 8px 8px 0 0;
            padding: 15px 25px;
            font-weight: 600;
            background: #f8f9fc;
            color: #858796;
            margin-right: 5px;
            transition: all 0.3s ease;
        }

        .nav-tabs-modern .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .nav-tabs-modern .nav-link:hover:not(.active) {
            background: #e9ecef;
            transform: translateY(-1px);
        }

        /* Fee Component Cards */
        .fee-component-card {
            border-radius: 12px;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            margin-bottom: 1rem;
            background: white;
        }

        .fee-component-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .fee-component-header {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border-radius: 12px 12px 0 0;
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .payment-progress {
            height: 8px;
            border-radius: 10px;
            background: #e9ecef;
            overflow: hidden;
        }

        .payment-progress-bar {
            height: 100%;
            border-radius: 10px;
            transition: width 0.5s ease;
        }

        /* Status Badges */
        .status-badge {
            padding: 0.5em 1em;
            font-size: .8em;
            font-weight: 700;
            border-radius: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.paid {
            background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            color: #2d5a27;
        }

        .status-badge.unpaid {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            color: #8b4513;
        }

        .status-badge.partial {
            background: linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%);
            color: #8b4513;
        }

        /* Quick Action Cards */
        .quick-action-card {
            border-radius: 15px;
            border: 1px solid #e9ecef;
            background: white;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-action-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            border-color: #4e73df;
        }

        .quick-action-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-right: 1rem;
        }

        /* Payment Modal Styling */
        .payment-modal .modal-content {
            border-radius: 20px;
            border: none;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }

        .payment-modal .modal-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 20px 20px 0 0;
            border: none;
        }

        .payment-modal .close {
            color: white;
            opacity: 0.8;
        }

        .payment-modal .close:hover {
            opacity: 1;
        }

        /* Payment Form Styling */
        .payment-form-section {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .payment-component-item {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }

        .payment-component-item.selected {
            border-color: #4e73df;
            background: #f1f3ff;
        }

        .payment-summary {
            background: linear-gradient(135deg, #f8f9fc 0%, #e9ecef 100%);
            border-radius: 10px;
            padding: 1.5rem;
            border: 2px solid #dee2e6;
        }

        /* Calendar Styling */
        .calendar-table {
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .calendar-table thead th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 1rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-table td {
            height: 80px;
            vertical-align: middle;
            text-align: center;
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
        }

        .calendar-table td:hover {
            background: #f8f9fc;
            transform: scale(1.05);
        }

        /* Modern Calendar Grid Styling */
        .comprehensive-calendar {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .calendar-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1.5rem;
        }

        .calendar-legend {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .calendar-grid {
            padding: 1rem;
        }

        .calendar-weekdays {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 8px 8px 0 0;
            overflow: hidden;
        }

        .weekday {
            background: #f8f9fc;
            padding: 1rem;
            text-align: center;
            font-weight: 600;
            color: #495057;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.875rem;
        }

        .calendar-days {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            background: #e9ecef;
            border-radius: 0 0 8px 8px;
            overflow: hidden;
        }

        .calendar-day {
            background: white;
            min-height: 80px;
            padding: 0.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-start;
            transition: all 0.3s ease;
            position: relative;
        }

        .calendar-day:hover:not(.empty) {
            background: #f8f9fc;
            transform: scale(1.02);
        }

        .calendar-day.empty {
            background: #f8f9fc;
            opacity: 0.5;
        }

        .calendar-day .day-number {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.25rem;
        }

        .calendar-day .attendance-status {
            font-size: 0.75rem;
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .calendar-day .attendance-status.present {
            background: #d4edda;
            color: #155724;
        }

        .calendar-day .attendance-status.absent {
            background: #f8d7da;
            color: #721c24;
        }

        .calendar-day .attendance-status.late {
            background: #fff3cd;
            color: #856404;
        }

        .calendar-day .attendance-status.excused {
            background: #d1ecf1;
            color: #0c5460;
        }

        /* Monthly Summary Styling */
        .monthly-summary {
            background: #f8f9fc;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            border: 1px solid #e9ecef;
        }

        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }

        .summary-stat {
            text-align: center;
            padding: 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .summary-stat .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #495057;
        }

        .summary-stat .stat-label {
            font-size: 0.875rem;
            color: #6c757d;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .profile-header {
                padding: 1.5rem 1rem;
            }

            .action-btn {
                display: block;
                width: 100%;
                margin: 0.25rem 0;
            }

            .stat-card {
                margin-bottom: 1rem;
            }

            .fee-component-card {
                margin-bottom: 0.5rem;
            }

            /* Mobile Calendar Adjustments */
            .calendar-grid {
                padding: 0.5rem;
            }

            .weekday {
                padding: 0.5rem;
                font-size: 0.75rem;
            }

            .calendar-day {
                min-height: 60px;
                padding: 0.25rem;
            }

            .calendar-day .day-number {
                font-size: 0.875rem;
            }

            .calendar-day .attendance-status {
                font-size: 0.625rem;
                padding: 0.125rem 0.25rem;
            }

            .summary-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .calendar-header {
                padding: 1rem;
            }

            .calendar-legend {
                gap: 0.5rem;
            }

            .legend-item {
                font-size: 0.75rem;
            }
        }

        /* Loading States */
        .loading {
            opacity: 0.5;
            pointer-events: none;
            position: relative;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 40px;
            height: 40px;
            border: 3px solid #f3f3f3;
            border-top: 3px solid #4e73df;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            transform: translate(-50%, -50%);
        }

        @keyframes spin {
            0% {
                transform: translate(-50%, -50%) rotate(0deg);
            }

            100% {
                transform: translate(-50%, -50%) rotate(360deg);
            }
        }

        /* Payment Summary Animations */
        .amount-counter {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
    </style>
@endpush
