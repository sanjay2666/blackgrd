<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | Loomexa</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20">
	<link rel="shortcut icon" type="image/x-icon" href="{{ asset('favicon.ico') }}?v=20">
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
        <a href="{{ route('home') }}" class="btn btn-default">
            <i class="fa fa-angle-left"></i> Back to Home
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
                        <i class="fa fa-lock"></i>
                    </div>
                    <div class="header-title">
                        <h3>Employee Login</h3>
                        <small>Please enter your email and password.</small>
                    </div>
                </div>
            </div>

            <div class="panel-body">
                {!! display_message('message') !!}

                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif

                <form method="POST" action="{{ route('login.store') }}">
                    @csrf

                    <div class="form-group">
                        <label class="control-label" for="email">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" placeholder="example@gmail.com" required autofocus>
                        <span class="help-block small">Your registered email address</span>
                        @error('email')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>

                    <div class="form-group">
                        <label class="control-label" for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="******" required>
                        <span class="help-block small">Your account password</span>
                        @error('password')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>

                    <div class="checkbox loomexa-auth-row">
                        <label>
                            <input type="checkbox" name="remember" value="1"> Remember me
                        </label>
                        <a href="{{ route('password.request') }}" class="pull-right loomexa-forgot-link">Forgot Password?</a>
                    </div>

                    <div class="loomexa-auth-actions">
                        <button type="submit" class="btn btn-add">Login</button>
                        <a class="btn btn-warning" href="{{ route('register') }}">Register</a>
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

