<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&family=Noto+Sans+Telugu:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'Noto Sans Telugu', 'sans-serif'] },
                    colors: {
                        primary: '#4f46e5',
                        secondary: '#64748b'
                    }
                }
            }
        }
    </script>
    <style>
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
        }

        .animate-fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .staggered-list>* {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Standard Transitions */
        .animate-fade-in {
            transition: opacity 0.4s ease-out, transform 0.4s ease-out;
        }

        /* Simplified Skeleton */
        .skeleton {
            background: #f1f5f9;
            position: relative;
            overflow: hidden;
        }

        .skeleton::after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            transform: translateX(-100%);
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.5), transparent);
            animation: shimmer 1.5s infinite;
        }

        @keyframes shimmer {
            100% {
                transform: translateX(100%);
            }
        }
    </style>
</head>

<body class="bg-gray-50 text-slate-800 pb-20">

    <!-- Navbar -->
    <nav class="bg-white border-b border-gray-200 sticky top-0 z-50">
        <div class="px-4 py-3 flex justify-between items-center max-w-lg mx-auto">
            <div class="flex items-center gap-3">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($student->name) }}&background=4f46e5&color=fff"
                    class="w-8 h-8 rounded-full">
                <div>
                    <h1 class="font-bold text-sm leading-tight">{{ Str::limit($student->name, 20) }}</h1>
                    <p class="text-[10px] text-gray-500">{{ $student->enrollment_number }}</p>
                </div>
            </div>
            <form action="{{ route('student.logout') }}" method="POST">
                @csrf
                <button class="text-xs text-gray-500 hover:text-red-500">
                    <i class="fa-solid fa-power-off"></i> Logout
                </button>
            </form>
        </div>
    </nav>

    <!-- Content -->
    <div class="max-w-lg mx-auto p-4 space-y-6">

        <!-- PROFILE CARD -->
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-gray-100 relative overflow-hidden">
            <!-- Profile Header -->
            <div class="flex items-start justify-between mb-4">
                <div class="flex gap-4">
                    <!-- Photo -->
                    <div class="relative">
                        @if($student->photo)
                            <img src="{{ asset('storage/' . $student->photo) }}"
                                class="w-16 h-16 rounded-xl object-cover border border-gray-100">
                        @else
                            <div class="w-16 h-16 rounded-xl bg-gray-100 flex items-center justify-center text-gray-400">
                                <i class="fa-solid fa-user text-2xl"></i>
                            </div>
                        @endif
                        @if(isset($pendingRequests['photo']))
                            <div class="absolute -bottom-2 -right-2 bg-yellow-500 text-white w-7 h-7 rounded-full flex items-center justify-center text-xs shadow-md border-2 border-white"
                                title="Photo update pending approval">
                                <i class="fa-solid fa-clock"></i>
                            </div>
                        @else
                            <button onclick="openModal('photo')"
                                class="absolute -bottom-2 -right-2 bg-indigo-600 text-white w-7 h-7 rounded-full flex items-center justify-center text-xs shadow-md border-2 border-white">
                                <i class="fa-solid fa-camera"></i>
                            </button>
                        @endif
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-indigo-600 uppercase tracking-wide mb-1">
                            {{ $student->batch->course->name ?? 'Course' }}
                        </div>
                        <h2 class="text-xl font-bold text-gray-900">{{ $student->name }}</h2>
                    </div>
                </div>
                <div class="text-right">
                    <span
                        class="bg-green-100 text-green-700 text-[10px] px-2 py-1 rounded-full font-bold uppercase tracking-wider">Active</span>
                </div>
            </div>

            <!-- Info Grid -->
            <div class="grid grid-cols-1 gap-3 text-sm mt-2">
                <!-- Student Mobile -->
                <div class="flex justify-between items-center py-2 border-b border-gray-50 border-dashed">
                    <span class="text-gray-400">My Mobile</span>
                    <div class="flex items-center gap-2">
                        @if(isset($pendingRequests['personal']) && ($pendingRequests['personal']->new_data && json_decode($pendingRequests['personal']->new_data, true)['type'] ?? null) === 'student')
                            <span class="text-[10px] bg-yellow-50 text-yellow-600 px-2 py-1 rounded font-medium">⏳ Waiting
                                for approval</span>
                        @elseif($student->student_mobile)
                            <span class="font-mono text-gray-700">******{{ substr($student->student_mobile, -3) }}</span>
                        @else
                            <button onclick="openLinkModal('student')"
                                class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded font-medium">Add
                                +</button>
                        @endif
                    </div>
                </div>
                <!-- Father Mobile -->
                <div class="flex justify-between items-center py-2 border-b border-gray-50 border-dashed">
                    <span class="text-gray-400">Father's Mobile</span>
                    <div class="flex items-center gap-2">
                        @if(isset($pendingRequests['personal']) && ($pendingRequests['personal']->new_data && json_decode($pendingRequests['personal']->new_data, true)['type'] ?? null) === 'father')
                            <span class="text-[10px] bg-yellow-50 text-yellow-600 px-2 py-1 rounded font-medium">⏳ Waiting
                                for approval</span>
                        @elseif($student->father_mobile)
                            <span class="font-mono text-gray-700">******{{ substr($student->father_mobile, -3) }}</span>
                        @else
                            <button onclick="openLinkModal('father')"
                                class="text-[10px] bg-indigo-50 text-indigo-600 px-2 py-1 rounded font-medium">Add
                                +</button>
                        @endif
                    </div>
                </div>

                <!-- Gender -->
                <div class="flex justify-between items-center py-2 border-b border-gray-50 border-dashed">
                    <span class="text-gray-400">Gender</span>
                    <span class="text-gray-700">{{ ucfirst($student->gender ?? 'Not recorded') }}</span>
                </div>

                <!-- Date of Birth -->
                <div class="flex justify-between items-center py-2 border-b border-gray-50 border-dashed">
                    <span class="text-gray-400">Date of Birth</span>
                    <div class="flex items-center gap-2">
                        @if(isset($pendingRequests['dob']))
                            <span class="text-[10px] bg-yellow-50 text-yellow-600 px-2 py-1 rounded font-medium">⏳ Waiting
                                for approval</span>
                        @else
                            <span
                                class="text-gray-700">{{ $student->dob ? $student->dob->format('d M, Y') : 'Not recorded' }}</span>
                            @if(!$student->dob)
                                <button onclick="openModal('dob')" class="text-indigo-600 hover:text-indigo-700 text-xs">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Father's Name -->
                <div class="flex justify-between items-center py-2 border-b border-gray-50 border-dashed">
                    <span class="text-gray-400">Father's Name</span>
                    <span class="text-gray-700">{{ $student->father_name ?? 'Not recorded' }}</span>
                </div>
            </div>

            <!-- Profile Completion Progress -->
            @php
                $completeness = $completeness ?? ['percentage' => 0, 'missing' => []];
            @endphp
            <div class="mt-4 bg-gradient-to-r from-indigo-50 to-purple-50 p-4 rounded-xl">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-xs font-bold text-gray-700">Profile Completion</span>
                    <span class="text-xs font-bold text-indigo-600">{{ $completeness['percentage'] }}%</span>
                </div>
                <div class="w-full bg-white rounded-full h-2 overflow-hidden">
                    <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-2 transition-all duration-500"
                        style="width: {{ $completeness['percentage'] }}%"></div>
                </div>
                @if(count($completeness['missing']) > 0)
                    <div class="mt-3 pt-3 border-t border-white/50">
                        <p class="text-[10px] text-gray-500 font-medium mb-2">Missing Information:</p>
                        <div class="flex flex-wrap gap-1">
                            @foreach($completeness['missing'] as $field)
                                <span class="text-[9px] bg-white/70 text-gray-600 px-2 py-0.5 rounded-full">{{ $field }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Address -->
            <div class="mt-4 bg-gray-50 p-3 rounded-lg flex justify-between items-start">
                <div class="flex-1">
                    <div class="text-[10px] text-gray-400 uppercase font-bold mb-1">Current Address</div>
                    @if(isset($pendingRequests['address']))
                        <span class="text-[10px] bg-yellow-50 text-yellow-600 px-2 py-1 rounded font-medium inline-block">⏳
                            Waiting for approval</span>
                    @else
                        <p class="text-sm text-gray-600 leading-snug">
                            {{ $student->admission->address ?? 'No address provided.' }}
                        </p>
                    @endif
                </div>
                @if(!isset($pendingRequests['address']))
                    <button onclick="openModal('address')" class="text-gray-400 hover:text-indigo-600 text-xs ml-2"><i
                            class="fa-solid fa-pen"></i></button>
                @endif
            </div>
        </div>

        <!-- TABS -->
        <div>
            <div class="flex gap-6 border-b border-gray-200 mb-4 px-2">
                <button onclick="switchTab('attendance')" id="btn-attendance"
                    class="pb-2 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 transition-colors">Attendance</button>
                <button onclick="switchTab('payments')" id="btn-payments"
                    class="pb-2 text-sm font-semibold border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-colors">Payments</button>
            </div>

            <!-- ATTENDANCE CONTENT -->
            <div id="tab-attendance" class="animate-fade-in group">
                <!-- Month Navigation & Stats -->
                <div class="mb-4">
                    <div class="mb-4">
                        <div
                            class="flex justify-between items-center mb-4 bg-white p-3 rounded-2xl shadow-sm border border-gray-100">
                            <button onclick="changeMonth(-1)"
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-50 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <i class="fa-solid fa-chevron-left text-xs"></i>
                            </button>

                            <div class="flex flex-col items-center">
                                <h3 class="font-bold text-gray-800 text-sm mb-1" id="calendar-month-title">Loading...
                                </h3>
                                <select id="month-selector" onchange="jumpToMonth(this.value)"
                                    class="text-xs border-none bg-gray-50 rounded px-2 py-1 text-gray-500 focus:ring-0 cursor-pointer">
                                    <option value="">Jump to Month...</option>
                                </select>
                            </div>

                            <button onclick="changeMonth(1)"
                                class="w-8 h-8 flex items-center justify-center rounded-full bg-gray-50 text-gray-400 hover:bg-indigo-50 hover:text-indigo-600 transition-colors">
                                <i class="fa-solid fa-chevron-right text-xs"></i>
                            </button>
                        </div>

                        <!-- Stats Grid -->
                        <div class="grid grid-cols-4 gap-2 mb-4">
                            <div class="bg-white p-2 rounded-xl border border-gray-100 shadow-sm text-center">
                                <div class="text-[10px] text-gray-400 uppercase font-bold tracking-wider mb-1">Working
                                </div>
                                <div class="text-lg font-bold text-gray-800" id="stat-working">0</div>
                            </div>
                            <div class="bg-white p-2 rounded-xl border border-emerald-100 shadow-sm text-center">
                                <div class="text-[10px] text-emerald-400 uppercase font-bold tracking-wider mb-1">
                                    Present
                                </div>
                                <div class="text-lg font-bold text-emerald-600" id="stat-present">0</div>
                            </div>
                            <div class="bg-white p-2 rounded-xl border border-red-100 shadow-sm text-center">
                                <div class="text-[10px] text-red-400 uppercase font-bold tracking-wider mb-1">Absent
                                </div>
                                <div class="text-lg font-bold text-red-600" id="stat-absent">0</div>
                            </div>
                            <div class="bg-white p-2 rounded-xl border border-blue-100 shadow-sm text-center">
                                <div class="text-[10px] text-blue-400 uppercase font-bold tracking-wider mb-1">Holiday
                                </div>
                                <div class="text-lg font-bold text-blue-600" id="stat-holiday">0</div>
                            </div>
                        </div>
                    </div>

                    <div id="calendar-wrapper" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                        <div class="flex justify-between items-center mb-4">
                            <span class="text-xs font-bold text-gray-400 uppercase tracking-widest">Attendance
                                Sheet</span>
                            <span class="text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded font-bold"
                                id="att-percent-badge">0%</span>
                        </div>

                        <!-- Week Headers -->
                        <div class="calendar-grid mb-2 text-center text-xs text-gray-400 font-medium">
                            <div>S</div>
                            <div>M</div>
                            <div>T</div>
                            <div>W</div>
                            <div>T</div>
                            <div>F</div>
                            <div>S</div>
                        </div>

                        <!-- Days -->
                        <div id="calendar-days" class="calendar-grid text-sm">
                            <!-- JS will populate -->
                            <div class="col-span-7 py-8 text-center text-gray-300 text-xs">Loading calendar...</div>
                        </div>

                        <div class="flex gap-4 mt-4 pt-4 border-t border-gray-50 text-xs text-gray-500 justify-center">
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-emerald-500"></div> Present
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-red-400"></div> Absent
                            </div>
                            <div class="flex items-center gap-1">
                                <div class="w-2 h-2 rounded-full bg-blue-400"></div> Holiday
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYMENTS CONTENT -->
            <div id="tab-payments" class="hidden animate-fade-in space-y-8">
                <!-- Simplified Skeleton Loader -->
                <div id="payments-loader" class="space-y-4 py-4">
                    <div class="bg-gray-200 rounded-2xl h-40 skeleton"></div>
                    <div class="h-4 w-32 bg-gray-200 rounded-full skeleton"></div>
                    <div class="bg-white border border-gray-100 rounded-2xl h-24 shadow-sm"></div>
                    <div class="bg-white border border-gray-100 rounded-2xl h-24 shadow-sm"></div>
                </div>

                <!-- Clean Indigo Summary Card -->
                <div id="payment-summary-card"
                    class="bg-indigo-600 rounded-2xl p-6 sm:p-8 text-white shadow-lg hidden relative overflow-hidden group">
                    <div
                        class="absolute top-0 right-0 -mt-8 -mr-8 w-40 h-40 bg-white/10 rounded-full transition-transform group-hover:scale-110">
                    </div>

                    <div class="relative z-10">
                        <div class="flex justify-between items-start mb-6">
                            <div class="bg-white/10 px-3 py-1 rounded-lg border border-white/20">
                                <span class="text-[10px] font-bold uppercase tracking-wider">Total Dues</span>
                            </div>
                            <i class="fa-solid fa-wallet text-xl opacity-50"></i>
                        </div>

                        <div class="space-y-1">
                            <div class="flex items-baseline gap-2">
                                <span class="text-xl sm:text-2xl font-light">₹</span>
                                <span class="text-4xl sm:text-5xl font-bold tracking-tight"
                                    id="total-balance-display">0</span>
                            </div>
                            <p class="text-white/60 text-[10px] font-medium uppercase tracking-widest pl-1">Remaining
                                Balance</p>
                        </div>

                        <div class="mt-8 flex items-center">
                            <div class="flex items-center gap-2">
                                <div class="w-2 h-2 rounded-full bg-red-400"></div>
                                <span class="text-xs font-medium">Pending Payment</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sections Wrapper -->
                <div id="payments-content-area" class="space-y-10 hidden">
                    <!-- Pending Dues Section -->
                    <div id="pending-section" class="hidden">
                        <div class="flex items-center justify-between mb-6 px-4">
                            <div class="flex items-center gap-4">
                                <div class="w-1.5 h-6 bg-red-500 rounded-full"></div>
                                <h3 class="text-sm font-black text-gray-400 uppercase tracking-[0.3em]">Pending</h3>
                            </div>
                            <span
                                class="bg-red-50 text-red-500 text-[10px] font-black px-3 py-1 rounded-full border border-red-100 uppercase"
                                id="pending-count">0 Items</span>
                        </div>
                        <div id="pending-list" class="space-y-4 staggered-list"></div>
                    </div>

                    <!-- History Section -->
                    <div id="history-section" class="hidden">
                        <div class="flex items-center justify-between mb-6 px-4">
                            <div class="flex items-center gap-4">
                                <div class="w-1.5 h-6 bg-emerald-500 rounded-full"></div>
                                <h3 class="text-sm font-black text-gray-400 uppercase tracking-[0.3em]">Transactions
                                </h3>
                            </div>
                        </div>
                        <div id="history-list" class="space-y-3 staggered-list"></div>
                    </div>
                </div>

                <!-- Empty State -->
                <div id="no-payments"
                    class="hidden text-center py-12 bg-white rounded-2xl border border-dashed border-gray-200">
                    <div
                        class="w-16 h-16 bg-gray-50 rounded-full flex items-center justify-center mx-auto mb-4 text-gray-300">
                        <i class="fa-solid fa-receipt text-2xl"></i>
                    </div>
                    <h4 class="font-bold text-gray-800">No Records</h4>
                    <p class="text-xs text-gray-400 mt-1 px-4">Your payment history and pending dues will appear here.
                    </p>
                </div>
            </div>

            <!-- Template: Pending Fee -->
            <template id="tpl-fee">
                <div
                    class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm flex justify-between items-center transition-all hover:border-indigo-100">
                    <div class="flex items-center gap-4">
                        <div
                            class="w-10 h-10 rounded-xl bg-indigo-50 text-indigo-500 flex items-center justify-center text-lg">
                            <i class="fee-icon fa-solid fa-receipt"></i>
                        </div>
                        <div>
                            <h4 class="fee-category text-sm font-bold text-gray-800">Category</h4>
                            <p class="fee-meta text-[10px] text-gray-400 font-medium uppercase tracking-wider">Pending
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="fee-amount text-base font-bold text-indigo-600">₹0</p>
                    </div>
                </div>
            </template>

            <!-- Template: Transaction -->
            <template id="tpl-transaction">
                <div
                    class="bg-white border border-gray-100 rounded-2xl p-4 shadow-sm transition-all hover:border-emerald-100">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-4">
                            <div
                                class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-500 flex items-center justify-center text-lg">
                                <i class="fa-solid fa-check-circle"></i>
                            </div>
                            <div>
                                <h4 class="tx-category text-sm font-bold text-gray-800">Category</h4>
                                <p class="tx-date text-[10px] text-gray-400">Date</p>
                            </div>
                        </div>
                        <p class="tx-amount text-base font-bold text-emerald-600">₹0</p>
                    </div>
                    <div class="flex justify-between items-center pt-3 border-t border-gray-50">
                        <span class="text-[10px] font-bold text-emerald-500 uppercase tracking-wider">Paid</span>
                        <a href="#" class="receipt-link text-[10px] font-bold text-indigo-600 hover:underline">
                            <i class="fa-solid fa-download mr-1"></i> Receipt
                        </a>
                    </div>
                </div>
            </template>
        </div>

    </div>

    <!-- Modals -->
    <!-- Photo Modal -->
    <div id="modal-photo" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('photo')"></div>
        <div class="absolute bottom-0 w-full bg-white rounded-t-2xl p-6 animate-fade-in shadow-2xl">
            <h3 class="font-bold text-lg mb-2 text-gray-800">Update Photo</h3>
            <p class="text-xs text-gray-500 mb-4">Upload a clear photo. We'll compress it automatically.</p>
            <form id="form-photo" onsubmit="submitRequest(event, 'photo')">
                <input type="file" id="input-photo" accept="image/*"
                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 mb-4">
                <button type="submit" id="btn-photo-submit"
                    class="w-full bg-indigo-600 text-white py-3 rounded-xl font-medium shadow-md hover:bg-indigo-700">Upload
                    & Request</button>
            </form>
        </div>
    </div>

    <!-- Address Modal -->
    <div id="modal-address" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('address')"></div>
        <div class="absolute bottom-0 w-full bg-white rounded-t-2xl p-6 animate-fade-in shadow-2xl">
            <h3 class="font-bold text-lg mb-2 text-gray-800">Update Address</h3>
            <form id="form-address" onsubmit="submitRequest(event, 'address')">
                <textarea id="input-address"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none h-24 resize-none"
                    required placeholder="Enter new address...">{{ $student->admission->address ?? '' }}</textarea>
                <button type="submit"
                    class="w-full mt-4 bg-indigo-600 text-white py-3 rounded-xl font-medium shadow-md hover:bg-indigo-700">Submit
                    Request</button>
            </form>
        </div>
    </div>

    <!-- Mobile Link Modal -->
    <div id="modal-link" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('link')"></div>
        <div class="absolute bottom-0 w-full bg-white rounded-t-2xl p-6 animate-fade-in shadow-2xl">
            <h3 class="font-bold text-lg mb-2 text-gray-800">Link Mobile Number</h3>
            <p class="text-xs text-gray-500 mb-4" id="link-modal-desc">Add a mobile number.</p>
            <form id="form-link" onsubmit="submitRequest(event, 'personal')">
                <input type="hidden" id="link-type" name="mobile_type">
                <input type="tel" id="input-mobile" name="mobile_number"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none mb-4 font-mono"
                    required placeholder="Enter 10-digit number" pattern="[0-9]{10}" maxlength="10">
                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-3 rounded-xl font-medium shadow-md hover:bg-indigo-700">Request
                    Update</button>
            </form>
        </div>
    </div>

    <!-- DOB Modal -->
    <div id="modal-dob" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="closeModal('dob')"></div>
        <div class="absolute bottom-0 w-full bg-white rounded-t-2xl p-6 animate-fade-in shadow-2xl">
            <h3 class="font-bold text-lg mb-2 text-gray-800">Update Date of Birth</h3>
            <p class="text-xs text-gray-500 mb-4">Enter your date of birth.</p>
            <form id="form-dob" onsubmit="submitRequest(event, 'dob')">
                <input type="date" id="input-dob" name="dob"
                    class="w-full bg-gray-50 border border-gray-200 rounded-xl p-3 text-sm focus:ring-2 focus:ring-indigo-500 outline-none mb-4"
                    required max="{{ date('Y-m-d') }}">
                <button type="submit"
                    class="w-full bg-indigo-600 text-white py-3 rounded-xl font-medium shadow-md hover:bg-indigo-700">Request
                    Update</button>
            </form>
        </div>
    </div>

    <!-- Toast -->
    <div id="toast"
        class="fixed top-5 left-1/2 -translate-x-1/2 bg-gray-900/90 text-white px-4 py-2 rounded-full text-xs font-medium shadow-xl hidden z-[60] backdrop-blur transition-opacity">
        Request Sent!</div>

    <script type="text/javascript">
        // Calendar Logic
        const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

        let currentMonth = null;
        let currentYear = null;

        async function loadCalendar(month = null, year = null) {
            const container = document.getElementById('calendar-days');
            container.innerHTML = '<div class="col-span-7 py-8 text-center text-gray-300 text-xs">Loading...</div>';

            try {
                let url = "{{ route('student.data.attendance') }}";
                if (month && year) {
                    url += `?month=${month}&year=${year}`;
                }

                const res = await fetch(url);
                const data = await res.json();

                // Update State
                currentMonth = data.current_month;
                currentYear = data.current_year;

                // Update Stats Dashboard
                document.getElementById('att-percent-badge').innerText = data.stats.percentage + '%';
                document.getElementById('calendar-month-title').innerText = data.month_name;

                document.getElementById('stat-working').innerText = data.stats.total_working_days;
                document.getElementById('stat-present').innerText = data.stats.present_days;
                document.getElementById('stat-absent').innerText = data.stats.absent_days;
                document.getElementById('stat-holiday').innerText = data.stats.holidays;

                // Build Grid
                container.innerHTML = '';

                const firstDay = new Date(currentYear, currentMonth - 1, 1).getDay();
                const daysInMonth = new Date(currentYear, currentMonth, 0).getDate();

                // Populate Dropdown (only if not already populated to avoid flickering, or update on every load?)
                // Updating on every load ensures we see new data if it appears, but might be annoying if open.
                // Simple approach: Clear and Refill.
                const selector = document.getElementById('month-selector');
                if (data.available_months && data.available_months.length > 0) {
                    selector.innerHTML = '<option value="">Jump to Month...</option>';
                    data.available_months.forEach(m => {
                        const isSelected = (m.value === `${currentYear}-${String(currentMonth).padStart(2, '0')}`) ? 'selected' : '';
                        selector.innerHTML += `<option value="${m.value}" ${isSelected}>${m.label}</option>`;
                    });
                    selector.classList.remove('hidden');
                } else {
                    selector.classList.add('hidden');
                }

                // Empty slots
                for (let i = 0; i < firstDay; i++) {
                    container.innerHTML += '<div></div>';
                }

                // Days
                for (let d = 1; d <= daysInMonth; d++) {
                    // Format YYYY-MM-DD
                    const dayString = `${currentYear}-${String(currentMonth).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const rawStatus = data.calendar[dayString];
                    const status = rawStatus ? rawStatus.toLowerCase() : null;

                    let bgClass = 'bg-gray-50 text-gray-400';

                    if (status === 'present') {
                        bgClass = 'bg-emerald-100 text-emerald-700 font-bold';
                    } else if (status === 'absent') {
                        bgClass = 'bg-red-100 text-red-600 font-bold';
                    } else if (status === 'late') {
                        bgClass = 'bg-yellow-100 text-yellow-600 font-bold';
                    } else if (status === 'internship') {
                        bgClass = 'bg-indigo-100 text-indigo-700 font-bold';
                    } else if (status === 'holiday' || status === 'excused') {
                        bgClass = 'bg-blue-50 text-blue-400 font-bold';
                    } else if (status === 'weekend') {
                        bgClass = 'bg-purple-50 text-purple-400 font-bold';
                    }

                    container.innerHTML += `<div class="aspect-square flex items-center justify-center rounded-lg ${bgClass} text-xs transition-all hover:scale-105 cursor-default" title="${status ? status.toUpperCase() : ''}">${d}</div>`;
                }

                // Show "No Data" message if no statuses found in the whole month
                const hasAnyStatus = Object.keys(data.calendar).length > 0;
                if (!hasAnyStatus) {
                    container.innerHTML += `<div class="col-span-7 text-center text-gray-400 text-xs py-4">No attendance records found for this month.</div>`;
                }

            } catch (e) {
                console.error(e);
                container.innerHTML = '<div class="col-span-7 text-center text-red-500 text-xs py-4">Failed to load attendance</div>';
            }
        }

        function changeMonth(offset) {
            if (!currentMonth || !currentYear) return;

            let newMonth = currentMonth + offset;
            let newYear = currentYear;

            if (newMonth > 12) {
                newMonth = 1;
                newYear++;
            } else if (newMonth < 1) {
                newMonth = 12;
                newYear--;
            }

            loadCalendar(newMonth, newYear);
        }

        function jumpToMonth(value) {
            if (!value) return;
            const [year, month] = value.split('-');
            loadCalendar(parseInt(month), parseInt(year));
        }

        // Load Calendar on Init
        loadCalendar();

        // Tabs
        function switchTab(tab) {
            document.getElementById('tab-attendance').classList.add('hidden');
            document.getElementById('tab-payments').classList.add('hidden');

            document.getElementById('btn-attendance').className = "pb-2 text-sm font-semibold border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-colors";
            document.getElementById('btn-payments').className = "pb-2 text-sm font-semibold border-b-2 border-transparent text-gray-400 hover:text-gray-600 transition-colors";

            document.getElementById('tab-' + tab).classList.remove('hidden');
            document.getElementById('btn-' + tab).className = "pb-2 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 transition-colors";

            if (tab === 'payments') {
                console.log('Switching to Payments tab');
                loadPayments();
            }
        }

        // NEW DECLARATIVE PAYMENT SYSTEM
        const PaymentUI = {
            nodes: {
                tab: document.getElementById('tab-payments'),
                loader: document.getElementById('payments-loader'),
                content: document.getElementById('payments-content-area'),
                noData: document.getElementById('no-payments'),
                summary: document.getElementById('payment-summary-card'),
                totalDisplay: document.getElementById('total-balance-display'),
                pendingList: document.getElementById('pending-list'),
                pendingSection: document.getElementById('pending-section'),
                pendingCount: document.getElementById('pending-count'),
                historyList: document.getElementById('history-list'),
                historySection: document.getElementById('history-section'),
            },
            templates: {
                fee: document.getElementById('tpl-fee'),
                tx: document.getElementById('tpl-transaction'),
            },
            state: { loaded: false }
        };

        async function loadPayments() {
            if (PaymentUI.state.loaded) return;

            try {
                const response = await fetch("{{ route('student.data.payments') }}");
                const data = await response.json();
                renderPaymentDashboard(data);
                PaymentUI.state.loaded = true;
            } catch (error) {
                console.error("Payment Load Failure:", error);
                PaymentUI.nodes.loader.innerHTML = `
                        <div class="text-center p-8 bg-white border border-gray-100 rounded-2xl shadow-sm">
                            <i class="fa-solid fa-triangle-exclamation text-red-500 text-2xl mb-3"></i>
                            <p class="text-sm font-bold text-gray-800">Connection Interrupted</p>
                            <button onclick="PaymentUI.state.loaded=false;loadPayments()" class="mt-4 text-xs text-indigo-600 font-bold uppercase">Try Again</button>
                        </div>
                    `;
            }
        }

        function renderPaymentDashboard(data) {
            const { nodes, templates } = PaymentUI;
            nodes.loader.classList.add('hidden');

            let totalBalance = 0;
            let hasItems = false;

            // 1. Process Pending
            nodes.pendingList.innerHTML = '';
            if (data.pending?.length > 0) {
                hasItems = true;
                nodes.pendingSection.classList.remove('hidden');
                nodes.pendingCount.innerText = `${data.pending.length} Outstanding`;

                data.pending.forEach(item => {
                    totalBalance += Number(item.balance);
                    const row = templates.fee.content.cloneNode(true);
                    row.querySelector('.fee-category').innerText = item.category;
                    row.querySelector('.fee-amount').innerText = `₹${Number(item.balance).toLocaleString('en-IN')}`;
                    nodes.pendingList.appendChild(row);
                });
            }

            // 2. Process History
            nodes.historyList.innerHTML = '';
            if (data.history?.length > 0) {
                hasItems = true;
                nodes.historySection.classList.remove('hidden');
                data.history.forEach(item => {
                    const row = templates.tx.content.cloneNode(true);
                    row.querySelector('.tx-category').innerText = item.category;
                    row.querySelector('.tx-date').innerText = item.payment_date;
                    row.querySelector('.tx-amount').innerText = `₹${Number(item.amount).toLocaleString('en-IN')}`;
                    row.querySelector('.receipt-link').href = `/receipts/${item.receipt_number}`;
                    nodes.historyList.appendChild(row);
                });
            }

            // 3. Update Summary Card
            if (totalBalance > 0) {
                nodes.totalDisplay.innerText = totalBalance.toLocaleString('en-IN');
                nodes.summary.classList.remove('hidden');
            }

            // 4. Final Layout Toggle
            if (hasItems) {
                nodes.content.classList.remove('hidden');
            } else {
                nodes.noData.classList.remove('hidden');
            }

            // Stagger Animation Trigger
            const items = nodes.tab.querySelectorAll('.staggered-list > *');
            items.forEach((item, idx) => {
                item.style.animationDelay = `${idx * 0.1}s`;
            });
        }

        // Modals & Forms
        function openModal(id) { document.getElementById('modal-' + id).classList.remove('hidden'); }
        function closeModal(id) {
            const modalId = (id === 'personal') ? 'link' : id;
            const modal = document.getElementById('modal-' + modalId);
            if (modal) modal.classList.add('hidden');
        }

        function openLinkModal(type) {
            document.getElementById('link-type').value = type;
            document.getElementById('link-modal-desc').innerText = `Add ${type === 'father' ? "Father's" : "your"} mobile number.`;
            openModal('link');
        }

        async function submitRequest(e, type) {
            e.preventDefault();
            const formData = new FormData();
            formData.append('_token', "{{ csrf_token() }}");
            formData.append('field_group', type);

            if (type === 'personal') {
                formData.append('mobile_type', document.getElementById('link-type').value);
                formData.append('mobile_number', document.getElementById('input-mobile').value);
            } else if (type === 'address') {
                formData.append('address', document.getElementById('input-address').value);
            } else if (type === 'photo') {
                const fileInput = document.getElementById('input-photo');
                if (fileInput.files.length > 0) {
                    formData.append('photo', fileInput.files[0]);
                }
            } else if (type === 'dob') {
                formData.append('dob', document.getElementById('input-dob').value);
            }

            try {
                const res = await fetch("{{ route('student.request.update') }}", {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();

                if (res.ok) {
                    closeModal(type);
                    if (data.message) showToast(data.message);
                    else showToast('Request Sent!');

                    // Optionally reload page to show pending badge
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert(data.error || 'Something went wrong');
                    if (data.errors) console.log(data.errors);
                }
            } catch (err) {
                console.error(err);
                alert('Connection Error');
            }
        }

        function showToast(msg) {
            const el = document.getElementById('toast');
            el.innerText = msg;
            el.classList.remove('hidden');
            setTimeout(() => el.classList.add('hidden'), 3000);
        }

    </script>
</body>

</html>