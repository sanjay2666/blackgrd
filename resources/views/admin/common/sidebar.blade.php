<aside class="main-sidebar">
<!-- sidebar -->
<div class="sidebar">
   <!-- sidebar menu -->
   <ul class="sidebar-menu">
	  <li class="active">
		 <a href="{{ route('admin.dashboard') }}"><i class="fa fa-tachometer"></i><span>Dashboard</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	<?php
		$mastersOpen = request()->routeIs('admin.individuals.*') || request()->routeIs('admin.departments.*') || request()->routeIs('admin.process-items.*') || request()->routeIs('admin.machines.*') || request()->routeIs('admin.office-ips.*') || request()->routeIs('admin.states.*') || request()->routeIs('admin.all-pages.*') || request()->routeIs('admin.colours.*') || request()->routeIs('admin.companies.*') || request()->routeIs('admin.cotings.*') || request()->routeIs('admin.couriers.*') || request()->routeIs('admin.gst-rates.*') || request()->routeIs('admin.item-types.*') || request()->routeIs('admin.items.*') || request()->routeIs('admin.item-yarn-requirements.*') || request()->routeIs('admin.notifications.*') || request()->routeIs('admin.packaging-types.*') || request()->routeIs('admin.unit-types.*') || request()->routeIs('admin.user-web-pages.*') || request()->routeIs('admin.warehouses.*') || request()->routeIs('admin.ware-house-compartments.*') || request()->routeIs('admin.login-attempts.*') || request()->routeIs('admin.login-otps.*') || request()->routeIs('admin.user-activity-logs.*');
	?>
	
  <li class="treeview {{ $mastersOpen ? 'active menu-open' : '' }}">
     <a href="javascript:void(0);"><i class="fa fa-users"></i><span>Masters</span><span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i></span></a>
     <ul class="treeview-menu" style="{{ $mastersOpen ? 'display:block;' : '' }}">
        <li class="{{ request()->routeIs('admin.individuals.*') ? 'active' : '' }}"><a href="{{ route('admin.individuals.index') }}">Individual</a></li>
        <li class="{{ request()->routeIs('admin.departments.*') ? 'active' : '' }}"><a href="{{ route('admin.departments.index') }}">Departments</a></li>
        <li class="{{ request()->routeIs('admin.process-items.*') ? 'active' : '' }}"><a href="{{ route('admin.process-items.index') }}">Process Items</a></li>
        <li class="{{ request()->routeIs('admin.machines.*') ? 'active' : '' }}"><a href="{{ route('admin.machines.index') }}">Machines</a></li>
        <li class="{{ request()->routeIs('admin.office-ips.*') ? 'active' : '' }}"><a href="{{ route('admin.office-ips.index') }}">Office IPs</a></li>
        <li class="{{ request()->routeIs('admin.states.*') ? 'active' : '' }}"><a href="{{ route('admin.states.index') }}">States</a></li>
        <li class="{{ request()->routeIs('admin.all-pages.*') ? 'active' : '' }}"><a href="{{ route('admin.all-pages.index') }}">All Pages</a></li>
        <li class="{{ request()->routeIs('admin.colours.*') ? 'active' : '' }}"><a href="{{ route('admin.colours.index') }}">Colours</a></li>
        <li class="{{ request()->routeIs('admin.companies.*') ? 'active' : '' }}"><a href="{{ route('admin.companies.index') }}">Companies</a></li>
        <li class="{{ request()->routeIs('admin.cotings.*') ? 'active' : '' }}"><a href="{{ route('admin.cotings.index') }}">Cotings</a></li>
        <li class="{{ request()->routeIs('admin.couriers.*') ? 'active' : '' }}"><a href="{{ route('admin.couriers.index') }}">Couriers</a></li>
        <li class="{{ request()->routeIs('admin.gst-rates.*') ? 'active' : '' }}"><a href="{{ route('admin.gst-rates.index') }}">GST Rates</a></li>
        <li class="{{ request()->routeIs('admin.item-types.*') ? 'active' : '' }}"><a href="{{ route('admin.item-types.index') }}">Item Types</a></li>
        <li class="{{ request()->routeIs('admin.items.*') ? 'active' : '' }}"><a href="{{ route('admin.items.index') }}">Items</a></li>
        <li class="{{ request()->routeIs('admin.item-yarn-requirements.*') ? 'active' : '' }}"><a href="{{ route('admin.item-yarn-requirements.index') }}">Item Yarn Requirements</a></li>
        <li class="{{ request()->routeIs('admin.notifications.*') ? 'active' : '' }}"><a href="{{ route('admin.notifications.index') }}">Notifications</a></li>
        <li class="{{ request()->routeIs('admin.packaging-types.*') ? 'active' : '' }}"><a href="{{ route('admin.packaging-types.index') }}">Packaging Types</a></li>
        <li class="{{ request()->routeIs('admin.unit-types.*') ? 'active' : '' }}"><a href="{{ route('admin.unit-types.index') }}">Unit Types</a></li>
        <li class="{{ request()->routeIs('admin.user-web-pages.*') ? 'active' : '' }}"><a href="{{ route('admin.user-web-pages.index') }}">User Web Pages</a></li>
        <li class="{{ request()->routeIs('admin.warehouses.*') ? 'active' : '' }}"><a href="{{ route('admin.warehouses.index') }}">Warehouses</a></li>
        <li class="{{ request()->routeIs('admin.ware-house-compartments.*') ? 'active' : '' }}"><a href="{{ route('admin.ware-house-compartments.index') }}">Warehouse Compartments</a></li>
        <li class="{{ request()->routeIs('admin.login-attempts.*') ? 'active' : '' }}"><a href="{{ route('admin.login-attempts.index') }}">Login Attempts</a></li>
        <li class="{{ request()->routeIs('admin.login-otps.*') ? 'active' : '' }}"><a href="{{ route('admin.login-otps.index') }}">Login OTPs</a></li>
        <li class="{{ request()->routeIs('admin.user-activity-logs.*') ? 'active' : '' }}"><a href="{{ route('admin.user-activity-logs.index') }}">User Activity Logs</a></li>
     </ul>
  </li>
    
	
	<li class="treeview">
		 <a href="javascript:void(0);"><i class="fa fa-shopping-cart"></i><span>Sales</span> <span class="pull-right-container"><i class="fa fa-angle-left pull-right"></i> </span> </a>
		 <ul class="treeview-menu">
			<li><a href="invoice.html">Invoices</a></li>
			<li><a href="ninvoices.html">New Invoices</a></li>
			<li><a href="recurring.html">Recurring invoices</a></li>
			<li><a href="nrecurring.html">New Recurring invoices</a></li>
			<li><a href="quote.html">quotes</a></li>
			<li><a href="nquote.html">New quote</a></li>
			<li><a href="payment.html">Payments</a></li>
			<li><a href="taxeport.html">Tax Rates</a></li>
		 </ul>
	</li>
	
	
	<li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-shopping-basket"></i><span>Transaction</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="deposit.html">New Deposit</a></li>
			<li><a href="expense.html">New Expense</a></li>
			<li><a href="transfer.html">Transfer</a></li>
			<li><a href="view-tsaction.html">View transaction</a></li>
			<li><a href="balance.html">Balance Sheet</a></li>
			<li><a href="treport.html">Transfer Report</a></li>
		 </ul>
	  </li>
	  
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-book"></i><span>Task</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="rtask.html">Running Task</a></li>
			<li><a href="atask.html">Archive Task</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-shopping-bag"></i><span>Accounting</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="cpayment.html">Client payment</a></li>
			<li><a href="emanage.html">Expense management</a></li>
			<li><a href="ecategory.html">Expense Category</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-file-text"></i><span>Report</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="preport.html">Project Report</a></li>
			<li><a href="creport.html">Client Report</a></li>
			<li><a href="ereport.html">Expense Report</a></li>
			<li><a href="incomexp.html">Income expense comparesion</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-bell"></i><span>Attendance</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="thistory.html">Time History</a></li>
			<li><a href="timechange.html">Time Change Request</a></li>
			<li><a href="atreport.html">Attendance Report</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-edit"></i><span>Recruitment</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="jpost.html">Jobs Posted</a></li>
			<li><a href="japp.html">Jobs Application</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-shopping-basket"></i><span>payroll</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="salary.html">Salary Template</a></li>
			<li><a href="hourly.html">Hourly</a></li>
			<li><a href="managesal.html">Manage salary</a></li>
			<li><a href="empsallist.html">Employee salary list</a></li>
			<li><a href="mpayment.html">Make payment</a></li>
			<li><a href="generatepay.html">Generate payslip</a></li>
			<li><a href="paysum.html">Payroll summary</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-bitbucket-square"></i><span>Stock</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="stockcat.html">Stock category</a></li>
			<li><a href="manstock.html">Manage Stock</a></li>
			<li><a href="astock.html">Assign stock</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-ticket"></i><span>Tickets</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="ticanswer.html">Answered</a></li>
			<li><a href="ticopen.html">Open</a></li>
			<li><a href="iprocess.html">Inprocess</a></li>
			<li><a href="close.html">CLosed</a></li>
			<li><a href="allticket.html">All Tickets</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-list"></i>
		 <span>Utilities</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="ativitylog.html">Activity Log</a></li>
			<li><a href="emailmes.html">Email message log</a></li>
			<li><a href="systemsts.html">System status</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-bar-chart"></i><span>Charts</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li class=""><a href="charts_flot.html">Flot Chart</a></li>
			<li><a href="charts_Js.html">Chart js</a></li>
			<li><a href="charts_morris.html">Morris Charts</a></li>
			<li><a href="charts_sparkline.html">Sparkline Charts</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-briefcase"></i>
		 <span>Icons</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="icons_bootstrap.html">Bootstrap Icons</a></li>
			<li><a href="icons_fontawesome.html">Fontawesome Icon</a></li>
			<li><a href="icons_flag.html">Flag Icons</a></li>
			<li><a href="icons_material.html">Material Icons</a></li>
			<li><a href="icons_weather.html">Weather Icons </a></li>
			<li><a href="icons_line.html">Line Icons</a></li>
			<li><a href="icons_pe.html">Pe Icons</a></li>
			<li><a href="icon_socicon.html">Socicon Icons</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-list"></i> <span>Other page</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="javascript:void(0);">Login</a></li>
			<li><a href="register.html">Register</a></li>
			<li><a href="profile.html">Profile</a></li>
			<li><a href="forget_password.html">Forget password</a></li>
			<li><a href="lockscreen.html">Lockscreen</a></li>
			<li><a href="404.html">404 Error</a></li>
			<li><a href="505.html">505 Error</a></li>
			<li><a href="blank.html">Blank Page</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-bitbucket"></i><span>UI Elements</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="buttons.html">Buttons</a></li>
			<li><a href="tabs.html">Tabs</a></li>
			<li><a href="notification.html">Notification</a></li>
			<li><a href="tree-view.html">Tree View</a></li>
			<li><a href="progressbars.html">Progressber</a></li>
			<li><a href="list.html">List View</a></li>
			<li><a href="typography.html">Typography</a></li>
			<li><a href="panels.html">Panels</a></li>
			<li><a href="modals.html">Modals</a></li>
			<li><a href="icheck_toggle_pagination.html">iCheck, Toggle, Pagination</a></li>
			<li><a href="labels-badges-alerts.html">Labels, Badges, Alerts</a></li>
		 </ul>
	  </li>
	  <li class="treeview">
		 <a href="javascript:void(0);">
		 <i class="fa fa-gear"></i>
		 <span>settings</span>
		 <span class="pull-right-container">
		 <i class="fa fa-angle-left pull-right"></i>
		 </span>
		 </a>
		 <ul class="treeview-menu">
			<li><a href="gsetting.html">Genaral settings</a></li>
			<li><a href="stfsetting.html">Staff settings</a></li>
			<li><a href="emailsetting.html">Email settings</a></li>
			<li><a href="paysetting.html">Payment</a></li>
		 </ul>
	  </li>
	  <li>
		 <a href="company.html">
		 <i class="fa fa-home"></i> <span>Companies</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="holiday.html">
		 <i class="fa fa-stop-circle"></i> <span>Public Holiday</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="user.html">
		 <i class="fa fa-user-circle"></i><span>User</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="items.html">
		 <i class="fa fa-file-o"></i><span>Items</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="{{ route('admin.departments.index') }}">
		 <i class="fa fa-tree"></i><span>Departments</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="document.html">
		 <i class="fa fa-file-text"></i> <span>Documents</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="train.html">
		 <i class="fa fa-clock-o"></i><span>Training</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="calender.html">
		 <i class="fa fa-calendar"></i> <span>Calender</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="notice.html">
		 <i class="fa fa-file-text"></i> <span>Notice Board</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="message.html">
		 <i class="fa fa-envelope-o"></i> <span>Message</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
	  <li>
		 <a href="note.html">
		 <i class="fa fa-comment"></i> <span>Notes</span>
		 <span class="pull-right-container">
		 </span>
		 </a>
	  </li>
   </ul>
</div>
<!-- /.sidebar -->
</aside>




