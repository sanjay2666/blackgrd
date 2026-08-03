<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Login | Loomexa</title>
    <link rel="icon" href="{{ asset('assets/brand/loomexa-mark.svg') }}" type="image/x-icon">
    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/pe-icon-7-stroke/css/pe-icon-7-stroke.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('assets/dist/css/stylecrm.css') }}" rel="stylesheet" type="text/css">
</head>
<body>
    <div class="login-wrapper">
        <div class="back-link">
            <a href="{{ route('home') }}" class="btn btn-add">Back to Home</a>
        </div>
        <div class="container-center">
            <div class="login-area">
                <div class="panel panel-bd panel-custom">
                    <div class="panel-heading">
                        <div class="view-header">
                            <div class="header-icon"><i class="pe-7s-unlock"></i></div>
                            <div class="header-title">
                                <h3>Admin Login</h3>
                                <small><strong>Please enter your admin credentials to login.</strong></small>
                            </div>
                        </div>
                    </div>
                    <div class="panel-body">
                        @if (session('status'))
                            <div class="alert alert-success">{{ session('status') }}</div>
                        @endif
                        <form method="POST" action="{{ route('admin.login.store') }}" id="loginForm">
                            @csrf
                            <div class="form-group">
                                <label class="control-label" for="email">Email</label>
                                <input type="email" placeholder="admin@blackgrd.test" required name="email" id="email" value="{{ old('email') }}" class="form-control" autofocus>
                                @error('email')<span class="help-block text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="form-group">
                                <label class="control-label" for="password">Password</label>
                                <input type="password" placeholder="******" required name="password" id="password" class="form-control">
                                @error('password')<span class="help-block text-danger small">{{ $message }}</span>@enderror
                            </div>
                            <div class="checkbox"><label><input type="checkbox" name="remember" value="1"> Remember me</label></div>
                            <div><button type="submit" class="btn btn-add">Login</button></div>
                        </form>
                        <hr>
                        <p class="small text-muted">Default admin: admin@blackgrd.test / Admin@12345</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/plugins/jQuery/jquery-1.12.4.min.js') }}" type="text/javascript"></script>
    <script src="{{ asset('assets/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
</body>
</html>

