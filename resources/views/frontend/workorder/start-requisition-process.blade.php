 
<!DOCTYPE html>
<html lang="en">
<head>@include('frontend.common.head', ['pageTitle' => 'Start Requisition | Loomexa'])
</head>
<body class="hold-transition sidebar-mini requisition-page">
<!--preloader-->
<div id="preloader">
  <div id="status"></div>
</div>
<!-- Site wrapper -->
<div class="wrapper"> @include('frontend.common.header')
  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-sm-12">
		
			{!! display_message('message') !!}
			
          <div class="panel panel-bd lobidrag">
            <div class="panel-heading warehouse-page-heading">
              <div>
                <h4><i class="fa fa-list-alt"></i> Start Requisition For Warping Process</h4>
                <span>Allot available yarn stock for this warping work order.</span>
              </div>
            </div>
            <div class="panel-body">
			<form method="post" action="{{ route('add_work_requisition') }}" onSubmit="return handleRequisitionSubmit(this)" class="form-horizontal" autocomplete="off">
			@csrf
              <div class="requisition-summary">
              <table class="table table-bordered">
                <tbody>
                  <tr>
                    <th>Work Order</th>
                    <td>{{ $workOrderNumber }}</td>
                    <th>Item Name</th>
                    <td>{{ $workOrderItemName }}</td>
                  </tr>
                </tbody>
              </table>
              </div>
			  
			   <input type="hidden" id="itemIdReq" name="itemIdReq" value="<?=$itemId;?>">
                <input type="hidden" id="work_order_id_req" name="work_order_id_req" value="<?=$workOrderId;?>">
					
			  <div class="wh-section-title">
				<span class="glyphicon glyphicon-list-alt"></span> Available Yarn Stock List
			  </div>
			  <table class="table table-bordered" id="myTable">
                <tbody>
					<tr>
						<th>ID</th> 
						<th>Item Name</th> 									 
						<th>Invoice</th>
						<th>Available</th> 
						<th>Quantity</th>  
						<th>Select</th>
					</tr>	 
				  @forelse($dataWIS as $result)	 
                  <tr>
                    <td>{{ $result->id }}</td> 
                    <td>{{ $result->Item->item_name ?? '' }}</td> 
					<td>{{ $result->invoice_number }}</td>		
                    <td>{{ $result->insp_bal_quan_size }}</td>
                    <td> 
						<input type="number" step="0.01" min="0.01" max="{{ $result->insp_bal_quan_size }}" name="quantity[]" class="form-control stock-quantity" disabled required> 
					</td>
                   		
					<td>	
						<input type="checkbox" class="stock-checkbox" name="req_item_id[]" value="{{ $result->id }}">  	
                     </td>
                  </tr>
				  @empty
				  <tr>
					<td colspan="6" class="text-center">No available stock found.</td>
				  </tr>
				  @endforelse
				  
                </tbody>
              </table>
				
			  <div class="requisition-actions">
			  	<button type="submit" id="sendRequisitionButton" class="btn btn-success" style="display:none;">Send Requisition </button>
			  	<div class="clearfix"></div>
			  </div>
			</form>
			  
			</div> 
			
          </div>
        </div>
      </div>
    </section>
  </div>
  @include('frontend.common.footer') </div>
@include('frontend.common.footerscript')
<script type="text/javascript" src="{{ asset('js/jquery.validate.js') }}"></script>

<script type="text/javascript">
    function disableSubmitButton(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        submitButton.style.pointerEvents = 'none';
        submitButton.innerHTML = 'Submitting...';
    }

    function handleRequisitionSubmit(form) {
        const submitButton = form.querySelector('button[type="submit"]');

        if (submitButton.disabled) {
            return false;
        }

        const checkedStocks = form.querySelectorAll('.stock-checkbox:checked');

        if (checkedStocks.length === 0) {
            alert('Please select at least one stock item.');
            return false;
        }

        disableSubmitButton(form);
        return true;
    }
</script> 

<script type="text/javascript"> 

    document.addEventListener('DOMContentLoaded', function () {
        const checkboxes = document.querySelectorAll('.stock-checkbox');
        const quantityInputs = document.querySelectorAll('input[name="quantity[]"]');
        const submitButton = document.getElementById('sendRequisitionButton');

        function toggleSubmitButton() {
            const hasCheckedStock = document.querySelectorAll('.stock-checkbox:checked').length > 0;
            submitButton.style.display = hasCheckedStock ? '' : 'none';
        }

        checkboxes.forEach((checkbox, index) => {
            checkbox.addEventListener('change', function () {
                if (this.checked) {
                    quantityInputs[index].disabled = false;
                } else {
                    quantityInputs[index].disabled = true;
                    quantityInputs[index].value = '';
                }

                toggleSubmitButton();
            });
        });

        toggleSubmitButton();
    }); 

</script>
</body>
</html>
