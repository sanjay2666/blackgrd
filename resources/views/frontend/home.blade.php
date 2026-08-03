<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Loomexa | Textile Production ERP</title>

    <link rel="icon" href="{{ asset('assets/brand/loomexa-mark.svg') }}" type="image/x-icon">
    <link href="{{ asset('frontend-assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/font-awesome/css/font-awesome.min.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('frontend-assets/dist/css/loomexa_style.css') }}" rel="stylesheet" type="text/css"/>
    <link href="{{ asset('frontend-assets/bootstrap/css/homepage_loomexa.css') }}" rel="stylesheet" type="text/css">
</head>
<body>

<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a class="site-logo" href="{{ route('home') }}">
                <img src="{{ asset('assets/brand/loomexa-logo.svg') }}" alt="Loomexa">
            </a>

            <ul class="main-menu">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#features">ERP Features</a></li>
                <li><a href="#process">Production Process</a></li>
                <li><a href="#contact">Contact</a></li>
                <li><a class="login-link" href="{{ route('login') }}">Employee Login</a></li>
            </ul>
        </div>
    </div>
</header>

<section id="home" class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-7">
                <div class="hero-label">Integrated Textile Production ERP</div>
                <h1>Smart Textile Production. Complete Process Control.</h1>
                <p>
                    Loomexa connects sales order, weaving, dyeing, coating, inspection, warehouse,
                    packaging and dispatch work in one simple ERP system.
                </p>
                <a href="#features" class="btn-main">Explore ERP</a>
                <a href="{{ route('login') }}" class="btn-light">Employee Login</a>
            </div>

            <div class="col-sm-5">
                <div class="hero-box">
                    <h3>Production Snapshot</h3>
                    <div class="hero-row">Sales Orders <span>142 Active</span></div>
                    <div class="hero-row">Weaving Lots <span>38 Running</span></div>
                    <div class="hero-row">Dyeing Batches <span>21 Running</span></div>
                    <div class="hero-row">Ready Dispatch <span>17 Orders</span></div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="process" class="section-space">
    <div class="container">
        <div class="section-heading">
            <h2>Production Process</h2>
            <p>Track every stage of textile manufacturing from customer order to final dispatch.</p>
        </div>

        <div class="row card-list">
            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-file-text-o"></i></div>
                    <h3>Sales Order</h3>
                    <p>Convert customer requirements into controlled production orders.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-industry"></i></div>
                    <h3>Weaving</h3>
                    <p>Plan machines, yarn consumption and fabric production.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-tint"></i></div>
                    <h3>Dyeing</h3>
                    <p>Track dyeing batches, shades and process movement.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-magic"></i></div>
                    <h3>Coating</h3>
                    <p>Control coating, finishing and value-added processes.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-check-square-o"></i></div>
                    <h3>Quality Inspection</h3>
                    <p>Record inspection results before stock movement.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-cubes"></i></div>
                    <h3>Warehouse</h3>
                    <p>Maintain raw material and finished stock visibility.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-archive"></i></div>
                    <h3>Packaging</h3>
                    <p>Prepare finished goods for dispatch readiness.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-3">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-truck"></i></div>
                    <h3>Dispatch</h3>
                    <p>Monitor delivery flow and customer dispatch status.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="section-space white-section">
    <div class="container">
        <div class="section-heading">
            <h2>ERP Features</h2>
            <p>Simple modules for textile manufacturing, production tracking and stock control.</p>
        </div>

        <div class="row card-list">
            <div class="col-sm-6 col-md-4">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-shopping-cart"></i></div>
                    <h3>Sales Orders</h3>
                    <p>Manage customer orders, delivery dates and production requirements.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-calendar"></i></div>
                    <h3>Production Planning</h3>
                    <p>Plan process-wise production load and pending work.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-clipboard"></i></div>
                    <h3>Work Orders</h3>
                    <p>Control work orders across all production departments.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-database"></i></div>
                    <h3>Stock Management</h3>
                    <p>Track raw material, semi-finished goods and finished stock.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-bar-chart"></i></div>
                    <h3>Reports</h3>
                    <p>View production, stock, order and dispatch reports.</p>
                </div>
            </div>

            <div class="col-sm-6 col-md-4">
                <div class="simple-card">
                    <div class="card-icon"><i class="fa fa-bell"></i></div>
                    <h3>Notifications</h3>
                    <p>Get useful alerts for pending work and important updates.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="about" class="section-space">
    <div class="container">
        <div class="row">
            <div class="col-sm-6">
                <div class="about-text">
                    <h2>Complete Production Visibility</h2>
                    <p>
                        Loomexa helps textile teams see work status, department movement,
                        material availability and dispatch readiness from one place.
                    </p>

                    <ul class="check-list">
                        <li><i class="fa fa-check"></i>Department-wise work order tracking</li>
                        <li><i class="fa fa-check"></i>Machine and production planning</li>
                        <li><i class="fa fa-check"></i>Lot, PCS and meter traceability</li>
                        <li><i class="fa fa-check"></i>Quality inspection records</li>
                        <li><i class="fa fa-check"></i>Warehouse and dispatch control</li>
                    </ul>
                </div>
            </div>

            <div class="col-sm-6">
                <div class="summary-box">
                    <h3>Live Summary</h3>
                    <div class="summary-item"><strong>128</strong> Active Work Orders</div>
                    <div class="summary-item"><strong>46</strong> Production in Progress</div>
                    <div class="summary-item"><strong>18</strong> Pending Inspection</div>
                    <div class="summary-item"><strong>32</strong> Ready for Dispatch</div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="section-space white-section">
    <div class="container">
        <div class="section-heading">
            <h2>Department Access</h2>
            <p>Each department can work with its own ERP access and required production data.</p>
        </div>

        <div class="row">
            <div class="col-xs-6 col-sm-4 col-md-2">
                <div class="department-box"><i class="fa fa-users"></i>Management</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
                <div class="department-box"><i class="fa fa-user"></i>Sales</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
                <div class="department-box"><i class="fa fa-industry"></i>Weaving</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
                <div class="department-box"><i class="fa fa-tint"></i>Dyeing</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
                <div class="department-box"><i class="fa fa-magic"></i>Coating</div>
            </div>
            <div class="col-xs-6 col-sm-4 col-md-2">
                <div class="department-box"><i class="fa fa-truck"></i>Dispatch</div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container">
        <div class="row">
            <div class="col-sm-8">
                <h2>One ERP for Textile Production Control</h2>
                <p>Bring production planning, process tracking, stock, inspection and dispatch into Loomexa.</p>
            </div>
            <div class="col-sm-4 cta-buttons">
                <a href="{{ route('login') }}" class="btn-main">Enter ERP</a>
                <a href="#contact" class="btn-light">Contact</a>
            </div>
        </div>
    </div>
