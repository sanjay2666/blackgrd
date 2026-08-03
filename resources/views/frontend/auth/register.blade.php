<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Register | Loomexa</title>
    <link rel="icon" href="{{ asset('assets/brand/loomexa-mark.svg') }}" type="image/x-icon">
    <link href="{{ asset('frontend-assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/pe-icon-7-stroke/css/pe-icon-7-stroke.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/themify-icons/themify-icons.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/dist/css/stylecrm.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/dist/css/loomexa_style.css') }}" rel="stylesheet" type="text/css"/>
</head>
<body>
<div class="login-wrapper">
    <div class="back-link front-top-link">
        <a href="{{ route('home') }}" class="btn btn-add">Back to Home</a>
    </div>
    <div class="container-center">
        <div class="front-logo">
            <img src="{{ asset('assets/brand/loomexa-logo.svg') }}" alt="Loomexa">
        </div>
        <div class="login-area">
            <div class="panel panel-bd panel-custom">
                <div class="panel-heading">
                    <div class="view-header">
                        <div class="header-icon">
                            <i class="pe-7s-add-user"></i>
                        </div>
                        <div class="header-title">
                            <h3>User Registration</h3>
                            <small><strong>Create your Loomexa frontend account.</strong></small>
                        </div>
                    </div>
                </div>
            <div class="panel-body">
                {!! display_message('message') !!}
                <form method="POST" action="{{ route('register.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="control-label" for="name">Name</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" class="form-control" placeholder="Your name" required autofocus>
                        @error('name')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="email">Email</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control" placeholder="example@gmail.com" required>
                        @error('email')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="password">Password</label>
                        <input type="password" name="password" id="password" class="form-control" placeholder="******" required>
                        @error('password')<span class="text-danger small">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label class="control-label" for="password_confirmation">Confirm Password</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" class="form-control" placeholder="******" required>
                    </div>
                    <button type="submit" class="btn btn-add">Register</button>
                    <a class="btn btn-warning" href="{{ route('login') }}">Login</a>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
<script src="{{ asset('frontend-assets/plugins/jQuery/jquery-1.12.4.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('frontend-assets/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
</body>
</html>

