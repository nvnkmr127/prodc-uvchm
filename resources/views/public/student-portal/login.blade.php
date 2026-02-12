<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal Login</title>
    <!-- Tailwind CSS (via CDN for simplicity, or use local build) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600&display=swap" rel="stylesheet">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        glass: "rgba(255, 255, 255, 0.1)",
                        glassBorder: "rgba(255, 255, 255, 0.2)",
                    }
                }
            }
        }
    </script>
    <style>
        body {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .input-field {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: white;
        }

        .input-field:focus {
            border-color: #6366f1;
            outline: none;
            box-shadow: 0 0 15px rgba(99, 102, 241, 0.3);
        }
    </style>
</head>

<body class="flex items-center justify-center p-4">

    <div class="glass-card rounded-2xl w-full max-w-md p-8 shadow-2xl relative overflow-hidden">
        <!-- Decorative Glow -->
        <div class="absolute -top-10 -left-10 w-32 h-32 bg-indigo-500 rounded-full blur-[80px] opacity-20"></div>
        <div class="absolute -bottom-10 -right-10 w-32 h-32 bg-pink-500 rounded-full blur-[80px] opacity-20"></div>

        <div class="relative z-10">
            <h1 class="text-3xl font-bold text-white text-center mb-2">Student Portal</h1>
            <p class="text-gray-400 text-center mb-8 text-sm">Access your academic dashboard</p>

            @if ($errors->any())
                <div class="mb-4 bg-red-500/10 border border-red-500/20 text-red-200 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('student.authenticate') }}" method="POST" class="space-y-6">
                @csrf

                <div>
                    <label class="block text-gray-400 text-xs uppercase tracking-wider mb-2">Enrollment Number</label>
                    <input type="text" name="enrollment_number" placeholder="e.g. UV2024001"
                        class="input-field w-full px-4 py-3 rounded-lg text-lg placeholder-gray-600 transition-all font-mono"
                        value="{{ old('enrollment_number') }}" required>
                </div>

                <div>
                    <label class="block text-gray-400 text-xs uppercase tracking-wider mb-2">Registered Mobile</label>
                    <input type="tel" name="mobile_number" placeholder="9876543210"
                        class="input-field w-full px-4 py-3 rounded-lg text-lg placeholder-gray-600 transition-all font-mono"
                        value="{{ old('mobile_number') }}" required>
                    <p class="text-xs text-gray-500 mt-2">Enter your 10-digit registered mobile number.</p>
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-bold py-3.5 rounded-xl shadow-lg transform transition hover:scale-[1.02] active:scale-[0.98]">
                    Access Dashboard
                </button>
            </form>

            <div class="mt-8 text-center">
                <p class="text-xs text-gray-500">
                    Having trouble? Contact Administration<br>
                    <span class="opacity-50">Zero-Provisioning Security System</span>
                </p>
            </div>
        </div>
    </div>

    <script>
        // Refresh CSRF Token on load to bypass stale cached tokens
        window.addEventListener('DOMContentLoaded', async () => {
            try {
                const response = await fetch("{{ route('student.refresh-csrf') }}");
                const data = await response.json();
                if (data.token) {
                    const tokenInputs = document.querySelectorAll('input[name="_token"]');
                    tokenInputs.forEach(input => input.value = data.token);
                    console.log('CSRF token refreshed');
                }
            } catch (error) {
                console.error('Failed to refresh CSRF token:', error);
            }
        });
    </script>
</body>

</html>