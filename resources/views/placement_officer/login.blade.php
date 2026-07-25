<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Placement Officer Login</title>
    <!-- Custom fonts and styles -->
    <link href="{{ asset('admin_theme/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('admin_theme/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <link href="{{ asset('css/modern-theme.css') }}?v={{ time() }}" rel="stylesheet">
</head>

<body class="d-flex align-items-center min-vh-100 py-5" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-xl-5 col-lg-6 col-md-8">
                <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden; background: #ffffff;">
                    <div class="card-body p-4 p-md-5">
                        <div class="text-center mb-4">
                            <div class="d-inline-flex align-items-center justify-content-center bg-primary text-white rounded-circle mb-3 shadow" style="width: 64px; height: 64px; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%) !important;">
                                <i class="fas fa-graduation-cap fa-2x"></i>
                            </div>
                            <h2 class="h4 text-dark font-weight-bold mb-1">Placement Portal</h2>
                            <p class="text-muted small">Enter your registered mobile number to log in</p>
                        </div>

                        @if ($errors->any())
                            <div class="alert alert-danger rounded-lg">
                                <ul class="mb-0 pl-3 small">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <form method="POST" action="{{ route('placement-officer.login.submit') }}">
                            @csrf
                            <div class="form-group mb-4">
                                <label class="font-weight-bold text-dark small"><i class="fas fa-mobile-alt mr-1 text-primary"></i> Mobile Number</label>
                                <input type="tel" class="form-control form-control-lg" style="border-radius: 12px; font-size: 1rem;"
                                    name="phone" placeholder="Enter Mobile Number..." required autofocus value="{{ old('phone') }}">
                            </div>

                            <button type="submit" class="btn btn-primary btn-lg btn-block shadow-sm" style="border-radius: 12px; font-weight: 600; background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%); border: none;">
                                <i class="fas fa-sign-in-alt mr-2"></i> Log In
                            </button>
                        </form>

                        <hr class="my-4">
                        
                        <div class="text-center">
                            <a class="small text-muted font-weight-bold" href="{{ route('login') }}"><i class="fas fa-arrow-left mr-1"></i> Main System Login</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('admin_theme/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
</body>
</html>

