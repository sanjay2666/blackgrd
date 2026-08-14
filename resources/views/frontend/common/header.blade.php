<header class="main-header">
<a href="{{ route('dashboard') }}" class="logo">
   <!-- Logo -->
   <span class="logo-mini">
   <img src="{{ asset('assets/brand/loomexa-mark.svg') }}" alt="">
   </span>
   <span class="logo-lg">
   <img src="{{ asset('assets/brand/loomexa-logo-nav.svg') }}" alt="Loomexa">
   </span>
</a>
<button type="button" class="frontend-mobile-menu-btn" id="frontendMobileMenuBtn">
   <i class="fa fa-bars"></i>
</button>
<!-- Header Navbar -->
<nav class="navbar navbar-static-top">
   <a href="javascript:void(0);" class="sidebar-toggle frontend-sidebar-toggle" data-toggle="offcanvas" role="button">
	  <!-- Sidebar toggle button-->
	  <span class="sr-only">Toggle navigation</span>
	  <span class="pe-7s-angle-left-circle"></span>
   </a>
   <!-- searchbar-->
   <div id="search">
	 <button type="button" class="close">×</button>
	<form>
	   <input type="search" value="" placeholder="Search.." />
	   <button type="submit" class="btn btn-add">Search...</button>
	</form>
 </div>
 <ul class="nav navbar-nav frontend-header-menu">
	  <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}">
		 <a href="{{ route('dashboard') }}">Dashboard</a>
	  </li>
	   
	  <li class="dropdown {{ request()->routeIs('sale-orders.*') || request()->routeIs('show-saleorderitems') || request()->routeIs('show-workorders') || request()->routeIs('show-dyed-workorders') ? 'active' : '' }}">
		 <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
		   Sales <span class="caret"></span>
		 </a>
		 <ul class="dropdown-menu">
            <li><a href="{{ route('sale-orders.index') }}">Sale Order List</a></li>
            <li><a href="{{ route('sale-orders.create') }}">Add Sale Order</a></li>
            <li><a href="{{ route('show-saleorderitems') }}">Create Work Order</a></li>
			<li><a href="{{ route('show-workorders') }}">Work Orders</a></li>
			<li><a href="{{ route('show-dyed-workorders') }}">Dyeing / Coating Work Orders</a></li>
		 </ul>
	  </li>
	  <li class="dropdown {{ request()->routeIs('packaging.*') || request()->routeIs('sales-challans.*') ? 'active' : '' }}">
		 <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">Packaging / Dispatch <span class="caret"></span></a>
		 <ul class="dropdown-menu">
            <li><a href="{{ route('packaging.show-available-orders') }}">Packaging Available</a></li>
            <li><a href="{{ route('packaging.show-packaged-orders') }}">Packaged Orders</a></li>
            <li><a href="{{ route('sales-challans.index') }}">Sales Challans / Dispatch</a></li>
		 </ul>
	  </li>
	  <li class="dropdown {{ request()->routeIs('show-purchaseorders') || request()->routeIs('add-purchaseorder') || request()->routeIs('purchase-orders.*') || request()->routeIs('print-purchaseorder') || request()->routeIs('show-purchases', 'show-purchase') ? 'active' : '' }}">
		 <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
		   Purchase <span class="caret"></span>
		 </a>
		 <ul class="dropdown-menu">
			<li><a href="{{ route('show-purchaseorders') }}">Purchase Order List</a></li>
			<li><a href="{{ route('add-purchaseorder') }}">Add Purchase Order</a></li>
			<li><a href="{{ route('show-purchases') }}">Received Purchases</a></li>
		 </ul>
	  </li>
	  <li class="dropdown {{ request()->routeIs('reports.*', 'show', 'show-stock-details-listing', 'show-saleorder-reports', 'show-warehouse-stock-report', 'show-warehouse-balance-report', 'show-workorder-inspection') ? 'active' : '' }}">
		 <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
		  Report <span class="caret"></span>
		 </a>
		 <ul class="dropdown-menu">
			<li><a href="{{ route('reports.pending-orders') }}">Pending Sale Orders</a></li>
			<li><a href="{{ route('show-saleorder-reports') }}">Sale Order Item Report</a></li>
			<li><a href="{{ route('reports.production-status') }}">Production Status</a></li>
			<li><a href="{{ route('show') }}">Warehouse Items List</a></li>
			<li><a href="{{ route('show-warehouse-stock-report') }}">Warehouse Stock Report</a></li>
			<li><a href="{{ route('show-warehouse-balance-report') }}">Warehouse Balance Report</a></li>
			<li><a href="{{ route('reports.stock-movement') }}">Stock Movement</a></li>
			<li><a href="{{ route('show-workorder-inspection') }}">Inspected Stock Inward</a></li>
			<li><a href="{{ route('reports.inspection-rejection') }}">Inspection / Rejection</a></li>
			<li><a href="{{ route('reports.packaging') }}">Packaging Report</a></li>
			<li><a href="{{ route('reports.customer-dispatch') }}">Customer Dispatch</a></li>
			<li><a href="{{ route('reports.purchase-receiving') }}">Purchase / Receiving</a></li>
			<li><a href="{{ route('reports.job-work') }}">Job Work Status</a></li>
		 </ul>
	  </li>
	  <li class="dropdown {{ request()->routeIs('add-item-in-warehouse', 'add-received-item-in-warehouse', 'show-warehouse-item-stock', 'show-warehouse-item-requirement', 'show-department-return-requests', 'show-balance-table-stock', 'storeStockForMillDispatch', 'show-mill-chalan', 'print-mill-dispatch-chalan', 'print-mill-dispatch-received-chalan', 'mill_dispatch_received_items_in_warehouse', 'store_mill_dispatch_received_item_in_warehouse', 'mill_dispatch_received_weaving_items_in_warehouse', 'store_mill_dispatch_received_weaving_item_in_warehouse', 'warehouse.breakMeter', 'updateVendor', 'update_mtr_received_status') ? 'active' : '' }}">
		 <a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
		  Stock <span class="caret"></span>
		 </a>
		 <ul class="dropdown-menu">
			<li><a href="{{ route('add-item-in-warehouse') }}">Store Warehouse Item</a></li>
			<li><a href="{{ route('add-received-item-in-warehouse') }}">Add Received Item</a></li>
			<li><a href="{{ route('show-warehouse-item-requirement') }}">Warehouse Requirements</a></li>
			<li><a href="{{ route('show-department-return-requests') }}">Department Return Requests</a></li>
			<li><a href="{{ route('show-warehouse-item-stock') }}">Mill Job Dispatch</a></li>
			<li><a href="{{ route('show-mill-chalan') }}">Mill Dispatch Challan</a></li>
			<li><a href="{{ route('show-balance-table-stock') }}">Warehouse Balance</a></li>
		 </ul>
	  </li>
 </ul>
 <div class="navbar-custom-menu">
	  <ul class="nav navbar-nav">
		 <li class="frontend-search-menu">
			<a href="#search"><i class="pe-7s-search"></i></a>
		 </li>
	     <!-- Notifications -->
		 <li class="dropdown notifications-menu">
			<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
			<i class="pe-7s-bell"></i>
			<span class="label label-warning">7</span>
			</a>
			<ul class="dropdown-menu">
			   <li>
				  <ul class="menu">
					 <li>
						<a href="javascript:void(0);" class="border-gray">
						<i class="fa fa-dot-circle-o color-green"></i>Change Your font style</a>
					 </li>
					 <li><a href="javascript:void(0);" class="border-gray">
						<i class="fa fa-dot-circle-o color-red"></i>
						check the system ststus..</a>
					 </li>
					 <li><a href="javascript:void(0);" class="border-gray">
						<i class="fa fa-dot-circle-o color-yellow"></i>
						Add more admin...</a>
					 </li>
					 <li><a href="javascript:void(0);" class="border-gray">
						<i class="fa fa-dot-circle-o color-violet"></i> Add more clients and order</a>
					 </li>
					 <li><a href="javascript:void(0);" class="border-gray">
						<i class="fa fa-dot-circle-o color-yellow"></i>
						Add more admin...</a>
					 </li>
					 <li><a href="javascript:void(0);" class="border-gray">
						<i class="fa fa-dot-circle-o color-violet"></i> Add more clients and order</a>
					 </li>
				  </ul>
			   </li>
			</ul>
		 </li>
		 <!-- user -->
		 <li class="dropdown dropdown-user">
			<a href="javascript:void(0);" class="dropdown-toggle" data-toggle="dropdown">
			<img src="{{ asset('frontend-assets/dist/img/avatar5.png') }}" class="img-circle" width="45" height="45" alt="user"></a>
			<ul class="dropdown-menu" >
			   <li><a href="javascript:void(0);" onclick="event.preventDefault(); document.getElementById('frontendLogoutForm').submit();">
				  <i class="fa fa-sign-out"></i> Signout</a>
			   </li>
			</ul>
		 </li>
	  </ul>
   </div>
