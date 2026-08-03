@php
    use App\Http\Controllers\CommonController;
@endphp

<div class="panel panel-default">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-6">
                <strong>Sale Order No:</strong> {{ $data->sale_order_number }}
            </div>

            <div class="col-sm-6 text-right">
                <strong>Date:</strong>
                {{ !empty($data->sale_order_date) ? date('d-m-Y', strtotime($data->sale_order_date)) : '' }}
            </div>
        </div>
    </div>

    <div class="panel-body">
        <div class="row" style="margin-bottom:15px;">
            <div class="col-sm-6">
                <strong>Customer:</strong>
                {{ isset($data->customer->name) ? $data->customer->name : '' }}
            </div>

            <div class="col-sm-3">
                <strong>Priority:</strong>
                {{ isset($data->order_priority) ? $data->order_priority : '' }}
            </div>

            <div class="col-sm-3">
                <strong>Loomexa Number:</strong>
                {{ isset($data->id) ? $data->id : '' }}
            </div>
        </div>

        <form method="POST" action="{{ route('sale-order.submit-selected-items') }}">
            @csrf

            <input type="hidden" name="sale_order_id" value="{{ enc($data->id) }}">

            <div class="table-responsive">
                <table class="table table-bordered table-striped table-hover small">
                    <thead>
                        <tr class="info">
                            <th style="width:12%;">Item</th>
                            <th style="width:9%;">Priority</th>
                            <th style="width:9%;">Dyeing</th>
                            <th style="width:9%;">Coating</th>
                            <th style="width:9%;">P.Extra</th>
                            <th style="width:9%;">Price</th>
                            <th style="width:8%;">Meter</th>
                            <th style="width:8%;">Dlvrd</th>
                            <th style="width:8%;">Pending</th>
                            <th style="width:15%;">Select</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if(!empty($data->saleOrderItems))
                            @foreach($data->saleOrderItems as $row)
                                 
                                 <?php 
								  //  echo "<pre>"; print_r($row); exit;
                                    $saleOrdItemId = $row->id;
                                    $woId = '00'; // CommonController::getWorkOrderIdForSaleOrder($saleOrdItemId);
                                    $internalName = CommonController::getItemInternalName($row->item_id);
                                    $pendingMeter = (float) $row->meter - (float) $row->delivered_item_mtr;
								 
								 ?>    
                                 

                                <tr>
                                    <td>
                                        <strong class="text-primary">{{ $row->item_name ?? '-' }}</strong>

                                        @if(!empty($internalName))
                                            <div class="text-muted">
                                                <small>({{ $internalName }})</small>
                                            </div>
                                        @endif
                                    </td>

                                    <td>{{ $row->order_item_priority }}</td>
                                    <td>{{ $row->dyeing_color }}</td>
                                    <td>{{ $row->coating_type }}</td>

                                    <td>
                                        <p style="margin-bottom:3px;">{{ $row->print_job }}</p>
                                        <p style="margin-bottom:0;">{{ $row->extra_job }}</p>
                                    </td>

                                    <td>{{ number_format($row->rate, 2) }}</td>
                                    <td>{{ number_format($row->meter, 2) }}</td>
                                    <td>{{ number_format($row->delivered_item_mtr, 2) }}</td>
                                    <td>{{ number_format($pendingMeter, 2) }}</td>

                                    <td style="width:180px; vertical-align:top;">
                                        <label style="display:flex; align-items:center; gap:6px; margin-bottom:8px; font-weight:normal; cursor:pointer;">
                                            <input type="checkbox" class="js-item-check" data-target="#meterWrap{{ $saleOrdItemId }}">
                                            <span>Select</span>
                                        </label>

                                        <div id="meterWrap{{ $saleOrdItemId }}" style="display:none;">
                                            <input type="hidden" name="items[{{ enc($saleOrdItemId) }}][sale_order_item_id]" value="{{ enc($saleOrdItemId) }}">
                                            <input type="hidden" name="items[{{ enc($saleOrdItemId) }}][selected]" class="js-selected-flag" value="0">
                                            <input type="number" step="0.01" min="0" class="form-control input-sm js-meter-input" name="items[{{ enc($saleOrdItemId) }}][meter]" placeholder="Meter" disabled>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="col-md-12 form-group">
                <label class="control-label">Your insightful comment completed this item with clarity and purpose.</label>
                <input type="text" placeholder="Comment" required class="form-control" name="dlvr_cleared_reason" id="dlvr_cleared_reason">
            </div>

            <div class="text-right">
                <button type="submit" class="btn btn-primary">
                    Submit Selected Items
                </button>
            </div>
        </form>
    </div>
</div>

<script type="text/javascript">
$(document).on('change', '.js-item-check', function () {
    var checkbox = $(this);
    var target = $(checkbox.data('target'));

    var meterInput = target.find('.js-meter-input');
    var selectedFlag = target.find('.js-selected-flag');

    if (checkbox.is(':checked')) {
        target.show();

        meterInput.prop('disabled', false);

        selectedFlag.val(1); // IMPORTANT
    } else {
        target.hide();

        meterInput.val('').prop('disabled', true);

        selectedFlag.val(0); // IMPORTANT
    }
});
</script>
