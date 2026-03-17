<!DOCTYPE html>
<html lang="en" class="h-full">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Sign In - {{ $settings['college_name']->value ?? config('app.name', 'Laravel') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">

    <!-- Tailwind CSS (CDN) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            800: '#1e40af',
                            900: '#1e3a8a',
                            950: '#172554',
                        }
                    },
                    animation: {
                        'fade-in': 'fadeIn 0.5s ease-out',
                        'slide-up': 'slideUp 0.5s ease-out',
                        'blob': 'blob 7s infinite',
                    },
                    keyframes: {
                        fadeIn: {
                            '0%': {
                                opacity: '0'
                            },
                            '100%': {
                                opacity: '1'
                            },
                        },
                        slideUp: {
                            '0%': {
                                transform: 'translateY(20px)',
                                opacity: '0'
                            },
                            '100%': {
                                transform: 'translateY(0)',
                                opacity: '1'
                            },
                        },
                        blob: {
                            '0%': {
                                transform: 'translate(0px, 0px) scale(1)'
                            },
                            '33%': {
                                transform: 'translate(30px, -50px) scale(1.1)'
                            },
                            '66%': {
                                transform: 'translate(-20px, 20px) scale(0.9)'
                            },
                            '100%': {
                                transform: 'translate(0px, 0px) scale(1)'
                            },
                        }
                    }
                }
            }
        }
    </script>
    <style>
        /* Custom Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.5);
        }

        .input-group:focus-within label {
            color: #2563eb;
        }

        .input-group:focus-within svg {
            color: #2563eb;
        }
    </style>
</head>

<body class="h-full font-sans antialiased text-gray-900 bg-gray-50 overflow-x-hidden">
    <div class="min-h-screen flex">

        <!-- Left Side: Visual/Branding (Animated) -->
        <div class="hidden lg:flex lg:w-1/2 relative bg-brand-900 overflow-hidden items-center justify-center">
            <!-- Animated Background -->
            <div class="absolute inset-0 bg-gradient-to-br from-brand-900 to-black"></div>
            <div
                class="absolute -top-24 -left-24 w-96 h-96 bg-brand-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob">
            </div>
            <div
                class="absolute top-0 -right-4 w-96 h-96 bg-purple-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-2000">
            </div>
            <div
                class="absolute -bottom-8 left-20 w-96 h-96 bg-pink-500 rounded-full mix-blend-multiply filter blur-3xl opacity-20 animate-blob animation-delay-4000">
            </div>

            <!-- Pattern Overlay -->
            <div
                class="absolute inset-0 bg-[url('https://grainy-gradients.vercel.app/noise.svg')] opacity-20 brightness-100 contrast-150">
            </div>

            <div class="relative z-10 p-16 text-white text-center">
                <div class="mb-8 transform transition hover:scale-105 duration-500">
                    @if(isset($settings['college_logo']))
                        <div class="bg-white p-6 rounded-3xl shadow-2xl inline-block border border-gray-100">
                            <img src="{{ asset('storage/' . $settings['college_logo']->value) }}" alt="Logo"
                                class="h-24 w-auto">
                        </div>
                    @else
                        <div class="bg-white p-6 rounded-3xl shadow-2xl inline-block border border-gray-100">
                            <span
                                class="text-3xl font-bold tracking-widest uppercase text-brand-600">{{ config('app.name') }}</span>
                        </div>
                    @endif
                </div>

                <h1 class="text-5xl font-extrabold mb-6 tracking-tight leading-tight drop-shadow-lg">
                    Welcome to <span
                        class="text-transparent bg-clip-text bg-gradient-to-r from-brand-200 to-white">{{ $settings['college_name']->value ?? config('app.name') }}</span>
                </h1>
                <p class="text-brand-100 text-xl font-light max-w-lg mx-auto leading-relaxed">
                    A secure, modern platform for managing your academic journey.
                </p>
            </div>
        </div>

        <!-- Right Side: Login Form (Interactive) -->
        <div
            class="flex-1 flex flex-col justify-center items-center py-12 px-4 sm:px-6 lg:px-20 xl:px-24 bg-gray-50 relative overflow-hidden">

            <!-- Moving Blobs for Form Background -->
            <div
                class="absolute top-10 right-10 w-72 h-72 bg-purple-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob">
            </div>
            <div
                class="absolute bottom-10 left-10 w-72 h-72 bg-brand-300 rounded-full mix-blend-multiply filter blur-xl opacity-30 animate-blob animation-delay-2000">
            </div>

            <div class="w-full max-w-sm lg:w-[500px] relative z-20 perspective-1000">

                <!-- 3D Tilt Card -->
                <div class="glass-card rounded-[2rem] shadow-2xl p-10 sm:p-12 transform transition-all hover:shadow-brand-500/20"
                    data-tilt data-tilt-max="3" data-tilt-speed="400" data-tilt-glare data-tilt-max-glare="0.2">

                    <div class="text-center mb-10">
                        <h2 class="text-4xl font-bold text-gray-900 mb-2 tracking-tight">Sign In</h2>
                        <p class="text-lg text-gray-500">Access your dashboard</p>
                    </div>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div
                            class="mb-6 rounded-2xl bg-green-50/80 backdrop-blur-sm p-4 border border-green-100 flex items-center shadow-sm">
                            <svg class="h-6 w-6 text-green-500 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span class="text-base font-medium text-green-800">{{ session('status') }}</span>
                        </div>
                    @endif

                    <!-- Validation Errors -->
                    @if ($errors->any())
                        <div
                            class="mb-6 rounded-2xl bg-red-50/80 backdrop-blur-sm p-4 border border-red-100 shadow-sm animate-pulse">
                            <ul class="list-disc list-inside text-sm text-red-600 font-medium">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('login') }}" method="POST" class="space-y-8">
                        @csrf

                        <!-- Animated Input: Email -->
                        <div class="relative group input-group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                <svg class="h-6 w-6 text-gray-400 transition-colors duration-300"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input type="email" id="email" name="email" required autofocus value="{{ old('email') }}"
                                class="peer block w-full pl-12 pr-4 py-4 text-lg bg-gray-50/50 border-2 border-gray-100 rounded-2xl focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-300 outline-none"
                                placeholder=" " />
                            <label for="email" class="absolute left-12 top-4 text-lg text-gray-400 transition-all duration-300 pointer-events-none
                                peer-focus:-translate-y-7 peer-focus:bg-white peer-focus:px-2 peer-focus:text-sm peer-focus:font-semibold
                                peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0
                                peer-not-placeholder-shown:-translate-y-7 peer-not-placeholder-shown:bg-white peer-not-placeholder-shown:px-2 peer-not-placeholder-shown:text-sm peer-not-placeholder-shown:font-semibold
                                ">
                                Email
                            </label>
                        </div>

                        <!-- Animated Input: Password -->
                        <div class="relative group input-group">
                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none z-10">
                                <svg class="h-6 w-6 text-gray-400 transition-colors duration-300"
                                    xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input type="password" id="password" name="password" required
                                autocomplete="current-password"
                                class="peer block w-full pl-12 pr-4 py-4 text-lg bg-gray-50/50 border-2 border-gray-100 rounded-2xl focus:border-brand-500 focus:bg-white focus:ring-4 focus:ring-brand-500/10 transition-all duration-300 outline-none"
                                placeholder=" " />
                            <label for="password" class="absolute left-12 top-4 text-lg text-gray-400 transition-all duration-300 pointer-events-none
                                peer-focus:-translate-y-7 peer-focus:bg-white peer-focus:px-2 peer-focus:text-sm peer-focus:font-semibold
                                peer-placeholder-shown:scale-100 peer-placeholder-shown:translate-y-0
                                peer-not-placeholder-shown:-translate-y-7 peer-not-placeholder-shown:bg-white peer-not-placeholder-shown:px-2 peer-not-placeholder-shown:text-sm peer-not-placeholder-shown:font-semibold
                                ">
                                Password
                            </label>
                        </div>

                        <div class="flex items-center justify-between">
                            <label class="flex items-center cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" name="remember" class="sr-only peer">
                                    <div
                                        class="w-10 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-brand-600">
                                    </div>
                                </div>
                                <span
                                    class="ml-3 text-sm font-medium text-gray-600 group-hover:text-gray-900 transition-colors">Remember
                                    me</span>
                            </label>

                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}"
                                    class="text-sm font-semibold text-brand-600 hover:text-brand-700 hover:underline decoration-2 underline-offset-4 transition-all">
                                    Forgot password?
                                </a>
                            @endif
                        </div>

                        <button type="submit"
                            class="relative w-full py-4 bg-gray-900 text-white text-lg font-bold rounded-2xl overflow-hidden group shadow-xl hover:shadow-2xl transition-all duration-300 transform hover:-translate-y-1">
                            <span
                                class="absolute inset-0 w-full h-full bg-gradient-to-r from-brand-600 to-brand-400 opacity-0 group-hover:opacity-100 transition-opacity duration-300 ease-out"></span>
                            <span class="relative flex items-center justify-center">
                                Sign In
                                <svg class="ml-2 w-5 h-5 transform group-hover:translate-x-1 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13 7l5 5m0 0l-5 5m5-5H6"></path>
                                </svg>
                            </span>
                        </button>
                    </form>

                    <div class="mt-8 text-center">
                        <p class="text-sm text-gray-400">
                            &copy; {{ date('Y') }} {{ config('app.name') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <!-- Vanilla Tilt for 3D Effect -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vanilla-tilt/1.8.0/vanilla-tilt.min.js"></script>
    <script>
        // Init Tilt if needed explicitly, though data-tilt does it auto
        // Input Logic to handle manual checks for floating labels if needed (CSS :placeholder-shown handles most)
    </script>
</body>

</html>