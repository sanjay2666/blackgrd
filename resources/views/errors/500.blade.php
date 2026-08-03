<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loomexa - Server Error</title>

    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=50">
    <link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=50">

    <link href="{{ asset('frontend-assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">

    <style>
        body { background:#f4f6f9; font-family:Arial, Helvetica, sans-serif; }
        .error-page-wrap { min-height:100vh; display:flex; align-items:center; justify-content:center; padding:30px 15px; }
        .error-box { width:720px; max-width:100%; background:#fff; border:1px solid #ddd; border-radius:8px; box-shadow:0 8px 25px rgba(0,0,0,0.12); overflow:hidden; }
        .error-header { background:#2f4050; color:#fff; padding:18px 25px; }
        .error-header h3 { margin:0; font-size:22px; font-weight:600; }
        .error-body { padding:40px 35px; text-align:center; }
        .error-code { font-size:92px; line-height:90px; font-weight:700; color:#d9534f; margin-bottom:15px; }
        .error-title { font-size:26px; color:#243b63; margin:0 0 12px; font-weight:600; }
        .error-text { font-size:15px; color:#777; line-height:24px; margin-bottom:25px; }
        .error-actions .btn { min-width:140px; margin:4px; }
        .error-footer { background:#f9f9f9; border-top:1px solid #eee; padding:12px 20px; text-align:center; color:#999; font-size:13px; }
        .error-icon { width:95px; height:95px; border-radius:50%; background:#f9e7e7; color:#d9534f; display:inline-flex; align-items:center; justify-content:center; font-size:42px; margin-bottom:18px; }
    </style>
</head>

<body>

<div class="error-page-wrap">
    <div class="error-box">

        <div class="error-header">
            <h3><i class="fa fa-exclamation-triangle"></i> Loomexa</h3>
        </div>

        <div class="error-body">
            <div class="error-icon"><i class="fa fa-server"></i></div>

            <div class="error-code">500</div>

            <h2 class="error-title">Internal Server Error</h2>

            <p class="error-text">
                Something went wrong while processing your request.<br>
                Please try again after some time. If the problem continues, contact your system administrator.
            </p>

            <div class="error-actions">
                <a href="{{ url()->previous() }}" class="btn btn-default"><i class="fa fa-arrow-left"></i> Go Back</a>
                <a href="{{ url('/') }}" class="btn btn-primary"><i class="fa fa-home"></i> Home Page</a>
            </div>
        </div>

        <div class="error-footer">
            Error Reference: 500 &nbsp; | &nbsp; {{ date('d-m-Y h:i A') }}
        </div>

    </div>
</div>

</body>
</html>