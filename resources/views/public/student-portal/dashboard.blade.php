<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: { sans: ['Outfit', 'sans-serif'] },
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
            animation: fadeIn 0.5s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
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
                        <button onclick="openModal('photo')"
                            class="absolute -bottom-2 -right-2 bg-indigo-600 text-white w-7 h-7 rounded-full flex items-center justify-center text-xs shadow-md border-2 border-white">
                            <i class="fa-solid fa-camera"></i>
                        </button>
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
                        @if($student->student_mobile)
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
                        @if($student->father_mobile) <!-- Assumes father_mobile field exists on student model -->
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
                        <span
                            class="text-gray-700">{{ $student->dob ? $student->dob->format('d M, Y') : 'Not recorded' }}</span>
                        @if(!$student->dob)
                            <button onclick="openModal('dob')" class="text-indigo-600 hover:text-indigo-700 text-xs">
                                <i class="fa-solid fa-pen"></i>
                            </button>
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
                <div>
                    <div class="text-[10px] text-gray-400 uppercase font-bold mb-1">Current Address</div>
                    <p class="text-sm text-gray-600 leading-snug">
                        {{ $student->admission->address ?? 'No address provided.' }}
                    </p>
                </div>
                <button onclick="openModal('address')" class="text-gray-400 hover:text-indigo-600 text-xs ml-2"><i
                        class="fa-solid fa-pen"></i></button>
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
            <div id="tab-attendance" class="animate-fade-in">
                <div id="calendar-wrapper" class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="font-bold text-gray-800">{{ now()->format('F Y') }}</h3>
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
                            <div class="w-2 h-2 rounded-full bg-yellow-400"></div> Late
                        </div>
                    </div>
                </div>
            </div>

            <!-- PAYMENTS CONTENT -->
            <div id="tab-payments" class="hidden animate-fade-in space-y-4">
                <div id="payments-loader" class="text-center py-6 text-gray-400 text-xs">Loading records...</div>

                <!-- Pending -->
                <div id="pending-section" class="hidden">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Pending Dues</h4>
                    <div id="pending-list" class="space-y-3"></div>
                </div>

                <!-- History -->
                <div id="history-section" class="hidden">
                    <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Payment History</h4>
                    <div id="history-list" class="space-y-3"></div>
                </div>

                <div id="no-payments" class="hidden text-center py-6 bg-white rounded-xl border border-gray-100">
                    <div class="inline-block p-3 bg-gray-50 rounded-full text-gray-300 mb-2"><i
                            class="fa-solid fa-receipt"></i></div>
                    <p class="text-xs text-gray-400">No payment records found.</p>
                </div>
            </div>
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

        async function loadCalendar() {
            const container = document.getElementById('calendar-days');
            try {
                const res = await fetch("{{ route('student.data.attendance') }}");
                const data = await res.json();

                // Set Badge
                document.getElementById('att-percent-badge').innerText = data.stats.percentage + '%';

                // Build Grid
                container.innerHTML = '';
                const date = new Date(); // Current date (or rely on server month)
                const firstDay = new Date(date.getFullYear(), date.getMonth(), 1).getDay();
                const daysInMonth = new Date(date.getFullYear(), date.getMonth() + 1, 0).getDate();

                // Empty slots
                for (let i = 0; i < firstDay; i++) {
                    container.innerHTML += '<div></div>';
                }

                // Days
                for (let d = 1; d <= daysInMonth; d++) {
                    // Format YYYY-MM-DD
                    const dayString = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    const status = data.calendar[dayString]; // 'present', 'absent', etc.

                    let bgClass = 'bg-gray-50 text-gray-400';
                    if (status === 'present') bgClass = 'bg-emerald-100 text-emerald-700 font-bold';
                    else if (status === 'absent') bgClass = 'bg-red-100 text-red-600 font-bold';
                    else if (status === 'late') bgClass = 'bg-yellow-100 text-yellow-600 font-bold';
                    else if (status === 'holiday') bgClass = 'bg-blue-50 text-blue-400';

                    container.innerHTML += `<div class="aspect-square flex items-center justify-center rounded-lg ${bgClass} text-xs">${d}</div>`;
                }

            } catch (e) {
                container.innerHTML = '<div class="col-span-7 text-center text-red-400 text-xs py-4">Error loading data</div>';
                console.error(e);
            }
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

            if (tab === 'payments') loadPayments();
        }

        // Payment Logic
        let paymentsLoaded = false;
        async function loadPayments() {
            if (paymentsLoaded) return;
            try {
                const res = await fetch("{{ route('student.data.payments') }}");
                const data = await res.json();

                const pList = document.getElementById('pending-list');
                const hList = document.getElementById('history-list');

                pList.innerHTML = '';
                hList.innerHTML = '';
                let hasData = false;

                // Pending
                if (data.pending && data.pending.length > 0) {
                    hasData = true;
                    document.getElementById('pending-section').classList.remove('hidden');
                    data.pending.forEach(item => {
                        pList.innerHTML += `
                            <div class="bg-white p-3 rounded-xl border border-red-100 shadow-sm flex justify-between items-center">
                                <div>
                                    <div class="text-sm font-bold text-gray-800">${item.category}</div>
                                    <div class="text-[10px] text-gray-500 font-medium">Balance: ₹${item.balance}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm font-bold text-gray-900">₹${item.amount}</div>
                                    <span class="text-[9px] bg-red-50 text-red-600 px-1.5 py-0.5 rounded uppercase font-bold">Unpaid</span>
                                </div>
                            </div>
                        `;
                    });
                }

                // History
                if (data.history && data.history.length > 0) {
                    hasData = true;
                    document.getElementById('history-section').classList.remove('hidden');
                    data.history.forEach(item => {
                        const receiptUrl = `/receipts/${item.receipt_number}`;
                        hList.innerHTML += `
                            <div class="bg-white p-3 rounded-xl border border-gray-100 shadow-sm">
                                <div class="flex justify-between items-center mb-2">
                                    <div>
                                        <div class="text-sm font-medium text-gray-800">${item.category}</div>
                                        <div class="text-[10px] text-gray-500 font-medium">${item.payment_date}</div>
                                    </div>
                                    <div class="text-right">
                                        <div class="text-sm font-bold text-gray-500">₹${item.amount}</div>
                                        <span class="text-[9px] bg-green-50 text-green-600 px-1.5 py-0.5 rounded uppercase font-bold">Paid</span>
                                    </div>
                                </div>
                                <a href="${receiptUrl}" target="_blank" class="block w-full text-center bg-indigo-50 text-indigo-600 py-2 rounded-lg text-xs font-medium hover:bg-indigo-100 transition-colors">
                                    <i class="fa-solid fa-receipt mr-1"></i> View Receipt
                                </a>
                            </div>
                       `;
                    });
                }

                if (!hasData) document.getElementById('no-payments').classList.remove('hidden');
                document.getElementById('payments-loader').classList.add('hidden');
                paymentsLoaded = true;

            } catch (e) {
                console.error(e);
                document.getElementById('payments-loader').innerText = 'failed to load.';
            }
        }

        // Modals & Forms
        function openModal(id) { document.getElementById('modal-' + id).classList.remove('hidden'); }
        function closeModal(id) { document.getElementById('modal-' + id).classList.add('hidden'); }

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