</nav>
</header>
<div class="frontend-mobile-menu" id="frontendMobileMenu">
   <div class="frontend-mobile-menu-head">
      <strong>Menu</strong>
      <button type="button" id="frontendMobileMenuClose"><i class="fa fa-times"></i></button>
   </div>
   <ul>
      <li class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"><a href="{{ route('dashboard') }}">Dashboard</a></li>
       
      <li class="{{ request()->routeIs('sale-orders.*') || request()->routeIs('show-saleorderitems') || request()->routeIs('show-workorders') || request()->routeIs('show-dyed-workorders') ? 'active' : '' }}">
         <a href="javascript:void(0);" class="frontend-mobile-submenu-link">Sales <i class="fa fa-angle-down"></i></a>
         <ul>
            <li><a href="{{ route('sale-orders.index') }}">Sale Order List</a></li>
            <li><a href="{{ route('sale-orders.create') }}">Add Sale Order</a></li>
            <li><a href="{{ route('show-saleorderitems') }}">Create Work Order</a></li>
			<li><a href="{{ route('show-workorders') }}">Work Orders</a></li>
			<li><a href="{{ route('show-dyed-workorders') }}">Dyeing / Coating Work Orders</a></li>
         </ul>
      </li>
      <li class="{{ request()->routeIs('packaging.*') || request()->routeIs('sales-challans.*') ? 'active' : '' }}">
         <a href="javascript:void(0);" class="frontend-mobile-submenu-link">Packaging / Dispatch <i class="fa fa-angle-down"></i></a>
         <ul>
            <li><a href="{{ route('packaging.show-available-orders') }}">Packaging Available</a></li>
            <li><a href="{{ route('packaging.show-packaged-orders') }}">Packaged Orders</a></li>
            <li><a href="{{ route('sales-challans.index') }}">Sales Challans / Dispatch</a></li>
         </ul>
      </li>
      <li class="{{ request()->routeIs('show-purchaseorders') || request()->routeIs('add-purchaseorder') || request()->routeIs('purchase-orders.*') || request()->routeIs('print-purchaseorder') || request()->routeIs('show-purchases', 'show-purchase') ? 'active' : '' }}">
         <a href="javascript:void(0);" class="frontend-mobile-submenu-link">Purchase <i class="fa fa-angle-down"></i></a>
         <ul>
            <li><a href="{{ route('show-purchaseorders') }}">Purchase Order List</a></li>
            <li><a href="{{ route('add-purchaseorder') }}">Add Purchase Order</a></li>
            <li><a href="{{ route('show-purchases') }}">Received Purchases</a></li>
         </ul>
      </li>
      <li class="{{ request()->routeIs('reports.*', 'show', 'show-stock-details-listing', 'show-saleorder-reports', 'show-warehouse-stock-report', 'show-warehouse-balance-report', 'show-workorder-inspection') ? 'active' : '' }}">
         <a href="javascript:void(0);" class="frontend-mobile-submenu-link">Report <i class="fa fa-angle-down"></i></a>
         <ul>
            <li><a href="{{ route('reports.pending-orders') }}">Pending Sale Orders</a></li>
            <li><a href="{{ route('show-saleorder-reports') }}">Sale Order Item Report</a></li>
            <li><a href="{{ route('reports.production-status') }}">Production Status</a></li>
            <li><a href="{{ route('show') }}">Warehouse Items List</a></li>
            <li><a href="{{ route('show-warehouse-stock-report') }}">Warehouse Stock Report</a></li>
            <li><a href="{{ route('show-warehouse-balance-report') }}">Warehouse Balance Report</a></li>
            <li><a href="{{ route('reports.stock-movement') }}">Stock Movement</a></li>
            <li><a href="{{ route('show-workorder-inspection') }}">Inspected Stock Inward</a></li>
            <li><a href="{{ route('reports.inspection-rejection') }}">Inspection / Rejection</a></li>
            <li><a href="{{ route('reports.packaging') }}">Packaging Report</a></li>
            <li><a href="{{ route('reports.customer-dispatch') }}">Customer Dispatch</a></li>
            <li><a href="{{ route('reports.purchase-receiving') }}">Purchase / Receiving</a></li>
            <li><a href="{{ route('reports.job-work') }}">Job Work Status</a></li>
         </ul>
      </li>
      <li class="{{ request()->routeIs('add-item-in-warehouse', 'add-received-item-in-warehouse', 'show-warehouse-item-stock', 'show-warehouse-item-requirement', 'show-department-return-requests', 'show-balance-table-stock', 'storeStockForMillDispatch', 'show-mill-chalan', 'print-mill-dispatch-chalan', 'print-mill-dispatch-received-chalan', 'mill_dispatch_received_items_in_warehouse', 'store_mill_dispatch_received_item_in_warehouse', 'mill_dispatch_received_weaving_items_in_warehouse', 'store_mill_dispatch_received_weaving_item_in_warehouse', 'warehouse.breakMeter', 'updateVendor', 'update_mtr_received_status') ? 'active' : '' }}">
         <a href="javascript:void(0);" class="frontend-mobile-submenu-link">Stock <i class="fa fa-angle-down"></i></a>
         <ul>
            <li><a href="{{ route('add-item-in-warehouse') }}">Store Warehouse Item</a></li>
            <li><a href="{{ route('add-received-item-in-warehouse') }}">Add Received Item</a></li>
            <li><a href="{{ route('show-warehouse-item-requirement') }}">Warehouse Requirements</a></li>
            <li><a href="{{ route('show-department-return-requests') }}">Department Return Requests</a></li>
            <li><a href="{{ route('show-warehouse-item-stock') }}">Mill Job Dispatch</a></li>
			<li><a href="{{ route('show-mill-chalan') }}">Mill Dispatch Challan</a></li>
			<li><a href="{{ route('show-balance-table-stock') }}">Warehouse Balance</a></li>
		 </ul>
	  </li>
   </ul>
</div>
<div class="frontend-mobile-menu-bg" id="frontendMobileMenuBg"></div>
<!-- =============================================== -->
<!-- Content Wrapper. Contains page content -->

<form id="frontendLogoutForm" method="POST" action="{{ route('logout') }}" style="display:none;">
@csrf
</form>

