<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Forgot Password | Loomexa</title>
    <link rel="icon" href="{{ asset('assets/brand/loomexa-mark.svg') }}" type="image/x-icon">
    <link href="{{ asset('frontend-assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/pe-icon-7-stroke/css/pe-icon-7-stroke.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/themify-icons/themify-icons.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/dist/css/stylecrm.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/dist/css/loomexa_style.css') }}" rel="stylesheet" type="text/css"/>
</head>
<body>
<div class="login-wrapper loomexa-login-page">
    <div class="loomexa-auth-back">
        <a href="{{ route('login') }}" class="btn btn-default">
            <i class="fa fa-angle-left"></i> Back to Login
        </a>
    </div>

    <div class="loomexa-auth-box">
        <div class="front-logo">
            <img src="{{ asset('assets/brand/loomexa-logo.svg') }}" alt="Loomexa">
        </div>

        <div class="panel panel-bd loomexa-auth-card">
            <div class="panel-heading">
                <div class="view-header">
                    <div class="header-icon">
                        <i class="fa fa-envelope"></i>
                    </div>
                    <div class="header-title">
                        <h3>Forgot Password</h3>
                        <small>Enter your email to receive reset link.</small>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                {!! display_message('message') !!}

                <form method="POST" action="{{ route('password.email') }}">
                    @csrf

                    <div class="form-group">
                        <label class="control-label" for="email">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" placeholder="example@gmail.com" required autofocus>
                        <span class="help-block small">Your registered email address</span>
                        @error('email')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>

                    <div class="loomexa-auth-actions">
                        <button type="submit" class="btn btn-add">Send Reset Link</button>
                        <a class="btn btn-warning" href="{{ route('login') }}">Login</a>
                    </div>
                </form>
            </div>

            <div class="loomexa-ip-box">
                <i class="fa fa-globe"></i> Current IP: {{ getIp() }}
            </div>
        </div>
    </div>
</div>
<script src="{{ asset('frontend-assets/plugins/jQuery/jquery-1.12.4.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('frontend-assets/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
</body>
</html>
