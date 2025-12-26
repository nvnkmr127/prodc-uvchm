<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquiry Submitted Successfully</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        .success-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            text-align: center;
            padding: 3rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="success-container">
                    <div class="text-success mb-4">
                        <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
                    </div>
                    <h2 class="text-primary mb-3">Thank You!</h2>
                    <p class="lead mb-4">Your enquiry has been submitted successfully. Our admission team will contact you within 24 hours.</p>
                    <p class="text-muted mb-4">You can also visit our campus or call us directly for immediate assistance.</p>
                    <a href="{{ route('enquiry.public.create') }}" class="btn btn-outline-primary me-2">
                        <i class="fas fa-plus me-2"></i>Submit Another Enquiry
                    </a>
                    <a href="{{ route('home') }}" class="btn btn-primary">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>