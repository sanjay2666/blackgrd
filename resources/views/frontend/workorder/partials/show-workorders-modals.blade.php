  <!-------------------Modal Start-------------------->

  <!-- Reason Modal -->
  <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog" aria-labelledby="reasonModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <form method="POST" action="{{ route('SetReasonForWorkOrderItem') }}">
          @csrf
          <input type="hidden" name="FId" id="modalFId">
          <div class="modal-header">
            <h3 class="modal-title">Are you ready to provide the reason for not creating the work order yet?</h3>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
          </div>
          <div class="modal-body">
            <h5 align="center">You will not be able to undo this action, and a detailed report will be sent to the <strong>director</strong> for review.</h5>
            <div class="panel panel-primary wo-reason-panel">
              <div class="panel-heading wo-reason-heading"> Reason History </div>
              <div class="panel-body wo-reason-body">
                <div class="table-responsive">
                  <table class="table table-bordered table-hover wo-reason-table" id="reasonTable">
                    <thead class="wo-reason-thead">
                      <tr class="wo-reason-header-row">
                        <th class="wo-w-60px">SrNo.</th>
                        <th>Reason</th>
                        <th class="wo-w-180px">Date</th>
                      </tr>
                    </thead>
                    <tbody class="wo-font-14">
                      <!-- JavaScript will fill this -->
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
            <div class="form-group ">
              <label>Comment</label>
              <input type="text" class="form-control" name="pending_reason" required placeholder="Enter comment">
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-danger" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Save</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!------------------------------------------------------------->

  <div class="modal fade" id="CoatingInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="{{ route('update_coating_inspec_process')}}" class="form-horizontal" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h3 class="modal-title"> <i class="fa fa-plus m-r-5"></i> Coating Inspection Process </h3>
          </div>
          <input type="hidden" name="page" value="<?=htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered table-striped table-hover table-condensed">
                  <tr class="warning">
                    <th>Item Name</th>
                    <td><span id="coating_ItemName"></span></td>
                  </tr>
                </table>
                <table class="table table-bordered">
                  <tr> <span id="coating_workRequirement1"></span> </tr>
                </table>
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr class="info">
                      <th colspan="6" class="text-center"><strong>Lot Info & Destination</strong></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="active">
                      <!-- Lot Number -->
                      <th class="wo-col-15">Lot Number <span class="text-danger">*</span></th>
                      <td class="wo-col-18"><input type="number" id="coating_req_lot_no" name="req_lot_no"
								   oninput="fetchWarehouseItemStockCoating(this.value, document.getElementById('coating_ins_work_order_id').value, 'myTableCoated')" class="form-control">
                      </td>
                      <!-- Width -->
                      <th class="wo-col-15">Width <span class="text-danger">*</span></th>
                      <td class="wo-col-18"><input type="text" class="form-control" id="coating_insp_width" value="0" name="insp_width">
                      </td>
                      <!-- GSM -->
                      <th class="wo-col-15">GSM <span class="text-danger">*</span></th>
                      <td class="wo-col-18"><input type="text" class="form-control" id="coating_insp_gsm" value="0" name="insp_gsm">
                      </td>
                    </tr>
					 <tr>
                      <td colspan="10"><span id="coating_workRequirement"></span> </td>
                    </tr>
                  </tbody>
                </table>
                <table class="table table-bordered" id="myTableCoated">
                  <input type="hidden" id="coating_ins_item_id" name="ins_item_id">
                  <input type="hidden" id="coating_ins_work_order_id" name="ins_work_order_id">
				  <input type="hidden" id="machineIdC" name="insp_work_machine_id">
				  <input type="hidden" id="reqProIdsC" name="insp_work_process_req_id">
                  <thead>
                    <tr class="success">
                      <th>Sr.No.</th>
                      <th>G.T.Number</th>
                      <th>Greige Meter</th>
                      <th>Break Meter</th>
                      <th>Output</th>
                      <th>Shrinkage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-row"> </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>T.Input:</strong></td>
                      <td id="toGreigeItemQty">0</td>
                      <td colspan="1"><strong>T.Output:</strong></td>
                      <td id="totalOutput">0</td>
                    </tr>
                  </tfoot>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><strong>Comment</strong>
                      <p>Machine 	 : <span id="MachineNameC"></span> </p>
                      <p>Taka Number : <span id="inspTakaNumberC"></span> </p></td>
                    <td><textarea class="form-control" id="coating_inspec_comment" required name="inspec_comment"></textarea></td>
                  </tr>
                  <tr>
                    <td><strong> <span id="coating_processtext"> </span></strong> </td>
                    <td><select name="insp_work_status_process" required id="coating_insp_work_status_process" class="form-control">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_1" onChange='selectWorkStatus(this)' class="form-control">
                        <option value=""> Select Work Status</option>
                        <option value="Completed"> Completed</option>
                        <option value="Defective"> Defective</option>
                      </select>
                    </td>
                  </tr>
                  <tr class="js-work-status-reason wo-hidden">
                    <td><strong>Defect Type Reason</strong></td>
                    <td><select name="fabric_fault_id" id="coating_fabric_fault_id" class="form-control">
                        <option value=""> Select Reason</option>
                        <?php foreach ($dataF->where('process_id', 4) as $rowF) { ?>
                        <option value="<?= $rowF->id; ?>">
                        <?= $rowF->reason; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Warehouse</strong></td>
                    <td><select name="insp_work_warehouseId" id="coating_insp_work_warehouseId" required class="form-control">
                        <option> Select Warehouse</option>
                        <?php foreach ($dataW as $row) { ?>
                        <option value="<?= $row->id; ?>">
                        <?= $row->warehouse_name; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update Inspection Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>
  <!-- ===== Modal 1 (Print) - IDs suffixed with _print ===== -->
  <div class="modal fade" id="CoatingPrintInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" action="{{ route('update_coating_print_inspec_process')}}" class="form-horizontal" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i>Inspection Process</h3>
          </div>
          <input type="hidden" name="page" value="<?=htmlspecialchars($current_page); ?>">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="coating_ItemName_print"></span></td>
                  </tr>
                </table>
                <span id="coating_workRequirement_print1"></span>
                <table class="table table-bordered" id="myTableCoatedPrint">
                  <input type="hidden" id="coating_ins_item_id_print" name="ins_item_id">
                  <input type="hidden" id="coating_ins_work_order_id_print" name="ins_work_order_id">
				  <input type="hidden"   id="machineIdC_print" name="insp_work_machine_id">

                  <thead>
                    <tr>
                      <p><strong>Lot Number : </strong>
                        <input type="number" id="req_lot_no_print" name="req_lot_no"
                          oninput="fetchWarehouseItemStockCoatingPrint(this.value, document.getElementById('coating_ins_work_order_id_print').value, 'myTableCoatedPrint')">
                      </p>
                    </tr>
					 <tr>
                      <td colspan="10"><span id="coating_workRequirement_print"></span> </td>
                    </tr>
                    <tr>
                      <th>Sr.No.</th>
                      <th>G.T.Number</th>
                      <th>Greige Meter</th>
                      <th>Break Meter</th>
                      <th>Output</th>
                      <th>Shrinkage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-row2"> </tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>T.Input:</strong></td>
                      <td id="toGreigeItemQty_print">0</td>
                      <td colspan="1"><strong>T.Output:</strong></td>
                      <td id="totalOutput_print">0</td>
                    </tr>
                  </tfoot>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><strong>Comment</strong>
                      <p>Machine : <span id="MachineNameC_print"></span> </p>
                      <p>Taka Number : <span id="inspTakaNumberC_print"></span> </p></td>
                    <td><textarea class="form-control" id="inspec_comment_print" required name="inspec_comment"></textarea></td>
                  </tr>
                  <tr>
                    <td><strong> <span id="coating_processtext_print"> </span></strong> </td>
                    <td><select name="insp_work_status_process" required id="coating_print_insp_work_status_process" class="form-control">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_2" onChange='selectWorkStatus(this)' class="form-control">
                        <option value=""> Select Work Status</option>
                        <option value="Completed"> Completed</option>
                        <option value="Defective"> Defective</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Warehouse</strong></td>
                    <td><select name="insp_work_warehouseId" id="coating_insp_work_warehouseId_print" required class="form-control">
                        <option> Select Warehouse</option>
                        <?php foreach ($dataW as $row) { ?>
                        <option value="<?= $row->id; ?>"><?=$row->warehouse_name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update Inspection Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="DyeingInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg wo-modal-wide">
      <div class="modal-content">
        <form method="post" id="dyeingInspectionForm" action="{{ route('update_dyeing_inspec_process') }}" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <input type="hidden" name="submission_token" value="{{ Str::uuid() }}">
          <div class="modal-header modal-header-primary">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h3><i class="fa fa-plus m-r-5"></i>Inspection Process</h3>
          </div>
          <input type="hidden" name="page" value="{{ htmlspecialchars($current_page) }}">
          <div class="modal-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <table class="table table-bordered table-striped table-hover table-condensed">
                  <tr class="warning">
                    <th>Item Name</th>
                    <td><span id="dyeing_ItemName"></span></td>
                  </tr>
                </table>
                <table class="table table-bordered">
                  <tr>
                    <td><span id="dyeing_workRequirement1"></span> </td>
                  </tr>
                </table>
                <table class="table table-bordered table-striped">
                  <thead>
                    <tr class="info">
                      <th colspan="6" class="text-center"><strong>Lot Info & Destination</strong></th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td colspan="10"> Destination:
                        <label class="wo-ml-8">
                        <input type="radio" id="to_warehouse" name="destination" value="Warehouse" checked>
                        <span>Warehouse</span> </label>
                        <label class="wo-ml-12">
                        <input type="radio" id="to_coating" name="destination" value="Department">
                        <span>Department</span> </label>
                      </td>
                    </tr>
                    <!-- Lot Number / Width / GSM row -->
                    <tr class="active">
                      <th class="wo-col-15">Lot Number</th>
                      <td class="wo-col-18"><input type="number" class="form-control" id="dyeing_req_lot_no" name="req_lot_no" required
                               oninput="fetchWarehouseItemStock(this.value, document.getElementById('dyeing_ins_work_order_id').value, 'myTableDyed')">
                      </td>
                      <th class="wo-col-15">Width <span class="text-danger">*</span></th>
                      <td class="wo-col-18"><input type="text" class="form-control" id="dyeing_insp_width" name="insp_width" value="0">
                      </td>
                      <th class="wo-col-15">GSM <span class="text-danger">*</span></th>
                      <td class="wo-col-18"><input type="text" class="form-control" id="dyeing_insp_gsm" name="insp_gsm" value="0">
                      </td>
                    </tr>
                    <tr>
                      <td colspan="10"><span id="dyeing_workRequirement"></span> </td>
                    </tr>
                  </tbody>
                </table>
                <table class="table table-bordered" id="myTableDyed">
                  <!-- hidden ids used by your JS / form -->
                  <input type="hidden" id="dyeing_ins_item_id" name="ins_item_id">
                  <input type="hidden" id="dyeing_ins_work_order_id" name="ins_work_order_id">
                  <input type="hidden" id="machineIdD" name="insp_work_machine_id">
				  <input type="hidden" id="reqProIdsDieing" name="insp_work_process_req_id">
                  <thead>
                    <tr class="warning">
                      <th>Sr.No.</th>
                      <th>G.T.Number</th>
                      <th>G.Meter</th>
                      <th>BRK Meter</th>
                      <th>Output</th>
                      <th>Rej.Mtr</th>
                      <th>Rej.Reason</th>
                      <th>Shrinkage</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr class="table-row"></tr>
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="2"><strong>T.Input:</strong></td>
                      <td id="toGreigeItemQtyy">0</td>
                      <td colspan="1"><strong>T.Output:</strong></td>
                      <td id="totalOutputt">0</td>
					  <td id="totalRejectOutputt">0</td>
                    </tr>
                  </tfoot>
                </table>
                <table class="table table-bordered wo-bordered-info-table">
                  <tr>
                    <td class="warning wo-w-30 wo-v-middle"><strong>Comment</strong>
                      <p class="wo-hidden wo-mt-5 wo-text-muted"> Taka Number : <span id="inspTakaNumberD"></span> </p></td>
                    <td><textarea class="form-control" id="dyeing_inspec_comment" required name="inspec_comment" rows="3" placeholder="Enter inspection comment"></textarea> </td>
                  </tr>
                  <tr>
                    <td class="warning wo-v-middle"><strong><span id="dyeing_processtext"></span></strong> </td>
                    <td><select name="insp_work_status_process" required id="dyeing_insp_work_status_process"  class="form-control" onChange="updateCoatingProcess()">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td class="warning wo-v-middle"><strong>Proceed with coating process?</strong> </td>
                    <td><select name="insp_coating_process" id="dyeing_insp_coating_process" required class="form-control">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td class="warning wo-v-middle"><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_3"
                              onChange="selectWorkStatus(this)"
                              class="form-control">
                        <option value=""> Select Work Status</option>
                        <option value="Completed">Ok</option>
                        <option value="Defective">Defective</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td class="warning wo-v-middle"><strong>Machine</strong> </td>
                    <td><span id="MachineNameD" class="wo-font-bold"></span> </td>
                  </tr>
                  <tr>
                    <td class="warning wo-v-middle"><strong>Warehouse</strong> </td>
                    <td ><select name="insp_work_warehouseId" id="dyeing_insp_work_warehouseId" required class="form-control">
                        <option> Select Warehouse</option>
                        <?php foreach ($dataW as $row) { ?>
                        <option value="<?= $row->id; ?>">
                        <?= $row->warehouse_name; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                <!-- end comment/controls table -->
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer">
            <button type="submit" class="btn btn-success pull-left">Update Inspection Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="WeavingInspProcessPop" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <form method="post" id="weavingInspectionForm" action="{{ url('/update_weaving_inspec_process') }}" onSubmit="disableSubmitButton(this)">
          @csrf
          <!-- Header -->
          <div class="modal-header panel-heading">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"> <i class="fa fa-check-circle"></i> Inspection Process </h4>
          </div>
          <input type="hidden" name="page" value="<?=htmlspecialchars($current_page); ?>">
          <!-- Body -->
          <div class="modal-body">
            <fieldset>
            <!-- Work Requirement -->
            <div id="weav_workRequirement" class="mb-3"></div>
            <!-- Main Table -->
            <table class="table table-bordered table-striped text-center" id="myTable">
              <input type="hidden" id="weav_ins_item_id" name="ins_item_id">
              <input type="hidden" id="weav_ins_work_order_id" name="ins_work_order_id">
              <input type="hidden" id="weav_machineId" name="insp_work_machine_id">
              <thead class="bg-success">
                <tr>
                  <th class="col-xs-2">Sr.</th>
                  <th class="col-xs-3">Item Name</th>
                  <th class="col-xs-3">Taka Number</th>
                  <th class="col-xs-2">Output</th>
                </tr>
                <tr>
                  <td>1</td>
                  <td><span id="weav_ItemName" class="text-primary font-bold"></span></td>
                  <td><input type="text" min="1" id="weaving_insp_taka_number" name="insp_taka_number" required class="form-control">
                  </td>
                  <td><input type="number" min="1" step="any" id="weaving_output_quan_size" name="output_quan_size[]" required placeholder="Output Size (Meter)" class="form-control">
                  </td>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th>EPI</th>
                  <th>PPI</th>
                  <th>Width</th>
                  <th>GSM</th>
                </tr>
                <tr>
                  <td><input type="number" min="1" step="any" id="insp_epi" name="insp_epi[]" required class="form-control"></td>
                  <td><input type="number" min="1" step="any" id="insp_ppi" name="insp_ppi[]" required class="form-control"></td>
                  <td><input type="number" min="1" step="any" id="insp_width_weav" name="insp_width[]" required class="form-control">
                  </td>
                  <td><input type="number" min="1" step="any" id="insp_gsm_weav" name="insp_gsm[]" required class="form-control"></td>
                </tr>
              </tbody>
            </table>
            <!-- Extra Details -->
            <table class="table table-bordered table-striped">
              <tr>
                <td class="col-xs-4"><strong>Comment</strong>
                  <p>Machine: <span id="MachineName" class="font-bold"></span></p>
                  <p>Master: <span id="MasterName" class="font-bold"></span></p>
                  <p>Beam Number: <span id="inspTakaNumber" class="font-bold"></span></p>
				</td>
                <td><textarea class="form-control" id="weaving_inspec_comment" required name="inspec_comment"></textarea></td>
              </tr>
              <tr>
                <td><strong><span id="weav_processtext"></span></strong></td>
                <td><select name="insp_work_status_process" required id="weaving_insp_work_status_process" class="form-control" onChange="updateDyeingProcess()">
                    <option value="">Select Inspection Process Status</option>
                    <option value="No">Not Complete</option>
                    <option value="Yes">Completed</option>
                  </select>
                </td>
              </tr>
              <tr>
                <td><strong>Do you want to start the dyeing process?</strong></td>
                <td><select name="insp_dyeing_process" id="weaving_insp_dyeing_process" required class="form-control">
                    <option value="No">No</option>
                    <option value="Yes">Yes</option>
                  </select>
                </td>
              </tr>
              <tr>
                <td><strong>Work Status</strong></td>
                <td><select name="work_status" required id="work_status_4" onChange="selectWorkStatus(this)" class="form-control">
                    <option value="">Select Work Status</option>
                    <option value="Completed">Ok</option>
                    <option value="Defective">Defective</option>
                  </select>
                </td>
              </tr>
              <tr class="js-work-status-reason wo-hidden">
                <td><strong>Defect Type Reason</strong></td>
                <td><select name="fabric_fault_id" id="weaving_fabric_fault_id" class="form-control">
                    <option value="">Select Reason</option>
                    <?php foreach ($dataF->where('process_id', 2) as $rowF) { ?>
                    <option value="<?= $rowF->id; ?>"> <?= $rowF->reason; ?> </option>
                    <?php } ?>
                  </select>
                </td>
              </tr>
              <tr>
                <td><strong>Warehouse</strong></td>
                <td><select name="insp_work_warehouseId" id="insp_work_warehouseId" required class="form-control">
                    <option>Select Warehouse</option>
                    <?php foreach ($dataW as $row) { ?>
                    <option value="<?= $row->id; ?>"> <?= $row->warehouse_name; ?> </option>
                    <?php } ?>
                  </select>
                </td>
              </tr>
            </table>
            </fieldset>
          </div>
          <!-- Footer -->
          <div class="modal-footer bg-light">
            <button type="submit" class="btn btn-success"> <i class="fa fa-save"></i> Update Inspection Process </button>
            <button type="button" class="btn btn-default" data-dismiss="modal"> <i class="fa fa-times"></i> Close </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade warping-inspection-modal" id="InspectionProcessPop" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg warping-inspection-dialog">
      <div class="modal-content warping-inspection-content">
        <form method="post" action="{{ route('update_inspec_process')}}" class="form-horizontal warping-inspection-form" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary warping-inspection-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
            <h4 class="modal-title"><i class="fa fa-check-circle m-r-5"></i> Warping Inspection Process</h4>
          </div>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($current_page); ?>">
          <div class="modal-body warping-inspection-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <div class="warping-inspection-summary">
                  <div class="warping-inspection-summary-item warping-inspection-summary-item-wide">
                    <span class="warping-inspection-summary-icon"><i class="fa fa-cube"></i></span>
                    <span class="warping-inspection-summary-text">
                      <span class="warping-inspection-summary-label">Item Name</span>
                      <strong id="ItemName" class="warping-inspection-item">Loading...</strong>
                    </span>
                  </div>
                  <div class="warping-inspection-summary-item">
                    <span class="warping-inspection-summary-icon"><i class="fa fa-cogs"></i></span>
                    <span class="warping-inspection-summary-text">
                      <span class="warping-inspection-summary-label">Machine</span>
                      <strong id="InspectionMachineName" class="warping-inspection-item">Loading...</strong>
                    </span>
                  </div>
                  <div class="warping-inspection-summary-item">
                    <span class="warping-inspection-summary-icon"><i class="fa fa-user"></i></span>
                    <span class="warping-inspection-summary-text">
                      <span class="warping-inspection-summary-label">Master</span>
                      <strong id="InspectionMasterName" class="warping-inspection-item">Loading...</strong>
                    </span>
                  </div>
                </div>
                <div id="workRequirement" class="warping-inspection-requirement"></div>
                <table class="table table-bordered table-striped warping-inspection-table" id="myTableInsp">
                  <thead>
                    <tr>
                      <input type="hidden" id="ins_item_id" name="ins_item_id">
                      <input type="hidden" id="ins_work_order_id" name="ins_work_order_id">
                      <th> <span id="InsoutputNext"></span>  </th>
                      <th>Output <span id="outputNext"></span> Size (<span id="outputUnitType"></span>)</th>
                      <th>Beam Number</th>
                      <th>Weaving Work Meter</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>1</td>
                      <td><input type="number" min="1" id="inspection_output_quan_size" class="form-control" name="output_quan_size[]" required placeholder="Output size">
                      </td>
                      <td><input type="text" id="inspection_insp_taka_number" class="form-control" name="insp_taka_number" required placeholder="Beam number">
                      </td>
                      <td><input type="text" id="weaving_mtr" class="form-control" name="weaving_mtr" required placeholder="Weaving meter">
                      </td>
                    </tr>
                  </tbody>
                </table>
                <table class="table table-bordered warping-inspection-control-table">
                  <tr>
                    <td><strong>Comment</strong> </td>
                    <td><textarea class="form-control" id="inspection_inspec_comment" required name="inspec_comment" rows="3" placeholder="Enter inspection comment"></textarea></td>
                  </tr>
                  <tr>
                    <td><strong> <span id="processtext"> </span></strong> </td>
                    <td><select name="insp_work_status_process" required id="inspection_insp_work_status_process" class="form-control">
                        <option value=""> Select Inspection Process Status</option>
                        <option value="No">Not Complete</option>
                        <option value="Yes">Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Do you want to start the Weaving process ?</strong></td>
                    <td><select name="insp_weaving_process" id="insp_weaving_process" required class="form-control">
                        <option value="No">No</option>
                        <option value="Yes">Yes</option>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Work Status</strong> </td>
                    <td><select name="work_status" required id="work_status_5" class="form-control">
                        <option value="Completed"> Completed</option>
                      </select>
                    </td>
                  </tr>
                  <tr class="js-work-status-process wo-hidden">
                    <td><strong>Process</strong></td>
                    <td><div class="i-check">
                        <input tabindex="7" type="radio" id="minimal-radio-1" value="reprocess" onClick="gatePass(this.value)" name="work_status_process">
                        <label for="minimal-radio-1">Re-Processing</label>
                      </div>
                      <div class="i-check">
                        <input tabindex="8" type="radio" id="minimal-radio-2" value="stock" onClick="gatePass(this.value)" name="work_status_process">
                        <label for="minimal-radio-2">Send To Warehouse</label>
                      </div></td>
                  </tr>
                  <tr class="js-work-status-reason wo-hidden">
                    <td><strong>Defect Type Reason</strong></td>
                    <td><select name="fabric_fault_id" id="inspection_fabric_fault_id" class="form-control">
                        <option value=""> Select Reason</option>
                        <?php foreach ($dataF->where('process_id', 1) as $rowF) { ?>
                        <option value="<?= $rowF->id; ?>">
                        <?= $rowF->reason; ?>
                        </option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                  <tr>
                    <td><strong>Warehouse</strong></td>
                    <td><select name="insp_work_warehouse_id" id="insp_work_warehouse_id" required class="form-control">
                      </select>
                    </td>
                  </tr>
                </table>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer warping-inspection-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Update Inspection</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="StartProcessPop" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog start-process-dialog">
      <div class="modal-content start-process-content">
        <form method="post" action="{{ route('update_startprocess')}}" class="form-horizontal start-process-form" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary start-process-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><i class="fa fa-play-circle m-r-5"></i> Start <span id="processNameId"></span> Process</h4>
          </div>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($current_page); ?>">
          <div class="modal-body start-process-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <span id="RequestedItems" class="start-process-requested"></span>
                <table class="table table-bordered start-process-table">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="ItemNameS" class="start-process-item-name">Loading...</span> </td>
                  </tr>
                  <tr>
                    <input type="hidden" id="itemId" name="itemId">
                    <input type="hidden" id="work_order_id" name="work_order_id">
                  </tr>
                  <tr>
                    <td><strong>Master</strong> </td>
                    <td><select id="masterId" class="form-control" name="masterId">
                        <?php foreach ($dataMas as $row) { ?>
							<option value="<?=$row->id;?>"><?=$row->name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>

                  <tr>
                    <td><strong>Machine</strong></td>
                    <td><select id="machineId" class="form-control" name="machineId">
                        <option value="">Select Machine</option>
                        <?php foreach ($machine as $row) { ?>
                          <option value="<?= $row->id; ?>"><?= e($row->name); ?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>

                </table>
                <tr>
                  <td><label>Process Remarks <span class="required">*</span></label>
                    <input type="text" name="process_started_remarks" id="process_started_remarks" required class="form-control">
                  </td>
                </tr>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer start-process-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Start Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div class="modal fade" id="StartProcessPopWev" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog start-process-dialog">
      <div class="modal-content start-process-content">
        <form method="post" action="{{ route('update_startprocess')}}" class="form-horizontal start-process-form" autocomplete="off" onSubmit="disableSubmitButton(this)">
          @csrf
          <div class="modal-header modal-header-primary start-process-header">
            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            <h4 class="modal-title"><i class="fa fa-play-circle m-r-5"></i> Start <span id="processNameIdWev"></span> Process</h4>
          </div>
          <input type="hidden" name="page" value="<?php echo htmlspecialchars($current_page); ?>">
          <div class="modal-body start-process-body">
            <div class="row">
              <div class="col-md-12">
                <fieldset>
                <span id="RequestedItemsWev" class="start-process-requested"></span>
                <table class="table table-bordered start-process-table">
                  <tr>
                    <th>Item Name</th>
                    <td><span id="ItemNameWev" class="start-process-item-name">Loading...</span> </td>
                  </tr>
                  <tr>
                    <input type="hidden" id="itemIdWev" name="itemId">
                    <input type="hidden" id="work_order_idWev" name="work_order_id">
                  </tr>
                  <tr>
                    <td><strong>Master</strong> </td>
                    <td><select id="masterIdWev" class="form-control" name="masterId">
                        <?php foreach ($dataMas as $row) { ?>
							<option value="<?=$row->id;?>"><?=$row->name;?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>

                  <tr>
                    <td><strong>Machine </strong></td>
                    <td><select id="machineIdWev" class="form-control" name="machineId">
                        <option value="">Select Machine</option>
                        <?php foreach ($machine as $row) { ?>
                          <option value="<?= $row->id; ?>"><?= e($row->name); ?></option>
                        <?php } ?>
                      </select>
                    </td>
                  </tr>
                </table>
                <tr>
                  <td><label>Process Remarks <span class="required">*</span></label>
                    <input type="text" name="process_started_remarks" id="process_started_remarksWev" required class="form-control">
                  </td>
                </tr>
                </fieldset>
              </div>
            </div>
          </div>
          <div class="modal-footer start-process-footer">
            <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            <button type="submit" class="btn btn-success">Start Process</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <!-- Modal HTML -->
  <div id="activateModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="activateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="activateModalLabel">Confirm Activation</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body"> You can activate this work order only once. A detailed report of this change will be sent to the director for review. After this modification, the button will be disabled. </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmActivateBtn">OK</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal HTML -->
  <div id="receiveStockModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="receiveStockModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="receiveStockModalLabel">Receive Stock</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body">
          <!-- Modal content will be loaded here -->
        </div>
      </div>
    </div>
  </div>
  <!-- Modal for Updating Lot Number -->
  <div class="modal fade" id="updateLotModal" tabindex="-1" role="dialog" aria-labelledby="updateLotModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="updateLotModalLabel">Update Lot Number</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="workOrderId" class="form-control" placeholder="Enter new lot number">
          <input type="hidden" id="workProId" class="form-control" placeholder="Enter new lot number">
          <div class="form-group">
            <label class="control-label" for="newLotNo"> Current Lot Number: <span id="currentLotNo"></span><br>
            Please enter a new lot number below to update: </label>
            <input type="text" id="newLotNo" class="form-control" placeholder="Enter new lot number">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
          <button type="button" class="btn btn-success" id="saveLotBtn">Save</button>
        </div>
      </div>
    </div>
  </div>
  <div id="shiftWoModal" class="modal fade loomexa-modal workorder-shift-modal" tabindex="-1" role="dialog" aria-labelledby="shiftModalLabel" aria-hidden="true">
    <div class="modal-dialog workorder-shift-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header workorder-shift-header">
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
          <div class="workorder-shift-title-row">
            <span class="workorder-shift-icon"><i class="fa fa-random"></i></span>
            <div>
              <h4 class="modal-title" id="shiftModalLabel">Shift Work Order to Warping</h4>
              <p class="workorder-shift-subtitle">Confirm this department change before continuing.</p>
            </div>
          </div>
        </div>
        <div class="modal-body workorder-shift-body">
          <div class="workorder-shift-alert">
            <i class="fa fa-exclamation-triangle"></i>
            <div>
              <strong>This action can be done only once.</strong>
              <span>A detailed report of this shift will be sent to the director for review. After confirmation, this work order row will be hidden and the shift button should not be used again.</span>
            </div>
          </div>
        </div>
        <div class="modal-footer workorder-shift-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmShiftBtn"><i class="fa fa-check"></i> Confirm Shift</button>
        </div>
      </div>
    </div>
  </div>

<div class="modal fade" id="returnModal" tabindex="-1" role="dialog" aria-labelledby="returnModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <form method="post" action="{{ route('sendItemReturnRequest') }}" class="form-horizontal" autocomplete="off" onsubmit="return validateReturnForm(this)">
                @csrf
				<div class="modal-header text-center">
					<button type="button" class="close" data-dismiss="modal" aria-label="Close">
						<span aria-hidden="true">&times;</span>
					</button>

					<h4 class="modal-title" id="returnModalLabel"> <span class="glyphicon glyphicon-retweet text-primary"></span> &nbsp; LOT ITEM RETURN </h4>
					<hr>
					<p class="text-muted">
						<span class="label label-info">Lot Number: <span id="modalLotNumber"></span></span>
						<span class="label label-danger">Return Process</span>
					</p>
				</div>

                <div class="modal-body">
                    <input type="hidden" id="ReqLotNumber" name="ReqLotNumber" value="">
                    <input type="hidden" id="wprId" name="wprId" value="">
                    <input type="hidden" id="chkworkOrderId" name="workOrderId" value="">

                    <div class="table-responsive">
                        <table class="table table-striped table-bordered table-hover table-condensed" id="returnItemsTable">
                           <thead>
								<tr class="info">
									<th># StockId</th>
									<th>Taka Number</th>
									<th>Lot Number</th>
									<th>Dyeing Sr.</th>
									<th>Meter</th>
									<th>All <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)"> </th>
								</tr>
							</thead>
                            <tbody>
                                <!-- Dynamic rows will be appended here -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        Close
                    </button>
                    <button type="submit" class="btn btn-primary">
                        Return
                    </button>
                </div>
            </form>

        </div>
    </div>
</div>

  <!-- Modal HTML -->
  <div id="deleteModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header bg-danger">
          <h5 class="modal-title" id="receiveStockModalLabel2">⚠️ Confirm Deletion</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <!-- Modal Body -->
        <div class="modal-body text-center">
          <p class=" text-dark"> Are you sure you want to <strong>delete</strong> this gatepass? This action cannot be undone. </p>
          <p class="text-muted"> <i>The related inspection will be closed if no active gatepass remains.</i> </p>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"> ❌ Cancel </button>
          <button type="button" class="btn btn-danger btn-sm" id="confirmDelBtn"> ✅ Confirm Delete </button>
        </div>
      </div>
    </div>
  </div>
  <!-- Modal HTML -->
  <div id="deleteGpModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="deleteGpModalLabel" aria-hidden="true">
    <div class="modal-dialog " role="document">
      <div class="modal-content">
        <!-- Modal Header -->
        <div class="modal-header bg-danger">
          <h5 class="modal-title" id="receiveStockModalLabel3">⚠️ Confirm Deletion</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <!-- Modal Body -->
        <div class="modal-body text-center">
          <p class=" text-dark"> Are you sure you want to <strong>delete</strong> this work order? This action cannot be undone. </p>
          <p class="text-muted"> <i>A detailed report of this change will be sent to the director for review.</i> </p>
        </div>
        <!-- Modal Footer -->
        <div class="modal-footer justify-content-center">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"> ❌ Cancel </button>
          <button type="button" class="btn btn-danger btn-sm" id="confirmDelGpBtn"> ✅ Confirm Delete </button>
        </div>
      </div>
    </div>
  </div>
  <div id="activateInspModal" class="modal fade" tabindex="-1" role="dialog" aria-labelledby="activateModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="activateModalLabel2">Confirm Activation</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
        </div>
        <div class="modal-body"> You can activate this inspection button. A detailed report of this change will be sent to the director for review. After this modification, the button will be disabled. </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
          <button type="button" class="btn btn-primary" id="confirmActivateInspBtn">OK</button>
        </div>
      </div>
    </div>
  </div>
  <!-- Lab Request Modal -->
  <div class="modal fade" id="labRequestModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-md">
      <div class="modal-content ">
        <!-- Header -->
        <div class="modal-header wo-lab-header">
          <h4 class="modal-title"> <i class="fa fa-flask"></i> Lab Test Request </h4>
          <button type="button" class="close wo-modal-close-light" data-dismiss="modal">×</button>
        </div>
        <!-- Body -->
        <div class="modal-body wo-lab-body">
          <table class="table table-bordered table-condensed ">
            <tbody>
              <tr>
                <th class="wo-lab-label wo-w-40">Lot Number</th>
                <td><span id="modalLotNo" class="badge badge-info  "></span> </td>
              </tr>
              <tr>
                <th class="wo-lab-label">Work Order ID</th>
                <td><span id="modalWorkOrder" class="badge badge-primary  "></span> </td>
              </tr>
            </tbody>
          </table>
          <input type="hidden" id="modalLotId">
          <div class="form-group">
            <label for="labRemarks" class="control-label"> <i class="fa fa-commenting"></i> Remarks / Comments </label>
            <textarea id="labRemarks" class="form-control" rows="3" placeholder="Enter remarks"></textarea>
          </div>
          <div class="form-group">
            <label for="labMeter" class="control-label"> <i class="fa fa-ruler"></i> Total Meter </label>
            <input type="number" id="labMeter" class="form-control" placeholder="Enter total meter">
          </div>
        </div>
        <!-- Footer -->
        <div class="modal-footer wo-lab-footer">
          <button type="button" class="btn btn-default btn-sm" data-dismiss="modal"> <i class="fa fa-times"></i> Cancel </button>
          <button type="button" class="btn btn-success btn-sm" onClick="confirmLabRequest()"> <i class="fa fa-check"></i> Confirm Request </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal -->
	<div class="modal fade" id="beamReturnBeamModal" tabindex="-1" role="dialog" aria-labelledby="beamReturnModalLabel" aria-hidden="true">
	  <div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
		  <form method="post" action="<?php echo route('sendItemReturnRequest'); ?>" class="form-horizontal" autocomplete="off" onsubmit="return validateBeamReturnForm(this)">
			<?php echo csrf_field(); ?>
			<div class="modal-header">
			  <h5 class="modal-title" id="beamReturnModalLabel">Beam Item Return: <span id="modalBeamLotNumber"></span></h5>
			  <button type="button" class="close" data-dismiss="modal" aria-label="Close"> <span aria-hidden="true">&times;</span> </button>
			</div>

			<div class="modal-body">
			  <input type="hidden" id="beamWprId" name="wprId" value="">
			  <input type="hidden" id="beamChkworkOrderId" name="workOrderId" value="">

			  <table class="table" id="beamReturnItemsTable">
				<thead>
				  <tr>
					<th># StockId</th>
					<th>Taka Number</th>
					<th>Received Meter</th>
					<th>Used Meter</th>
					<th>Return Meter</th>
					<th><input type="checkbox" id="beamSelectAll" onclick="toggleSelectAllBeam(this)">All</th>
				  </tr>
				</thead>
				<tbody>
				  <!-- Dynamic rows appended here -->
				</tbody>
			  </table>
			</div>

			<div class="modal-footer">
			  <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
			  <button type="submit" class="btn btn-primary">Return</button>
			</div>
		  </form>
		</div>
	  </div>
	</div>

  <!-- Close Work Order Modal -->
	<div id="closeActivateModal" class="modal fade" role="dialog" aria-labelledby="activateModalLabel3" aria-hidden="true">
	  <div class="modal-dialog close-workorder-dialog" role="document">
		<div class="modal-content">
		  <div class="modal-header close-workorder-header">
			<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			<div class="close-workorder-title-row">
				<span class="close-workorder-icon"><i class="fa fa-exclamation-triangle"></i></span>
				<div>
					<h4 class="modal-title" id="activateModalLabel3">Confirm to Close this Work Order</h4>
					<p class="close-workorder-subtitle">This action will stop further work on the selected work order.</p>
				</div>
			</div>
		  </div>
		  <div class="modal-body close-workorder-body">
			<div class="close-workorder-alert">
				<i class="fa fa-ban"></i>
				<div>
					<strong>Are you sure you want to close this work order?</strong>
					<span>After confirmation, this work order cannot continue through the regular process from this page.</span>
				</div>
			</div>
		  </div>
		  <div class="modal-footer close-workorder-footer">
			<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> Cancel</button>
			<button type="button" class="btn btn-danger" id="confirmCloseWOBtn"><i class="fa fa-check"></i> Close Work Order</button>
		  </div>
		</div>
	  </div>
	</div>

<div class="modal fade" id="workOrderTotalModal" tabindex="-1" role="dialog" aria-labelledby="workOrderTotalModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="workOrderTotalModalLabel">
                    <i class="fa fa-bar-chart"></i> Work Order Totals
                </h4>
            </div>

            <div class="modal-body">
                <div id="totalLoading" class="loading-wrap wo-hidden">
                    <div class="spinner"></div>
                    <div class="loading-text">Loading totals...</div>
                </div>

                <div id="totalDataWrap">
                    <div class="total-box mtr">
                        <div class="total-icon"><i class="fa fa-arrows-h"></i></div>
                        <div class="total-label">Total Meter</div>
                        <div class="total-value mtr" id="showTotMtr">0</div>
                    </div>

                    <div class="total-box insp">
                        <div class="total-icon"><i class="fa fa-search"></i></div>
                        <div class="total-label">Total Inspected Meter</div>
                        <div class="total-value insp" id="showTotInspMtr">0</div>
                    </div>

                    <div class="total-box req">
                        <div class="total-icon"><i class="fa fa-check-circle"></i></div>
                        <div class="total-label">Total Required</div>
                        <div class="total-value req" id="showTotReqMtr">0</div>
                    </div>

                    <div class="summary-note">
                        <i class="fa fa-info-circle"></i>
                        These totals are calculated from the currently applied filters.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="planningWarningModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">

            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true"></button>
                <h4 class="modal-title text-primary"><i class="fa fa-info-circle"></i> Planning Notice</h4>
            </div>

            <div class="modal-body">
                <div class="panel panel-primary">
                    <div class="panel-heading">
                        <h4 class="panel-title">
                            <i class="fa fa-clipboard"></i> Planning Required Before Work
                        </h4>
                    </div>

                    <div class="panel-body text-center">
                        <h3 class="text-primary">
                            Planning has not been created
                        </h3>

                        <p class="lead">
                            Lot Number <span class="label label-primary" id="planningLotNumber"></span>
                        </p>

                        <div class="well well-sm text-left">
                            <h4 class="text-primary">
                                <i class="fa fa-check-circle"></i> Current Permission
                            </h4>
                            <p>
                                You can continue for now by clicking the <strong>OK</strong> button.
                            </p>
                        </div>

                        <div class="list-group text-left">
                            <div class="list-group-item list-group-item-info">
                                <h4 class="list-group-item-heading">
                                    <i class="fa fa-info-circle"></i> Future Rule
                                </h4>
                                <p class="list-group-item-text">
                                    This temporary facility will be disabled in the future. Please create the planning first before starting work or inspection for any lot.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><i class="fa fa-check"></i> OK, Continue for Now</button>
            </div>

        </div>
    </div>
</div>

  <!-------------------Modal End-------------------->

