<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Register - {{ $settings['college_name']->value ?? config('app.name', 'Laravel') }}</title>

    <!-- Custom fonts for this template-->
    <link href="{{ asset('admin_theme/vendor/fontawesome-free/css/all.min.css') }}" rel="stylesheet" type="text/css">
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i" rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="{{ asset('admin_theme/css/sb-admin-2.min.css') }}" rel="stylesheet">
    <style>
        .bg-register-image-custom {
            background: url('https://source.unsplash.com/McdW2cm1Sog/600x800');
            background-position: center;
            background-size: cover;
        }
    </style>
</head>

<body class="bg-gradient-primary">
    <div class="container">
        <div class="row justify-content-center align-items-center" style="height: 100vh;">
            <div class="col-xl-10 col-lg-12 col-md-9">
                <div class="card o-hidden border-0 shadow-lg my-5">
                    <div class="card-body p-0">
                        <!-- Nested Row within Card Body -->
                        <div class="row">
                            <div class="col-lg-6 d-none d-lg-block bg-register-image-custom"></div>
                            <div class="col-lg-6">
                                <div class="p-5">
                                    <div class="text-center">
                                        <h1 class="h4 text-gray-900 mb-4">Create an Account!</h1>
                                    </div>

                                    <!-- Validation Errors -->
                                    @if ($errors->any())
                                        <div class="alert alert-danger">
                                            <ul class="mb-0">
                                                @foreach ($errors->all() as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    @endif

                                    <form class="user" method="POST" action="{{ route('register') }}">
                                        @csrf
                                        <div class="form-group">
                                            <input type="text" class="form-control form-control-user" id="name" name="name" value="{{ old('name') }}" required autofocus placeholder="Full Name">
                                        </div>
                                        <div class="form-group">
                                            <input type="email" class="form-control form-control-user" id="email" name="email" value="{{ old('email') }}" required placeholder="Email Address">
                                        </div>
                                        <div class="form-group row">
                                            <div class="col-sm-6 mb-3 mb-sm-0">
                                                <input type="password" class="form-control form-control-user" id="password" name="password" required placeholder="Password">
                                            </div>
                                            <div class="col-sm-6">
                                                <input type="password" class="form-control form-control-user" id="password_confirmation" name="password_confirmation" required placeholder="Repeat Password">
                                            </div>
                                        </div>
                                        <button type="submit" class="btn btn-primary btn-user btn-block">
                                            Register Account
                                        </button>
                                    </form>
                                    <hr>
                                    <div class="text-center">
                                        <a class="small" href="{{ route('login') }}">Already have an account? Login!</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap core JavaScript-->
    <script src="{{ asset('admin_theme/vendor/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('admin_theme/vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Core plugin JavaScript-->
    <script src="{{ asset('admin_theme/vendor/jquery-easing/jquery.easing.min.js') }}"></script>

    <!-- Custom scripts for all pages-->
    <script src="{{ asset('admin_theme/js/sb-admin-2.min.js') }}"></script>
</body>
</html>