</section>

<section id="contact" class="section-space">
    <div class="container">
        <div class="section-heading">
            <h2>Contact</h2>
            <p>Company contact details can be updated later.</p>
        </div>

        <div class="contact-box">
            <div class="row">
                <div class="col-sm-3">
                    <strong>Company</strong><br>
                    Loomexa
                </div>
                <div class="col-sm-3">
                    <strong>Industry</strong><br>
                    Textile Manufacturing
                </div>
                <div class="col-sm-3">
                    <strong>Location</strong><br>
                    Surat, Gujarat, India
                </div>
                <div class="col-sm-3">
                    <strong>Email</strong><br>
                    contact@example.com
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="row">
            <div class="col-sm-4">
                <div class="footer-brand">
                    <img src="{{ asset('assets/brand/loomexa-mark.svg') }}" alt="Loomexa">
                    <span>Loomexa</span>
                </div>
                <p>Loomexa is a textile manufacturing ERP for production, stock, quality and dispatch visibility.</p>
            </div>

            <div class="col-sm-2">
                <h3>Links</h3>
                <a href="#home">Home</a>
                <a href="#about">About</a>
                <a href="#features">ERP Features</a>
                <a href="#contact">Contact</a>
            </div>

            <div class="col-sm-3">
                <h3>Process</h3>
                <a href="#process">Sales Order</a>
                <a href="#process">Weaving</a>
                <a href="#process">Dyeing</a>
                <a href="#process">Dispatch</a>
            </div>

            <div class="col-sm-3">
                <h3>Access</h3>
                <a href="{{ route('login') }}">Employee Login</a>
            </div>
        </div>

        <div class="footer-bottom">
            &copy; {{ date('Y') }} Loomexa. All Rights Reserved.
        </div>
    </div>
</footer>

<script src="{{ asset('frontend-assets/plugins/jQuery/jquery-1.12.4.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('frontend-assets/bootstrap/js/bootstrap.min.js') }}" type="text/javascript"></script>
<script type="text/javascript">
    var links = document.querySelectorAll('a[href^="#"]');

    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function(event) {
            var section = document.querySelector(this.getAttribute('href'));

            if (section) {
                event.preventDefault();
                section.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }
</script>
</body>
</html>
