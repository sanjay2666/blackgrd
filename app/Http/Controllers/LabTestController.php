<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; 
use App\Models\LabTest;
use App\Models\LabTestResult;
use App\Models\LabTestRequest;
use App\Models\LabTestStandard;
use App\Models\LabRequirement;
use App\Models\WorkProcessRequirement;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use App\Models\Individual;
use App\Models\SaleOrder;
use App\Models\SaleOrderItem;
use App\Models\LabColourFastness;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use DB;
use Log;
use Validator, Auth, Session, Hash;  
use Carbon\Carbon;

class LabTestController extends Controller
{
 
	public function __construct()
	{
		$this->middleware('auth');
	}
	public function print_lab_report_old($id)
	{		 
		$id 	 = dec($id);
		$labTest = LabTest::with('Item')->with('LabTestResult')->findOrFail($id);
		
		$labRequestId = $labTest->lab_request_id ?? null;
		$labRequestRequirementId = $labTest->lab_request_requirement_id ?? null;
		
		$labRequest = $labRequestId ? LabTestRequest::find($labRequestId) : null;
		$requirement = $labRequestRequirementId
			? LabRequirement::with('labTestRequest')->find($labRequestRequirementId)
			: null;
	 
		$resultsCollection 	= $labTest->results ?? collect();		
		$colorName 			= $requirement->color_classification;
		$itemId 			= $labTest->item_id;	
		
		// echo "<pre>"; print_r($requirement); exit;			 
		$viewData = [
			'labTest'           => $labTest,
			'labRequest'        => $labRequest,
			'requirement'       => $requirement,
			'resultsCollection' => $resultsCollection,
		];

		return view('html.labrequest.print_lab_report', $viewData);	
		
	}
	
	
	public function print_lab_report($id)
	{
		if (empty(CommonController::checkPageViewPermission())) {
			return redirect()->back()->with([
				'message'      => 'Access denied! You do not have permission to access this page.',
				'messageClass' => 'errorClass'
			]);
		}
		
		$id = dec($id);

		$labTest = LabTest::with(['Item', 'LabTestResult'])->findOrFail($id);

		// Get Lab Request (if exists)
		$labRequest = null;
		if (!empty($labTest->lab_request_id)) {
			$labRequest = LabTestRequest::find($labTest->lab_request_id);
		}

		// Get Lab Requirement (if exists)
		$requirement = null;
		if (!empty($labTest->lab_request_requirement_id)) {
			$requirement = LabRequirement::with('labTestRequest')->find($labTest->lab_request_requirement_id);
		}
 
		  // echo "<pre>"; print_r($labTest); exit;
 

		// Build a lightweight $report array so the view can safely use it
		$report = [
			'sample_desc'                 => $labTest->sample_description ?? null,
			'component'                   => $labTest->component ?? null,
			'fabric_supplier'             => $labTest->fabric_supplier ?? null,
			'lot_number'                  => $labTest->lot_number ?? null,
			'colour'                      => $labTest->colour ?? null,
			'po_no'                       => $labTest->po_number ?? null,
			'item_code'                   => $labTest->item_code ?? null,
			'model_no'                    => $labTest->model_no ?? null,
			'traceability_no'             => $labTest->traceability_no ?? null,
			'quantity'                    => $labTest->quantity ?? null,
			'tsr'                         => $labTest->tsr ?? null,
			'test_stage'                  => $labTest->test_stage ?? null,
			'component_validation_report' => $labTest->component_validation_report ?? null,
		];

		// Normalize color classification into an uppercase array
		$clsArr = [];
		$clsString = $requirement->color_classification ?? ($requirement['color_classification'] ?? '');
		if (is_string($clsString) && $clsString !== '') {
			$clsArr = array_map('strtoupper', array_map('trim', explode(',', $clsString)));
		} elseif (is_array($clsString)) {
			$clsArr = array_map('strtoupper', $clsString);
		}

		$dateIn  = $requirement && $requirement->created_at ? Carbon::parse($requirement->created_at) : null;
		$dateOut = $labTest && $labTest->created_at ? Carbon::parse($labTest->created_at) : null;

		$leadTimeText = '-';
		if ($dateIn && $dateOut) 
		{
			$days = $dateIn->diffInDays($dateOut);  // minimum 1 day  show kerna hai. 1 se jayda ho to Days 
			if ($days < 1) {  $days = 1;  }
			$label = ($days == 1) ? 'Day' : 'Days';
			$leadTimeText = str_pad($days, 2, '0', STR_PAD_LEFT) . " " . $label;	 
		}
		
		
		
		return view('html.labrequest.print_lab_report', [
			'labTest'           => $labTest,
			'labRequest'        => $labRequest,
			'requirement'       => $requirement,
			'report'            => $report,
			'clsArr'            => $clsArr,
			'leadTimeText'      => $leadTimeText,
		]);
	}

	  
	public function store(Request $request)
	{ 
		if (empty(CommonController::checkPageViewPermission())) {
			return redirect()->back()->with([
				'message'      => 'Access denied! You do not have permission to access this page.',
				'messageClass' => 'errorClass'
			]);
		}
		 // echo "<pre>"; print_r($request->all()); exit;		
		$request->validate([
			'work_order_id'    => 'required|string',
			'report_date'      => 'required|date',
			'test_report_type' => 'required|string',
		]);

		$standardsArr = $request->input('standards', []);
		$userId       = Auth::id(); 
		$user         = User::find($userId);
		$individualId = $user->individual_id ?? null;
		 
		DB::beginTransaction();
		try {
			
			$labTest = LabTest::create([
				'lab_request_id' 				=> $request->input('lab_request_id'),
				'lab_request_requirement_id' 	=> $request->input('lab_request_requirement_id'),
				'po_number' 					=> $request->input('po_number'),
				'item_name' 					=> $request->input('item_name'),
				'item_id' 						=> $request->input('item_id'),
				'colour' 						=> $request->input('colour'),
				'lot_number' 					=> $request->input('lot_number'),
				'tested_by' 					=> $userId,
				'test_report_type' 				=> $request->input('test_report_type'),
				'work_order_id' 				=> $request->input('work_order_id'), 
				'report_date' 					=> date('Y-m-d', strtotime($request->input('report_date'))),
				'environment' 					=> $request->input('environment'),
				'created_by' 					=> $userId,
			]);
 
			 
			 // 1 - Mass
			 
			if (! empty($standardsArr['mass_per_unit_area'])) 
			{ 
				$meta = [
					'readings' 		=> $request->input('mass', []),
					'unit' 			=> $request->input('mass_per_unit_area_unit'),
					'temperature' 	=> $request->input('mass_temperature'),
					'rh' 			=> $request->input('mass_rh'),
					'remarks' 		=> $request->input('mass_remarks'),
					'summary' => [
						'avg' 			=> $request->input('mass_avg'),
						'sd' 			=> $request->input('mass_sd'),
						'cv_percent' 	=> $request->input('mass_cv_percent'),
					],
				];
				LabTestResult::create([
					'lab_test_id' 		=> $labTest->id,
					'test_key' 			=> 'mass_per_unit_area', 
					'sub_key'        	=> null,     
					'reading_index' 	=> 1,
					
					'reading_value' 	=> json_encode($meta['readings']),
					'unit' 				=> $meta['unit'],
					'standard_code' 	=> $standardsArr['mass_per_unit_area'],
					'standard_id' 		=> $request->input('mass_per_unit_area_standard_id') ?? null,  
					'standard_name'     => $request->input('mass_per_unit_area_standard_name') ?? null,  
					'temperature' 		=> is_numeric($meta['temperature']) ? (float)$meta['temperature'] : null,
					'rh' 				=> is_numeric($meta['rh']) ? (float)$meta['rh'] : null,
					'remarks' 			=> $meta['remarks'],
					'meta' 				=> $meta,
					'test_version' 		=> 'Version C',
					'created_by' 		=> $userId,
				]);
			}

			 // 2 - Useful Width
			  
			if (! empty($standardsArr['useful_width'])) 
			{
				 // Normalize fields that might be arrays
				$unitRaw = $request->input('useful_width_unit');
				$unit = is_array($unitRaw) ? implode(',', $unitRaw) : $unitRaw;

				$standardRaw = $standardsArr['useful_width'];
				$standardName = is_array($standardRaw) ? implode(',', $standardRaw) : $standardRaw;

				$remarksRaw = $request->input('width_remarks');
				$remarks = is_array($remarksRaw) ? implode(', ', $remarksRaw) : $remarksRaw;

				$readings = $request->input('width', []);  

				$meta = [
					'readings'    => $readings,
					'unit'        => $unit,
					'temperature' => $request->input('width_temperature'),
					'rh'          => $request->input('width_rh'),
					'remarks'     => $remarks,
					'summary'     => [
						'avg'       => $request->input('width_avg'),
						'sd'        => $request->input('width_sd'),
						'cv_percent'=> $request->input('width_cv_percent'),
					],
				];

				LabTestResult::create([
					'lab_test_id'    => $labTest->id,
					'test_key'       => 'useful_width',
					'sub_key'        => null,                
					'reading_index'  => 1,   
					'reading_value'  => json_encode($readings, JSON_UNESCAPED_UNICODE),
					'unit'           => $unit,
					'standard_code'  => $standardName,
					'standard_id'    => $request->input('useful_width_standard_id') ?? null, 
					'standard_name'  => $request->input('useful_width_standard_name') ?? null,  
					'temperature'    => is_numeric($meta['temperature']) ? (float)$meta['temperature'] : null,
					'rh'             => is_numeric($meta['rh']) ? (float)$meta['rh'] : null,
					'remarks'        => $meta['remarks'],
					'meta'           => $meta,             // requires model cast 'meta' => 'array'
					'created_by'     => $userId,
					'test_version' 	 => 'Version 5- NR1',
				]);
			}
 
			 // 3 - pH
			
			if (! empty($standardsArr['ph_aqueous_extract'])) {
				$meta = [
					'readings' => $request->input('ph', []),
					'unit' => $request->input('ph_aqueous_extract_unit'),
					'temperature' => $request->input('ph_temperature'),
					'rh' => $request->input('ph_rh'),
					'remarks' => $request->input('ph_remarks'),
					'extraction' => [
						'extraction_ph' => $request->input('extraction_ph'),
						'extraction_temp' => $request->input('extraction_temp'),
						'extraction_temp2' => $request->input('extraction_temp2'),
						'extraction_note' => $request->input('ph_note'),
					],
					'extracted_mean' => $request->input('ph_extracted_mean'),
				];
				LabTestResult::create([
					'lab_test_id' 			=> $labTest->id,
					'test_key' 				=> 'ph_aqueous_extract',
					'sub_key' 				=> null,
					'reading_index' 		=> 1, 
					'reading_value' 		=> json_encode($meta['readings']), 
					'unit' 					=> $meta['unit'], 
					'standard_code' 		=> $standardsArr['ph_aqueous_extract'],
					'standard_id' 			=> $request->input('ph_aqueous_extract_standard_id') ?? null,  
					'standard_name'     	=> $request->input('ph_aqueous_extract_standard_name') ?? null, 
					'temperature'	 		=> is_numeric($meta['temperature']) ? (float)$meta['temperature'] : null,
					'rh' 					=> is_numeric($meta['rh']) ? (float)$meta['rh'] : null,
					'remarks' 				=> $meta['remarks'],
					'meta' 					=> $meta,
					'created_by' 			=> $userId,
					'test_version' 	 		=> 'Version 2',
				]);
			}
 
			 // 4 - Abrasion Resistance 
			if (! empty($standardsArr['abrasion_resistance'])) 
			{
				// Normalize single-value fields that might accidentally be arrays
				$unitRaw = $request->input('abrasion_resistance_unit');
				$unit = is_array($unitRaw) ? implode(',', $unitRaw) : $unitRaw;

				$standardRaw = $standardsArr['abrasion_resistance'];
				$standardCode = is_array($standardRaw) ? implode(',', $standardRaw) : $standardRaw;

				$standardId = $request->input('abrasion_resistance_standard_id') ?? null;
				$standardName = $request->input('abrasion_resistance_standard_name') ?? null;

				// Use the actual input name from your POST dump
				$readings = $request->input('abrasion_cycles', []); // <-- was 'abrasion' before

				$meta = [
					'readings'    => $readings,
					'unit'        => $unit,
					'load'        => $request->input('abrasion_resistance_load'),
					'abrasive'    => $request->input('abrasion_resistance_abrasive'),
					'temperature' => $request->input('abrasion_resistance_temperature'),
					'rh'          => $request->input('abrasion_resistance_rh'),
					'remarks'     => $request->input('abrasion_remarks'),
					'summary'     => [
						'avg'        => $request->input('abrasion_avg'),
						'sd'         => $request->input('abrasion_sd'),
						'cv_percent' => $request->input('abrasion_cv_percent'),
					],
				];

				// Decide what to store in reading_value:
				// Option A (recommended): store numeric avg (if present) so numeric queries are easy
				$readingValue = is_numeric($meta['summary']['avg']) ? (float)$meta['summary']['avg'] : null;

				// Option B (alternate): if you really want the full JSON string in reading_value,
				// uncomment this line and comment Option A above.
				// $readingValue = json_encode($readings, JSON_UNESCAPED_UNICODE);

				LabTestResult::create([
					'lab_test_id'      => $labTest->id,
					'test_key'         => 'abrasion_resistance',
					'sub_key'          => 'abrasion',  // keep short and consistent
					'reading_index'    => 0,           // summary row convention (0) or null
					'reading_value'    => $readingValue,
					'unit'             => $unit,
					'standard_code'    => $standardCode,
					'standard_id'      => $standardId,
					'standard_name'    => $standardName,
					'temperature'      => is_numeric($meta['temperature']) ? (float)$meta['temperature'] : null,
					'rh'               => is_numeric($meta['rh']) ? (float)$meta['rh'] : null,
					'remarks'          => is_string($meta['remarks']) ? $meta['remarks'] : (is_array($meta['remarks']) ? implode(', ', $meta['remarks']) : null),
					'meta'             => $meta,      // requires model cast 'meta' => 'array'
					'created_by'       => $userId,
					'test_version' 	   => 'Version 3 - NR 1',
				]);
			}
 			
			 // 5 - Max Force & Elongation (warp/weft)
			if (! empty($standardsArr['max_force_elongation'])) 
			{
				
				$unitRaw = $request->input('max_force_elongation_unit');
				$unit = is_array($unitRaw) ? implode(',', $unitRaw) : $unitRaw;

				$standardRaw = $standardsArr['max_force_elongation'];
				$standardCode = is_array($standardRaw) ? implode(',', $standardRaw) : $standardRaw;

				$standardId = $request->input('max_force_elongation_standard_id') ?? null;
				$standardName = $request->input('max_force_elongation_standard_name') ?? null;

				$remarksRaw = $request->input('max_force_elongation_remarks');
				$remarks = is_array($remarksRaw) ? implode(', ', $remarksRaw) : $remarksRaw;

				 
				$warpForceRaw = $request->input('warp_force', []);
				$weftForceRaw = $request->input('weft_force', []);
				$warpElongRaw = $request->input('warp_elongation', []);
				$weftElongRaw = $request->input('weft_elongation', []);

				 
				$cleanNumericArray = function($arr){
					if (!is_array($arr)) return [];
					$out = [];
					foreach ($arr as $v) {
						
						if (is_numeric($v)) {
						  
							$out[] = (float) $v;
						} else {
							 
							$out[] = null;
						}
					}
					return $out;
				};

				$warpForce = $cleanNumericArray($warpForceRaw);
				$weftForce = $cleanNumericArray($weftForceRaw);
				$warpElong = $cleanNumericArray($warpElongRaw);
				$weftElong = $cleanNumericArray($weftElongRaw);    
				$summaries = [
					'warp_force' => [
						'avg' => $request->input('warp_force_avg'),
						'sd'  => $request->input('warp_force_sd'),
						'cv_percent' => $request->input('warp_force_cv_percent'),
					],
					'weft_force' => [
						'avg' => $request->input('weft_force_avg'),
						'sd'  => $request->input('weft_force_sd'),
						'cv_percent' => $request->input('weft_force_cv_percent'),
					],
					'warp_elongation' => [
						'avg' => $request->input('warp_elongation_avg'),
						'sd'  => $request->input('warp_elongation_sd'),
						'cv_percent' => $request->input('warp_elongation_cv_percent'),
					],
					'weft_elongation' => [
						'avg' => $request->input('weft_elongation_avg'),
						'sd'  => $request->input('weft_elongation_sd'),
						'cv_percent' => $request->input('weft_elongation_cv_percent'),
					],
				];

				$meta = [
					'warp_force'       => $warpForce,
					'weft_force'       => $weftForce,
					'warp_elongation'  => $warpElong,
					'weft_elongation'  => $weftElong,
					'unit'             => $unit,
					'temperature'      => $request->input('warp_weft_force_temperature'),
					'rh'               => $request->input('warp_weft_force_rh'),
					'remarks'          => $remarks,
					'summaries'        => $summaries,
				]; 
				$readingValueForStorage = json_encode($summaries, JSON_UNESCAPED_UNICODE);
				
				LabTestResult::create([
					'lab_test_id'       => $labTest->id,
					'test_key'          => 'max_force_elongation',
					'sub_key'           => 'max_force_elongation',
					'reading_index'     => 0,                      
					'reading_value'     => $readingValueForStorage,  
					'unit'              => $unit,
					'standard_code'     => $standardCode,
					'standard_id'       => $standardId,
					'standard_name'     => $standardName,
					'temperature'       => is_numeric($meta['temperature']) ? (float)$meta['temperature'] : null,
					'rh'                => is_numeric($meta['rh']) ? (float)$meta['rh'] : null,
					'remarks'           => $meta['remarks'],
					'meta'              => $meta,                  
					'created_by'        => $userId,
					'test_version' 	   => 'Version 3 - NR 1',
					
				]);
			}
 
			 // 6 - Tear Force (warp/weft) 
			if (! empty($standardsArr['tear_force'])) 
			{

				// Normalize single-value fields
				$unitRaw = $request->input('tear_force_unit');
				$unit = is_array($unitRaw) ? implode(',', $unitRaw) : $unitRaw;

				$standardRaw = $standardsArr['tear_force'];
				$standardCode = is_array($standardRaw) ? implode(',', $standardRaw) : $standardRaw;

				$standardId = $request->input('tear_force_standard_id') ?? null;
				$standardName = $request->input('tear_force_standard_name') ?? null;

				$remarksRaw = $request->input('tear_strength_remarks');
				$remarks = is_array($remarksRaw) ? implode(', ', $remarksRaw) : $remarksRaw;

				// Raw arrays from request
				$warpRaw = $request->input('tear_warp', []);
				$weftRaw = $request->input('tear_weft', []); 
				$cleanNumericArray = function($arr) {
					if (! is_array($arr)) return [];
					$out = [];
					foreach ($arr as $v) {
						$out[] = is_numeric($v) ? (float) $v : null;
					}
					return $out;
				};

				$warp = $cleanNumericArray($warpRaw);
				$weft = $cleanNumericArray($weftRaw); 
				$summaries = [
					'tear_warp' => [
						'avg' => is_numeric($request->input('tear_warp_avg')) ? (float)$request->input('tear_warp_avg') : null,
						'sd'  => is_numeric($request->input('tear_warp_sd')) ? (float)$request->input('tear_warp_sd') : null,
						'cv_percent' => is_numeric($request->input('tear_warp_cv_percent')) ? (float)$request->input('tear_warp_cv_percent') : null,
					],
					'tear_weft' => [
						'avg' => is_numeric($request->input('tear_weft_avg')) ? (float)$request->input('tear_weft_avg') : null,
						'sd'  => is_numeric($request->input('tear_weft_sd')) ? (float)$request->input('tear_weft_sd') : null,
						'cv_percent' => is_numeric($request->input('tear_weft_cv_percent')) ? (float)$request->input('tear_weft_cv_percent') : null,
					],
				];

				$meta = [
					'tear_warp' => $warp,
					'tear_weft' => $weft,
					'unit' => $unit,
					'temperature' => $request->input('tear_force_temperature'),
					'rh' => $request->input('tear_force_rh'),
					'remarks' => $remarks,
					'summaries' => $summaries,
				]; 
				$warpAvg = $summaries['tear_warp']['avg'];
				$weftAvg = $summaries['tear_weft']['avg'];
				 
				$readingValue = null;
				if (is_numeric($warpAvg) && is_numeric($weftAvg)) 
				{
					$readingValue = ($warpAvg + $weftAvg) / 2.0;
				} elseif (is_numeric($warpAvg)) {
					$readingValue = $warpAvg;
				} elseif (is_numeric($weftAvg)) {
					$readingValue = $weftAvg;
				}

				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'tear_force',
					'sub_key'         => 'tear_force',
					'reading_index'   => 0,                      
					'reading_value'   => $readingValue,           
					'unit'            => $unit,
					'standard_code'   => $standardCode,
					'standard_id'     => $standardId,
					'standard_name'   => $standardName,
					'temperature'     => is_numeric($meta['temperature']) ? (float)$meta['temperature'] : null,
					'rh'              => is_numeric($meta['rh']) ? (float)$meta['rh'] : null,
					'remarks'         => $meta['remarks'],
					'meta'            => $meta,                   
					'created_by'      => $userId,
					'test_version' 	   => 'Version A - Elemendorf tester',
				]);
			}
						
			// 7 - Colour Fastness to Rubbing
		 
			if (! empty($standardsArr['cf_rubbing'])) 
			{
				// normalize simple fields
				$unitRaw 		= $request->input('cf_rubbing_unit');
				$unit 			= is_array($unitRaw) ? implode(',', $unitRaw) : $unitRaw;

				$standardRaw 	= $standardsArr['cf_rubbing'];
				$standardCode 	= is_array($standardRaw) ? implode(',', $standardRaw) : $standardRaw;

				$standardId 	= $request->input('cf_rubbing_standard_id') ?? null;
				$standardName 	= $request->input('cf_rubbing_standard_name') ?? null;

				$sideRaw 		= $request->input('cf_rubbing_side');
				$side 			= is_array($sideRaw) ? implode(',', $sideRaw) : $sideRaw;

				$remarksRaw 	= $request->input('cf_rubbing_remarks');
				$remarks 		= is_array($remarksRaw) ? implode(', ', $remarksRaw) : $remarksRaw;
 
				// Build meta with numeric casting where appropriate
				$meta = [
					'length' => $request->input('cf_rubbing_length'),
					'width'  => $request->input('cf_rubbing_width'),
					'unit'   => $unit,
					'side'   => $side,
					'temperature' => $request->input('cf_rubbing_temperature'),
					'rh'          => $request->input('cf_rubbing_rh'),
					'dry' => [
						'length_precise' => $request->input('cf_rubbing_dry_length_precise'),
						'length_grey'    => $request->input('cf_rubbing_dry_length_grey'),
						'width_precise'  => $request->input('cf_rubbing_dry_width_precise'),
						'width_grey'     => $request->input('cf_rubbing_dry_width_grey'),
					],
					'wet' => [
						'length_precise' => $request->input('cf_rubbing_wet_length_precise'),
						'length_grey'    => $request->input('cf_rubbing_wet_length_grey'),
						'width_precise'  => $request->input('cf_rubbing_wet_width_precise'),
						'width_grey'     => $request->input('cf_rubbing_wet_width_grey'),
					],
					'remarks' => $remarks,
				]; 
				 
				$readingValue = null;
				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'cf_rubbing',
					'sub_key'         => 'cf_rubbing',            
					'reading_index'   => 0,                      
					'reading_value'   => $readingValue,           
					'unit'            => $unit,
					'standard_code'   => $standardCode,
					'standard_id'     => $standardId,
					'standard_name'   => $standardName,
					'temperature'     => $meta['temperature'],
					'rh'              => $meta['rh'],
					'remarks'         => $meta['remarks'],
					'meta'            => $meta,                  
					'created_by'      => $userId,
					'test_version' 	  => 'Version 2',
				]);
			}
 
			// 8 - Colour Fastness to Water
			if (! empty($standardsArr['cf_waterrrrrrrrrrrrrrrrr'])) 
			{ 
				// Accept both cfw_ and cf_water_ prefixes (form uses cfw_ in your dump)
				$get = function($keys) use ($request) {
					// $keys: array of candidate input names in priority order
					foreach ($keys as $k) {
						$v = $request->input($k);
						if (!is_null($v) && $v !== '') return $v;
					}
					return null;
				};

				// short helpers
				$toNumeric = function($v) {
					return is_numeric($v) ? (float)$v : null;
				};
				$normalizeScalar = function($v) {
					return is_array($v) ? implode(', ', $v) : $v;
				};

				// read top-level fields (try cfw_ then cf_water_ then cf_water fallback)
				$unit       = $normalizeScalar($get(['cf_water_unit','cfw_unit','cf_water_unit']));
				$temperature= $toNumeric($get(['cfw_temperature','cf_water_temperature','cfw_temp']));
				$rh         = $toNumeric($get(['cfw_rh','cf_water_rh','cfw_humidity']));
				$remarks    = $normalizeScalar($get(['cfw_remarks','cf_water_remarks','cfw_remark','cf_water_remark']));

				// Colour change fields (your dump uses cfw_ names)
				$change_precise = $toNumeric($get(['cfw_colour_change_precise','cf_water_change_precise','cfw_change_precise']));
				$change_grey    = $toNumeric($get(['cfw_colour_change_grey','cf_water_change_grey','cfw_change_grey']));

				// substrates — try cfw_ keys first, then cf_water_ keys
				$substrates = [
					'acetate' => [
						'precise' => $toNumeric($get(['cfw_acetate_precise','cf_water_acetate_precise'])),
						'grey'    => $toNumeric($get(['cfw_acetate_grey','cf_water_acetate_grey'])),
					],
					'cotton' => [
						'precise' => $toNumeric($get(['cfw_cotton_precise','cf_water_cotton_precise','cfw_cotton_precise'])),
						'grey'    => $toNumeric($get(['cfw_cotton_grey','cf_water_cotton_grey','cfw_cotton_grey'])),
					],
					'wool' => [
						'precise' => $toNumeric($get(['cfw_wool_precise','cf_water_wool_precise'])),
						'grey'    => $toNumeric($get(['cfw_wool_grey','cf_water_wool_grey'])),
					],
					'polyester' => [
						'precise' => $toNumeric($get(['cfw_polyester_precise','cf_water_poly_precise','cfw_poly_precise'])),
						'grey'    => $toNumeric($get(['cfw_polyester_grey','cf_water_poly_grey','cfw_poly_grey'])),
					],
					'acrylic' => [
						'precise' => $toNumeric($get(['cfw_acrylic_precise','cf_water_acrylic_precise'])),
						'grey'    => $toNumeric($get(['cfw_acrylic_grey','cf_water_acrylic_grey'])),
					],
					'nylon' => [
						'precise' => $toNumeric($get(['cfw_nylon_precise','cf_water_nylon_precise'])),
						'grey'    => $toNumeric($get(['cfw_nylon_grey','cf_water_nylon_grey'])),
					],
					'polyester_alt' => [ // in case of different naming, keep safe
						'precise' => $toNumeric($get(['cfw_poly_precise','cf_water_poly_precise'])),
						'grey'    => $toNumeric($get(['cfw_poly_grey','cf_water_poly_grey'])),
					],
					'cotton_alt' => [ // fallback variants
						'precise' => $toNumeric($get(['cfw_cotton_precise','cf_water_cotton_precise'])),
						'grey'    => $toNumeric($get(['cfw_cotton_grey','cf_water_cotton_grey'])),
					],
				];

				// build meta (keep names consistent for reporting)
				$meta = [
					'change' => [
						'precise' => $change_precise,
						'grey' => $change_grey,
					],
					'substrates' => [
						'acetate'   => $substrates['acetate'],
						'cotton'    => $substrates['cotton'] ?? $substrates['cotton_alt'],
						'wool'      => $substrates['wool'],
						'polyester' => $substrates['polyester'] ?? $substrates['polyester_alt'],
						'acrylic'   => $substrates['acrylic'],
						'nylon'     => $substrates['nylon'],
					],
					'unit' => $unit,
					'temperature' => $temperature,
					'rh' => $rh,
					'remarks' => $remarks,
				]; 
				$readingValue = null; 
				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'cf_water',
					'sub_key'         => 'cf_water',
					'reading_index'   => 0,                 
					'reading_value'   => $readingValue,
					'unit'            => $unit,
					'standard_code'   => $standardsArr['cf_water'],
					'standard_id'     => $request->input('cf_water_standard_id') ?? $request->input('cfw_standard_id') ?? null,
					'standard_name'   => $request->input('cf_water_standard_name') ?? $request->input('cfw_standard_name') ?? null,
					'temperature'     => $temperature,
					'rh'              => $rh,
					'remarks'         => $remarks,
					'meta'            => $meta,             
					'created_by'      => $userId,
					'test_version' 	  => 'Version 6',
				]);
			}
			
			// 8 - Colour Fastness to Water (no helpers, save raw values)
			if (! empty($standardsArr['cf_water'])) 
			{
				// direct raw picks — no helpers, no casting, no formatting
				$unit 			= $request->input('cfw_unit') ?? $request->input('cf_water_unit') ?? null;
				$temperature 	= $request->input('cfw_temperature') ?? $request->input('cf_water_temperature') ?? null;
				$rh 			= $request->input('cfw_rh') ?? $request->input('cf_water_rh') ?? null;
				$remarks 		= $request->input('cfw_remarks') ?? $request->input('cf_water_remarks') ?? null;

				$change_precise = $request->input('cfw_colour_change_precise') ?? $request->input('cf_water_change_precise') ?? null;
				$change_grey    = $request->input('cfw_colour_change_grey') ?? $request->input('cf_water_change_grey') ?? null;

				$substrates = [
					'acetate' => [
						'precise' => $request->input('cfw_acetate_precise') ?? $request->input('cf_water_acetate_precise') ?? null,
						'grey'    => $request->input('cfw_acetate_grey')    ?? $request->input('cf_water_acetate_grey')    ?? null,
					],
					'cotton' => [
						'precise' => $request->input('cfw_cotton_precise') ?? $request->input('cf_water_cotton_precise') ?? null,
						'grey'    => $request->input('cfw_cotton_grey')    ?? $request->input('cf_water_cotton_grey')    ?? null,
					],
					'wool' => [
						'precise' => $request->input('cfw_wool_precise') ?? $request->input('cf_water_wool_precise') ?? null,
						'grey'    => $request->input('cfw_wool_grey')    ?? $request->input('cf_water_wool_grey')    ?? null,
					],
					'polyester' => [
						'precise' => $request->input('cfw_polyester_precise') ?? $request->input('cf_water_poly_precise') ?? null,
						'grey'    => $request->input('cfw_polyester_grey')    ?? $request->input('cf_water_poly_grey')    ?? null,
					],
					'acrylic' => [
						'precise' => $request->input('cfw_acrylic_precise') ?? $request->input('cf_water_acrylic_precise') ?? null,
						'grey'    => $request->input('cfw_acrylic_grey')    ?? $request->input('cf_water_acrylic_grey')    ?? null,
					],
					'nylon' => [
						'precise' => $request->input('cfw_nylon_precise') ?? $request->input('cf_water_nylon_precise') ?? null,
						'grey'    => $request->input('cfw_nylon_grey')    ?? $request->input('cf_water_nylon_grey')    ?? null,
					],
				];

				$meta = [
					'change' => [
						'precise' => $change_precise,
						'grey'    => $change_grey,
					],
					'substrates' => $substrates,
					'unit' => $unit,
					'temperature' => $temperature,
					'rh' => $rh,
					'remarks' => $remarks,
				];

				LabTestResult::create([
					'lab_test_id'    => $labTest->id,
					'test_key'       => 'cf_water',
					'sub_key'        => 'cf_water',
					'reading_index'  => 0,
					'reading_value'  => null,
					'unit'           => $unit,
					'standard_code'  => $standardsArr['cf_water'],
					'standard_id'    => $request->input('cf_water_standard_id') ?? $request->input('cfw_standard_id') ?? null,
					'standard_name'  => $request->input('cf_water_standard_name') ?? $request->input('cfw_standard_name') ?? null,
					'temperature'    => $temperature,
					'rh'             => $rh,
					'remarks'        => $remarks,
					'meta'           => $meta,   // raw values only
					'created_by'     => $userId,
					'test_version'   => 'Version 6',
				]);
			}
 
			// 9 - Colour Fastness to Sea Water
			if (! empty($standardsArr['cf_see_water'])) 
			{
				// Top-level fields
				$unit        = $request->input('cf_see_water_unit') 
								?? $request->input('cfsw_unit') 
								?? $request->input('cfw_unit');

				$temperature = $request->input('cfsw_temperature') 
								?? $request->input('cfw_temperature') 
								?? $request->input('cf_see_water_temperature');

				$rh          = $request->input('cfsw_rh') 
								?? $request->input('cfw_rh') 
								?? $request->input('cf_see_water_rh');

				$remarks     = $request->input('cfsw_remarks') 
								?? $request->input('cfw_remarks') 
								?? $request->input('cf_see_water_remarks');

				// Colour change
				$colour_precise = $request->input('cfsw_colour_change_precise') 
									?? $request->input('cfw_colour_change_precise') 
									?? $request->input('cf_see_water_colour_change_precise');

				$colour_grey    = $request->input('cfsw_colour_change_grey') 
									?? $request->input('cfw_colour_change_grey') 
									?? $request->input('cf_see_water_colour_change_grey');

				// Substrates
				$substrates = [
					'acetate' => [
						'precise' => $request->input('cfsw_acetate_precise') 
									?? $request->input('cfw_acetate_precise') 
									?? $request->input('cf_see_water_acetate_precise'),
						'grey'    => $request->input('cfsw_acetate_grey') 
									?? $request->input('cfw_acetate_grey') 
									?? $request->input('cf_see_water_acetate_grey'),
					],
					'cotton' => [
						'precise' => $request->input('cfsw_cotton_precise') 
									?? $request->input('cfw_cotton_precise') 
									?? $request->input('cf_see_water_cotton_precise'),
						'grey'    => $request->input('cfsw_cotton_grey') 
									?? $request->input('cfw_cotton_grey') 
									?? $request->input('cf_see_water_cotton_grey'),
					],
					'nylon' => [
						'precise' => $request->input('cfsw_nylon_precise') 
									?? $request->input('cfw_nylon_precise') 
									?? $request->input('cf_see_water_nylon_precise'),
						'grey'    => $request->input('cfsw_nylon_grey') 
									?? $request->input('cfw_nylon_grey') 
									?? $request->input('cf_see_water_nylon_grey'),
					],
					'polyester' => [
						'precise' => $request->input('cfsw_polyester_precise') 
									?? $request->input('cfw_polyester_precise') 
									?? $request->input('cf_see_water_polyester_precise'),
						'grey'    => $request->input('cfsw_polyester_grey') 
									?? $request->input('cfw_polyester_grey') 
									?? $request->input('cf_see_water_polyester_grey'),
					],
					'acrylic' => [
						'precise' => $request->input('cfsw_acrylic_precise') 
									?? $request->input('cfw_acrylic_precise') 
									?? $request->input('cf_see_water_acrylic_precise'),
						'grey'    => $request->input('cfsw_acrylic_grey') 
									?? $request->input('cfw_acrylic_grey') 
									?? $request->input('cf_see_water_acrylic_grey'),
					],
					'wool' => [
						'precise' => $request->input('cfsw_wool_precise') 
									?? $request->input('cfw_wool_precise') 
									?? $request->input('cf_see_water_wool_precise'),
						'grey'    => $request->input('cfsw_wool_grey') 
									?? $request->input('cfw_wool_grey') 
									?? $request->input('cf_see_water_wool_grey'),
					],
				];

				// Build meta
				$meta = [
					'colour_change' => [
						'precise' => $colour_precise,
						'grey'    => $colour_grey,
					],
					'substrates'  => $substrates,
					'unit'        => $unit,
					'temperature' => $temperature,
					'rh'          => $rh,
					'remarks'     => $remarks,
				]; 

				LabTestResult::create([
					'lab_test_id'      => $labTest->id,
					'test_key'         => 'cf_sea_water',
					'sub_key'          => 'cf_sea_water',
					'reading_index'    => 0,   
					'reading_value'    => null,
					'unit'             => $unit,
					'standard_code'    => $standardsArr['cf_see_water'],
					'standard_id'      => $request->input('cf_see_water_standard_id') ?? $request->input('cfsw_standard_id'),
					'standard_name'    => $request->input('cf_see_water_standard_name') ?? $request->input('cfsw_standard_name'),
					'temperature'      => $temperature,
					'rh'               => $rh,
					'remarks'          => $remarks,
					'meta'             => $meta,   
					'created_by'       => $userId,
					'test_version' 	  => 'Version 6',
				]);
			}
 
			// 10 - Colour Fastness to Perspiration
			if (! empty($standardsArr['cf_perspiration'])) 
			{
				// Helpers
				$toNumeric = function($v) {
					return is_numeric($v) ? (float)$v : null;
				};
				$normalizeScalar = function($v) {
					return is_array($v) ? implode(', ', $v) : $v;
				};

				// Normalize simple fields
				$unit        = $request->input('cf_perspiration_unit');
				$temperature = $request->input('cf_perspiration_temperature');
				$rh          = $request->input('cf_perspiration_rh');
				$remarks     = $request->input('cf_perspiration_remarks');

				// Colour change values
				$colour_change_precise = $request->input('cf_perspiration_colour_change_precise');
				$colour_change_grey    = $request->input('cf_perspiration_colour_change_grey');

				 
				$substrates = [
					'acetate' => [
						'precise' => $request->input('cf_perspiration_acetate_precise'),
						'grey'    => $request->input('cf_perspiration_acetate_grey'),
					],
					'cotton' => [
						'precise' => $request->input('cf_perspiration_cotton_precise'),
						'grey'    => $request->input('cf_perspiration_cotton_grey'),
					],
					'nylon' => [
						'precise' => $request->input('cf_perspiration_nylon_precise'),
						'grey'    => $request->input('cf_perspiration_nylon_grey'),
					],
					'polyester' => [
						'precise' => $request->input('cf_perspiration_polyester_precise'),
						'grey'    => $request->input('cf_perspiration_polyester_grey'),
					],
					'acrylic' => [
						'precise' => $request->input('cf_perspiration_acrylic_precise'),
						'grey'    => $request->input('cf_perspiration_acrylic_grey'),
					],
					'wool' => [
						'precise' => $request->input('cf_perspiration_wool_precise'),
						'grey'    => $request->input('cf_perspiration_wool_grey'),
					],
				];
				// Build meta consistently
				$meta = [
					'colour_change' => [
						'precise' => $colour_change_precise,
						'grey'    => $colour_change_grey,
					],
					'substrates' => $substrates,
					'unit' => $unit,
					'temperature' => $temperature,
					'rh' => $rh,
					'remarks' => $remarks,
				];
				
				$readingValue = null;
				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'cf_perspiration',
					'sub_key'         => 'cf_perspiration',     
					'reading_index'   => 0,                    
					'reading_value'   => $readingValue,       
					'unit'            => $unit,
					'standard_code'   => $standardsArr['cf_perspiration'] ?? null,
					'standard_id'     => $request->input('cf_perspiration_standard_id') ?? null,
					'standard_name'   => $request->input('cf_perspiration_standard_name') ?? null,
					'temperature'     => $temperature,
					'rh'              => $rh,
					'remarks'         => $remarks,
					'meta'            => $meta,                 
					'created_by'      => $userId,
					'test_version' 	  => 'Version 6',
				]);
			}
 
			
			// 11 - Colour Fastness to Chlorinated Water
			if (! empty($standardsArr['cf_chlorinated_water'])) 
			{
				$unit        = $request->input('cf_chlorinated_water_unit');
				$temperature = $request->input('cf_chlorinated_water_temperature');
				$rh          = $request->input('cf_chlorinated_water_rh');
				$remarks     = $request->input('cf_chlorinated_water_remarks');

				// colour change
				$change_precise = $request->input('cf_chlorinated_water_colour_change_precise');
				$change_grey    = $request->input('cf_chlorinated_water_colour_change_grey');

				// substrates
				$substrates = [
					'acetate' => [
						'precise' => $request->input('cf_chlorinated_water_acetate_precise'),
						'grey'    => $request->input('cf_chlorinated_water_acetate_grey'),
					],
					'cotton' => [
						'precise' => $request->input('cf_chlorinated_water_cotton_precise'),
						'grey'    => $request->input('cf_chlorinated_water_cotton_grey'),
					],
					'nylon' => [
						'precise' => $request->input('cf_chlorinated_water_nylon_precise'),
						'grey'    => $request->input('cf_chlorinated_water_nylon_grey'),
					],
					'polyester' => [
						'precise' => $request->input('cf_chlorinated_water_polyester_precise'),
						'grey'    => $request->input('cf_chlorinated_water_polyester_grey'),
					],
					'acrylic' => [
						'precise' => $request->input('cf_chlorinated_water_acrylic_precise'),
						'grey'    => $request->input('cf_chlorinated_water_acrylic_grey'),
					],
					'wool' => [
						'precise' => $request->input('cf_chlorinated_water_wool_precise'),
						'grey'    => $request->input('cf_chlorinated_water_wool_grey'),
					],
				];

				// build meta
				$meta = [
					'colour_change' => [
						'precise' => $change_precise,
						'grey'    => $change_grey,
					],
					'substrates' => $substrates,
					'unit' => $unit,
					'temperature' => $temperature,
					'rh' => $rh,
					'remarks' => $remarks,
				];
				
				$readingValue = null;
				LabTestResult::create([
					'lab_test_id'      => $labTest->id,
					'test_key'         => 'cf_chlorinated_water',
					'sub_key'          => 'cf_chlorinated_water',
					'reading_index'    => 0,
					'reading_value'    => $readingValue,
					'unit'             => $unit,
					'standard_code'    => $standardsArr['cf_chlorinated_water'],
					'standard_id'      => $request->input('cf_chlorinated_water_standard_id') ?? null,
					'standard_name'    => $request->input('cf_chlorinated_water_standard_name') ?? null,
					'temperature'      => $temperature,
					'rh'               => $rh,
					'remarks'          => $remarks,
					'meta'             => $meta,   // model must cast meta => array
					'created_by'       => $userId,
					'test_version' 	   => 'Version 6',
				]);
			}

			 
			// 12 - Colour Fastness to Saliva (handles form key cf_sliva or cf_saliva)
			if (! empty($standardsArr['cf_sliva'])) 
			{
				// Decide which standard key is present (tolerate typo)
				$standardCode = $standardsArr['cf_sliva'] ?? $standardsArr['cf_saliva'] ?? null;
				$standardId   = $request->input('cf_sliva_standard_id') ?? $request->input('cf_saliva_standard_id') ?? null;
				$standardName = $request->input('cf_sliva_standard_name') ?? $request->input('cf_saliva_standard_name') ?? null;

				// Top-level meta fields (accept either prefix). Save raw values as-is.
				$unit        = $request->input('cf_sliva_unit') ?? $request->input('cf_saliva_unit') ?? null;
				$temperature = $request->input('cf_sliva_temperature') ?? $request->input('cf_saliva_temperature') ?? null;
				$rh          = $request->input('cf_sliva_rh') ?? $request->input('cf_saliva_rh') ?? null;
				$remarks     = $request->input('cf_sliva_remarks') ?? $request->input('cf_saliva_remarks') ?? null;

				// Colour change (raw values)
				$colour_precise = $request->input('cf_sliva_colour_change_precise') ?? $request->input('cf_saliva_colour_change_precise') ?? null;
				$colour_grey    = $request->input('cf_sliva_colour_change_grey') ?? $request->input('cf_saliva_colour_change_grey') ?? null;

				// Substrates (keep raw values — may be scalar or array)
				$substrates = [
					'acetate' => [
						'precise' => $request->input('cf_sliva_acetate_precise') ?? $request->input('cf_saliva_acetate_precise') ?? null,
						'grey'    => $request->input('cf_sliva_acetate_grey')   ?? $request->input('cf_saliva_acetate_grey')   ?? null,
					],
					'cotton' => [
						'precise' => $request->input('cf_sliva_cotton_precise') ?? $request->input('cf_saliva_cotton_precise') ?? null,
						'grey'    => $request->input('cf_sliva_cotton_grey')    ?? $request->input('cf_saliva_cotton_grey')    ?? null,
					],
					'nylon' => [
						'precise' => $request->input('cf_sliva_nylon_precise') ?? $request->input('cf_saliva_nylon_precise') ?? null,
						'grey'    => $request->input('cf_sliva_nylon_grey')    ?? $request->input('cf_saliva_nylon_grey')    ?? null,
					],
					'polyester' => [
						'precise' => $request->input('cf_sliva_polyester_precise') ?? $request->input('cf_saliva_polyester_precise') ?? null,
						'grey'    => $request->input('cf_sliva_polyester_grey')    ?? $request->input('cf_saliva_polyester_grey')    ?? null,
					],
					'acrylic' => [
						'precise' => $request->input('cf_sliva_acrylic_precise') ?? $request->input('cf_saliva_acrylic_precise') ?? null,
						'grey'    => $request->input('cf_sliva_acrylic_grey')    ?? $request->input('cf_saliva_acrylic_grey')    ?? null,
					],
					'wool' => [
						'precise' => $request->input('cf_sliva_wool_precise') ?? $request->input('cf_saliva_wool_precise') ?? null,
						'grey'    => $request->input('cf_sliva_wool_grey')    ?? $request->input('cf_saliva_wool_grey')    ?? null,
					],
				];

				// Build meta exactly from raw inputs
				$meta = [
					'colour_change' => [
						'precise' => $colour_precise,
						'grey'    => $colour_grey,
					],
					'substrates' => $substrates,
					'unit'       => $unit,
					'temperature'=> $temperature,
					'rh'         => $rh,
					'remarks'    => $remarks,
				];

				// reading_value: keep NULL (summary row)
				$readingValue = null;

				LabTestResult::create([
					'lab_test_id'    => $labTest->id,
					'test_key'       => 'cf_saliva',              // normalized key for reporting
					'sub_key'        => 'cf_saliva',
					'reading_index'  => 0,
					'reading_value'  => $readingValue,
					'unit'           => $unit,
					'standard_code'  => $standardCode,
					'standard_id'    => $standardId,
					'standard_name'  => $standardName,
					// Save raw temperature/rh exactly as received
					'temperature'    => $temperature,
					'rh'             => $rh,
					'remarks'        => $meta['remarks'],
					'meta'           => $meta,    // model should cast meta => array or DB accept JSON
					'created_by'     => $userId,
					'test_version'   => 'Version 6',
				]);
			}
						
			
			// 13 - Spray Test			
			if (! empty($standardsArr['spray_test'])) 
			{
				// Read raw values directly from the request (no helper functions, no casting)
				$rawReadings = $request->input('spray', []); // may be array or empty
				$readings = $rawReadings; // save as-is

				$unit        = $request->input('spray_test_unit');
				$temperature = $request->input('spray_test_temperature');
				$rh          = $request->input('spray_test_rh');
				$remarks     = $request->input('spray_remarks');

				$summary = [
					'avg'        => $request->input('spray_avg'),
					'sd'         => $request->input('spray_sd'),
					'cv_percent' => $request->input('spray_cv_percent'),
				];

				// Meta to store full details (raw)
				$meta = [
					'readings'    => $readings,
					'unit'        => $unit,
					'temperature' => $temperature,
					'rh'          => $rh,
					'water_gain'  => $request->input('spray_water_gain'),
					'remarks'     => $remarks,
					'summary'     => $summary,
				];

				// reading_value: use raw avg value from form (no casting)
				$readingValue = $request->input('spray_avg');

				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'spray_test',
					'sub_key'         => 'spray_test',
					'reading_index'   => 0,                     // summary row convention
					'reading_value'   => $readingValue,
					'unit'            => $unit,
					'standard_code'   => $standardsArr['spray_test'],
					'standard_id'     => $request->input('spray_test_standard_id') ?? null,
					'standard_name'   => $request->input('spray_test_standard_name') ?? null,
					'temperature'     => $temperature,
					'rh'              => $rh,
					'remarks'         => $remarks,
					'meta'            => $meta,                 // model should cast meta => array / DB accept JSON
					'created_by'      => $userId,
					'test_version'    => 'Version 9',
				]);
			}


			// 14 - Resistance to Water Penetration
			if (! empty($standardsArr['water_resistance'])) 
			{
				// helpers
				 
				$readings   = $request->input('water_resistance', []);  
				$unit       = $request->input('water_resistance_unit'); 
				$side       = $request->input('water_resistance_side'); 
				$temperature = $request->input('water_resistance_temperature'); 
				$rh         = $request->input('water_resistance_rh'); 
				$remarks    = $request->input('water_res_remarks'); 
				$summary = [
					'avg'        => $request->input('water_res_avg'),
					'sd'         => $request->input('water_res_sd'),
					'cv_percent' => $request->input('water_res_cv_percent'),
				];

				$meta = [
					'readings'    => $readings,
					'unit'        => $unit,
					'side'        => $side,
					'temperature' => $temperature,
					'rh'          => $rh,
					'remarks'     => $remarks,
					'summary'     => $summary,
				];
				
				$readingValue = is_numeric($summary['avg']) ? (float)$summary['avg'] : null;
				 
				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'water_resistance',
					'sub_key'         => $side ? strtolower($side) : 'water_resistance',  
					'reading_index'   => 0,                        
					'reading_value'   => $readingValue,           
					'unit'            => $unit,
					'standard_code'   => $standardsArr['water_resistance'],
					'standard_id'     => $request->input('water_resistance_standard_id') ?? null,
					'standard_name'   => $request->input('water_resistance_standard_name') ?? null,
					'temperature'     => $temperature,
					'rh'              => $rh,
					'remarks'         => $remarks,
					'meta'            => $meta,                   
					'created_by'      => $userId,
					'test_version' 	  => 'Version 7',
					'pressure_applied'=> '60 cmH2O/min',
					'test_performed_on'=> 'Air Side Contact with Water',
					 
				]);
			}
			  
			// 15 - simple direct save — no filtering, no helpers
			if (! empty($standardsArr['phenolic_yellowing'])) 
			{
				// direct reads from request (no transformation)
				$unit          			= $request->input('phenolic_yellowing_unit');
				$temperature   			= $request->input('phenolic_temperature');
				$rh            			= $request->input('phenolic_rh');
				$side          			= $request->input('phenolic_side') ?? $request->input('cf_side');
				$remarks       			= $request->input('phenolic_remarks');
				 
				$phenolicDeValue 		= $request->input('phenolic_cf_de_value');
				$preciseGrdScale  		= $request->input('phenolic_cf_grade_scale');
				$cfRequirement     		= $request->input('phenolic_cf_requirement');
				
				$phenolic_ts_de_value 				= $request->input('phenolic_ts_de_value');
				$phenolic_ts_grade_scale  			= $request->input('phenolic_ts_grade_scale');
				$phenolic_ts_requirement     		= $request->input('phenolic_ts_requirement');

				// raw meta exactly as came from form (no cleaning)
				$meta = [
					'phenolic_cf_de_value'         	=> $phenolicDeValue,
					'phenolic_cf_grade_scale' 		=> $preciseGrdScale,
					'phenolic_cf_requirement'    	=> $cfRequirement,
					
					'phenolic_ts_de_value'         	=> $phenolic_ts_de_value,
					'phenolic_ts_grade_scale' 		=> $phenolic_ts_grade_scale,
					'phenolic_ts_requirement'    	=> $phenolic_ts_requirement,
					
					'side'          => $side,
					'unit'          => $unit,
					'temperature'   => $temperature,
					'rh'            => $rh,
					'remarks'       => $remarks,
				]; 
				
				// create record (meta stored as array — ensure model casts meta => array/json)
				LabTestResult::create([
					'lab_test_id'    => $labTest->id,
					'test_key'       => 'phenolic_yellowing',
					'sub_key'        => 'phenolic_yellowing',
					'reading_index'  => 0,
					'reading_value'  => $side,                        // raw form value
					'unit'           => $unit,
					'standard_code'  => $standardsArr['phenolic_yellowing'],
					'standard_id'    => $request->input('phenolic_yellowing_standard_id'),
					'standard_name'  => $request->input('phenolic_yellowing_standard_name'),
					'temperature'    => $temperature,
					'rh'             => $rh,
					'remarks'        => $remarks,
					'meta'           => $meta,                                 // raw meta
					'created_by'     => $userId,
					'test_version' 	  => 'Version 5-Oven Perspirometer',
				]);
			}
 
 
			// 16 - Construction
			if (! empty($standardsArr['construction'])) 
			{
				// Read raw values directly (no helper closures, no casting)
				$unit        = $request->input('construction_unit') ?? $request->input('construction_unit');
				$temperature = $request->input('construction_temperature') ?? $request->input('construction_temp');
				$rh          = $request->input('construction_rh');
				$remarks     = $request->input('construction_remarks') ?? $request->input('construction_remark'); 
				 
				$endsPerInch  = $request->input('construction_ends') ?? $request->input('ends_per_inch');
				$picksPerInch = $request->input('construction_picks') ?? $request->input('picks_per_inch');

				$meta = [
					'ends_per_inch'  => $endsPerInch,
					'picks_per_inch' => $picksPerInch,
					'unit'           => $unit,
					'temperature'    => $temperature,
					'rh'             => $rh,
					'remarks'        => $remarks,
				];

				LabTestResult::create([
					'lab_test_id'     => $labTest->id,
					'test_key'        => 'construction',
					'sub_key'         => 'construction',
					'reading_index'   => 0,            
					'reading_value'   => null,         
					'unit'            => $unit,
					'standard_code'   => $standardsArr['construction'],
					'standard_id'     => $request->input('construction_standard_id') ?? null,
					'standard_name'   => $request->input('construction_standard_name') ?? null,
					'temperature'     => $temperature,
					'rh'              => $rh,
					'remarks'         => $remarks,
					'meta'            => $meta,         
					'created_by'      => $userId,
				]);
			}

		
			// 17 - Bow (ASTM D 3882)
			if (! empty($standardsArr['bow'])) 
			{
				// Read raw inputs (use exact keys from your POST)
				$W = [
					$request->input('bowW1'),
					$request->input('bowW2'),
					$request->input('bowW3'),
				];
				$D = [
					$request->input('bowD1'),
					$request->input('bowD2'),
					$request->input('bowD3'),
				];
				$percent = [
					$request->input('bowPercent1'),
					$request->input('bowPercent2'),
					$request->input('bowPercent3'),
				];

				// other raw fields (no normalization, no numeric casting)
				$unit        = $request->input('bow_unit') ?? $request->input('bowUnit') ?? '%';
				$temperature = $request->input('bow_temperature');
				$rh          = $request->input('bow_rh');
				$remarks     = $request->input('bow_remarks') ?? $request->input('bow_remark');

				// Use raw average from the form if provided; do not compute or cast
				$average = $request->input('bowAverage');

				$meta = [
					'W'         => $W,
					'D'         => $D,
					'percent'   => $percent,
					'average'   => $average,
					'unit'      => $unit,
					'temperature'=> $temperature,
					'rh'        => $rh,
					'remarks'   => $remarks,
				];

				LabTestResult::create([
					'lab_test_id'      => $labTest->id,
					'test_key'         => 'bow',
					'sub_key'          => 'bow',
					'reading_index'    => 0,                    // summary row
					'reading_value'    => $average,             // raw average value from form (if any)
					'unit'             => $unit,
					'standard_code'    => $standardsArr['bow'],
					'standard_id'      => $request->input('bow_standard_id') ?? null,
					'standard_name'    => $request->input('bow_standard_name') ?? null,
					'temperature'      => $temperature,
					'rh'               => $rh,
					'remarks'          => $remarks,
					'meta'             => $meta,                // model should cast meta => array / DB accept JSON
					'created_by'       => $userId,
				]);
			}

			
			// 18 - Skew (ASTM D 3882)
			if (! empty($standardsArr['skew'])) 
			{ 
				// raw inputs (saved as-is)
				$W = [
					$request->input('skewW1'),
					$request->input('skewW2'),
					$request->input('skewW3'),
				];
				$D = [
					$request->input('skewD1'),
					$request->input('skewD2'),
					$request->input('skewD3'),
				];
				$percent = [
					$request->input('skewPercent1'),
					$request->input('skewPercent2'),
					$request->input('skewPercent3'),
				];

				// use raw average from the form (do not compute or cast)
				$average = $request->input('skewAverage');

				// other raw fields
				$unit        = $request->input('skew_unit') ?? '%';
				$temperature = $request->input('skew_temperature');
				$rh          = $request->input('skew_rh');
				$remarks     = $request->input('skew_remarks');

				$meta = [
					'W'          => $W,
					'D'          => $D,
					'percent'    => $percent,
					'average'    => $average,
					'unit'       => $unit,
					'temperature'=> $temperature,
					'rh'         => $rh,
					'remarks'    => $remarks,
				];

				LabTestResult::create([
					'lab_test_id'    => $labTest->id,
					'test_key'       => 'skew',
					'sub_key'        => 'skew',
					'reading_index'  => 0,            // summary row
					'reading_value'  => $average,     // raw average value from form (if any)
					'unit'           => $unit,
					'standard_code'  => $standardsArr['skew'],
					'standard_id'    => $request->input('skew_standard_id') ?? null,
					'standard_name'  => $request->input('skew_standard_name') ?? null,
					'temperature'    => $temperature,
					'rh'             => $rh,
					'remarks'        => $remarks,
					'meta'           => $meta,        // ensure model casts meta => array or DB accepts JSON
					'created_by'     => $userId,
				]);
			}
			
			// 19 - Thickness of Textile
			
			if (! empty($standardsArr['thickness_of_textile'])) 
			{
				$meta = [
					'readings'    => $request->input('thickness_of_textile_width', []), // array of readings
					'unit'        => $request->input('thickness_of_textile_unit'),
					'temperature' => $request->input('thickness_of_textile_temperature'),
					'rh'          => $request->input('thickness_of_textile_rh'),
					'remarks'     => $request->input('thickness_remarks'),
					'summary'     => [
						'avg'        => $request->input('thickness_of_textile_width_avg'),
						'sd'         => $request->input('thickness_of_textile_width_sd'),
						'cv_percent' => $request->input('thickness_of_textile_width_cv_percent'),
					],
				];

				LabTestResult::create([
					'lab_test_id'    => $labTest->id,
					'test_key'       => 'thickness_of_textile',
					'sub_key'        => 'width',
					'reading_index'  => 1,
					'reading_value'  => json_encode($meta['readings'], JSON_UNESCAPED_UNICODE), 
					'unit'           => $meta['unit'] ?? 'mm',
					'standard_code'  => $standardsArr['thickness_of_textile'],
					'standard_id'    => $request->input('thickness_of_textile_standard_id') ?? null,
					'standard_name'  => $request->input('thickness_of_textile_standard_name') ?? null,
					'temperature'    => is_numeric($meta['temperature']) ? (float) $meta['temperature'] : null,
					'rh'             => is_numeric($meta['rh']) ? (float) $meta['rh'] : null,
					'remarks'        => $meta['remarks'],
					'meta'           => $meta,
					'created_by'     => $userId,
				]);
			}

			
			$labRequestId 		= $request->lab_request_id;
			
			$labReq = LabTestRequest::findOrFail($labRequestId);			 
			$labReq->lab_req_status 		= 'ResultSubmitted';
			$labReq->result_submitted_at 	= now();
			$labReq->lab_test_id 			= $labTest->id;
			$labReq->updated_by 			= Auth::id();
			$labReq->updated_at 			= now();
			$labReq->save();
			
			DB::commit();
 
			return redirect()->route('show-lab-request')->with('message','Lab test saved successfully.');
		} catch (\Throwable $e) {
			DB::rollBack();
			\Log::error('LabTest store error: ' . $e->getMessage());
			 
			return back()->with('message', 'Unable to save lab test: '.$e->getMessage());
		}
	}
 
	
	
	public function check_lab_report($id)
	{ 
	
		if (empty(CommonController::checkPageViewPermission())) {
			return redirect()->back()->with([
				'message'      => 'Access denied! You do not have permission to access this page.',
				'messageClass' => 'errorClass'
			]);
		}

		$id 	 = dec($id);
		$labTest = LabTest::with('Item')->with('LabTestResult')->findOrFail($id);
		
		$labRequestId 				= $labTest->lab_request_id ?? null;
		$labRequestRequirementId 	= $labTest->lab_request_requirement_id ?? null;
		
		$labRequest 	= $labRequestId ? LabTestRequest::find($labRequestId) : null;
		$requirement 	= $labRequestRequirementId
			? LabRequirement::with('labTestRequest')->find($labRequestRequirementId)
			: null;
	 
		$resultsCollection 	= $labTest->results ?? collect();		
		$colorName 			= $requirement->color_classification;
		$itemId 			= $labTest->item_id;	
		
		// echo "<pre>"; print_r($requirement); exit;			 
		$viewData = [
			'labTest'           => $labTest,
			'labRequest'        => $labRequest,
			'requirement'       => $requirement,
			'resultsCollection' => $resultsCollection,
		];

		return view('html.labrequest.check_lab_report', $viewData);			
	}


	
	
	public function downloadReport(Request $request, $id)
	{
		try {
			$labTest = LabTest::with('results')->findOrFail($id);

			$labRequestId = $labTest->lab_request_id ?? null;
			$labRequestRequirementId = $labTest->lab_request_requirement_id ?? null;
			$labRequest = LabTestRequest::find($labRequestId);
			$requirement = LabRequirement::with('labTestRequest')->findOrFail($labRequestRequirementId);

			$resultsCollection = $labTest->results ?? collect();

			 // standards keyed by test_key
			$standards = LabTestStandard::orderBy('id')->get()->keyBy('test_key');

			 // Ordered tests (same as you had)
			$orderedTests = [
				'mass_per_unit_area'    => 'Determination of Mass per Unit Area (GSM)',
				'useful_width'          => 'Measurement of Useful Width of Components',
				'ph_aqueous_extract'    => 'Determination of pH in Aqueous Extract',
				'abrasion_resistance'   => 'Abrasion Resistance (Martindale)',
				'max_force_elongation'  => 'Maximum Force and Elongation',
				'tear_force'            => 'Tear Force',
				'cf_rubbing'            => 'Colour Fastness — Rubbing',
				'cf_water'              => 'Colour Fastness to Water',
				'cf_see_water'          => 'Colour Fastness to Sea Water',
				'cf_perspiration'       => 'Colour Fastness to Perspiration',
				'cf_chlorinated_water'  => 'Colour Fastness to Chlorinated Water',
				'cf_sliva'              => 'Colour Fastness to Sliva',
				'spray_test'            => 'Spray Test',
				'water_resistance'      => 'Resistance to Water Penetration',
				'phenolic_yellowing'    => 'Phenolic Yellowing',
				'construction'          => 'Construction',
				'bow'                   => 'Bow-ASTM D 3882 Bow In Woven And Knitted Fabrics',
				'skew'                  => 'ASTM D 3882 Skew In Woven And Knitted Fabrics',
				'thickness_of_textile'  => 'Thickness Of Textile',
			];

			 // special mapping: ordered key => array of actual parameter keys your saver creates
			$specialMappings = [
				'max_force_elongation' => ['warp_force', 'weft_force', 'warp_elongation', 'weft_elongation'],
				'tear_force'           => ['tear_warp', 'tear_weft'],
				 // colour fastness keys may be saved with prefixes like cfw_, cfsw_, cf_perspiration_ etc.
				'cf_water'             => ['cfw', 'cf_water', 'cf_water_'],
				'cf_see_water'         => ['cfsw', 'cf_see_water', 'cf_sea_water'],
				'cf_perspiration'      => ['cf_perspiration'],
				'cf_chlorinated_water' => ['cf_chlorinated_water'],
				'cf_sliva'             => ['cf_sliva'],
				'cf_rubbing'           => ['cf_rubbing'],
				 // bow/skew percent arrays often saved as bowPercent / skewPercent
				'bow'                  => ['bowPercent', 'bow'],
				'skew'                 => ['skewPercent', 'skew'],
				'thickness_of_textile' => ['thickness_of_textile_width', 'thickness_of_textile'],
				'construction'         => ['construction_ends', 'construction_picks', 'construction'],
			];

			 // helper: decode readings field safely (returns array or associative array)
			$decodeReadings = function ($res) {
				$r = $res->readings ?? null;
				if ($r === null) return [];
				if (is_array($r)) return $r;
				$dec = @json_decode($r, true);
				if (json_last_error() === JSON_ERROR_NONE && $dec !== null) return $dec;
				return [$r];
			};

			 // Build a lookup: parameter => collection of result models (handles duplicates)
			$resultsGrouped = $resultsCollection->groupBy('parameter');

			 // for prefix matching also create an indexed collection by parameter string
			$resultsByParam = [];
			foreach ($resultsCollection as $r) {
				$resultsByParam[$r->parameter] = $r;
			}

			$tests = [];
			$index = 1;
			foreach ($orderedTests as $key => $title) {
				$item = new \stdClass();
				$item->parameter = $key;
				$item->title = $title;
				$item->index = $index++;

				$matched = collect();

				 // 1) exact grouped matches
				if (isset($resultsGrouped[$key])) {
					$matched = $matched->merge($resultsGrouped[$key]);
				}

				 // 2) special mapping names
				if ($matched->isEmpty() && isset($specialMappings[$key])) {
					foreach ($specialMappings[$key] as $mapKey) {
						 // if mapKey exactly exists as parameter
						if (isset($resultsGrouped[$mapKey])) {
							$matched = $matched->merge($resultsGrouped[$mapKey]);
						} else {
							 // prefix style: any parameter that starts with mapKey
							$found = $resultsCollection->filter(function ($r) use ($mapKey) {
								return Str::startsWith($r->parameter, $mapKey);
							});
							if ($found->isNotEmpty()) $matched = $matched->merge($found);
						}
					}
				}

				 // 3) prefix match (e.g., cfw_* for cf_water)
				if ($matched->isEmpty()) {
					$found = $resultsCollection->filter(function ($r) use ($key) {
						return Str::startsWith($r->parameter, $key . '_') || Str::startsWith($r->parameter, $key);
					});
					if ($found->isNotEmpty()) $matched = $matched->merge($found);
				}

				 // 4) fallback: any result whose parameter contains the key
				if ($matched->isEmpty()) {
					$found = $resultsCollection->filter(function ($r) use ($key) {
						return stripos($r->parameter, $key) !== false;
					});
					if ($found->isNotEmpty()) $matched = $matched->merge($found);
				}

				 // Now prepare item fields from matched results
				if ($matched->isNotEmpty()) {
					$allReadings = [];
					$units = [];
					$temperatures = [];
					$rhs = [];
					$sds = [];
					$cvs = [];
					$remarks = [];
					$extras = [];

					foreach ($matched as $res) {
						$decoded = $decodeReadings($res);
						 // if decoded is associative (precise/grey), keep associative under the parameter key
						$isAssoc = is_array($decoded) && array_keys($decoded) !== range(0, count($decoded) - 1);

						if ($isAssoc) {
							 // store associative under param name to preserve structure
							$allReadings[$res->parameter] = $decoded;
						} else {
							 // append numeric/ordered readings into a flat list
							foreach ($decoded as $val) $allReadings[] = $val;
						}

						if (!empty($res->unit)) $units[] = $res->unit;
						if (!empty($res->temperature)) $temperatures[] = $res->temperature;
						if (!empty($res->rh)) $rhs[] = $res->rh;
						if (!empty($res->sd)) $sds[] = $res->sd;
						if (!empty($res->cv_percent)) $cvs[] = $res->cv_percent;
						if (!empty($res->remarks)) $remarks[] = $res->remarks;

						 // decode extras if present (keep as array if JSON)
						$extraDecoded = null;
						if ($res->extra !== null) {
							if (is_array($res->extra)) $extraDecoded = $res->extra;
							else {
								$dec = @json_decode($res->extra, true);
								$extraDecoded = (json_last_error() === JSON_ERROR_NONE) ? $dec : $res->extra;
							}
						}
						if ($extraDecoded) $extras[] = $extraDecoded;
					}

					 // compact unit (prefer first non-empty, else null)
					$unit = count($units) ? $units[0] : null;
					$temperature = count($temperatures) ? implode(', ', array_unique($temperatures)) : null;
					$rh = count($rhs) ? implode(', ', array_unique($rhs)) : null;
					$sd = count($sds) ? (count($sds) === 1 ? $sds[0] : null) : null; // if many SDs leave null or decide merging logic
					$cv = count($cvs) ? (count($cvs) === 1 ? $cvs[0] : null) : null;
					$remarksAll = count($remarks) ? implode(' | ', array_unique($remarks)) : null;

					$item->readings = $allReadings;
					$item->average = $this->pickNumericAverage($matched);  // helper below - picks average if present
					$item->unit = $unit;
					$item->temperature = $temperature;
					$item->rh = $rh;
					$item->sd = $sd;
					$item->cv_percent = $cv;
					$item->remarks = $remarksAll;
					$item->extra = count($extras) ? $extras : null;

					 // result: prefer numeric average; else join readable readings
					if (is_numeric($item->average) && $item->average !== null) {
						$item->result = $item->average;
					} else {
						 // if associative readings (coloured maps), json_encode for view
						if (is_array($allReadings) && $allReadings !== []) {
							$assoc = array_keys($allReadings) !== range(0, count($allReadings) - 1);
							if ($assoc) {
								$item->result = json_encode($allReadings);
							} else {
								$item->result = implode(', ', array_map(function ($v) {
									return (string)$v;
								}, $allReadings));
							}
						} else {
							$item->result = '-';
						}
					}
				} else {
					 // no matched rows
					$item->readings    = [];
					$item->average     = null;
					$item->unit        = null;
					$item->temperature = null;
					$item->rh          = null;
					$item->sd          = null;
					$item->cv_percent  = null;
					$item->remarks     = null;
					$item->extra       = null;
					$item->result      = '-';
				}

				 // attach standard metadata (may be null)
				$item->standard = $standards->get($key);

				$tests[] = $item;
			}

			 // Build report meta
			$issueDateRaw = $labTest->report_date ?? null;
			if ($issueDateRaw) {
				try {
					$issueDate = \Carbon\Carbon::parse($issueDateRaw)->format('d/m/Y');
				} catch (\Exception $e) {
					$issueDate = $issueDateRaw;
				}
			} else {
				$issueDate = now()->format('d/m/Y');
			}

			$report = [
				'customer_name' => $labTest->customer_name ?? 'AJY TECH INDIA PRIVATE LIMITED',
				'report_no'     => $labTest->report_no ?? ('AJY-'.$labTest->id),
				'issue_date'    => $issueDate,
				'date_in'       => $labRequest->created_at ?? null,
				'date_out'      => $labTest->created_at ?? null,
				'lead_time'     => ($labRequest && $labTest && $labRequest->created_at && $labTest->created_at)
									? \Carbon\Carbon::parse($labRequest->created_at)->diffInDays(\Carbon\Carbon::parse($labTest->created_at))
									: null,
				'status'        => $labTest->status ?? null,
				'sample_desc'   => $labTest->sample_description ?? $labTest->sample_desc ?? null,
				'supplier'      => 'AJY TECH INDIA PRIVATE LIMITED',
				'lot_number'    => $labTest->lot_number ?? null,
				'colour'        => $labTest->colour ?? null,
				'gsm'           => $labTest->gsm ?? null,
				'width'         => $labTest->width ?? null,
			];

			 // return view or pdf
			$isDownload = $request->query('download') == '1' || $request->get('download') == '1';

			$viewData = [
				'labTest'   => $labTest,
				'labRequest'=> $labRequest,
				'report'    => $report,
				'requirement'=> $requirement,
				'tests'     => $tests,
				'standards' => $standards,
				'results'   => $resultsCollection,
				'for_pdf'   => $isDownload,
			];

			if ($isDownload) {
				$pdf = Pdf::setOptions(['isRemoteEnabled' => true])->loadView('pdf.lab-test-report', $viewData)->setPaper('a4', 'portrait');
				$filename = 'lab_test_report_'.$labTest->id.'.pdf';
				return $pdf->download($filename);
			}

			return view('pdf.lab-test-report', $viewData);

		} catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
			return back()->with('message', 'Lab test not found.');
		} catch (\Exception $e) {
			\Log::error('Error generating report: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
			return back()->with('message', 'Error generating report: '.$e->getMessage());
		}
	}

	/**
	 * Helper method in the same controller to pick a numeric average from matched results.
	 * Returns first numeric average found, else null.
	 */
	protected function pickNumericAverage($matchedCollection)
	{
		foreach ($matchedCollection as $m) {
			if (isset($m->average) && is_numeric($m->average)) return $m->average;
		}
		return null;
	}

	 
	 
	public function showLabRequest(Request $request)
	{
		
		if (empty(CommonController::checkPageViewPermission())) 
		{
			return redirect()->back()->with([
				'message' => 'Access denied! You do not have permission to access this page.', 
				'messageClass' => 'errorClass'
			]);
		}		
		
		// echo "ssfssf"; exit;
		
		try {			 
			$workOrderId = $request->get('work_order_id');
			$query = LabTestRequest::where('status', 1); 
			if (!empty($workOrderId)) {
				$query->where('work_order_id', $workOrderId);
			} 
			$dataLR    = $query->orderBy('id', 'desc')->paginate(10);
			$standards = LabTestStandard::select('id', 'test_key', 'test_name', 'ds_code', 'iso_code', 'astm_code')
						->orderBy('id')
						->get()
						->keyBy('test_key');
			return view('html.labrequest.show-labrequest', compact('dataLR','standards'));
		} catch (\Exception $e) {
			return back()->with('message', 'Error fetching lab requests: '.$e->getMessage());
		}
	}
 
	public function sendLabRequest(Request $request)
	{
		if (empty(CommonController::checkPageViewPermission())) {
			return redirect()->back()->with([
				'message'      => 'Access denied! You do not have permission to access this page.',
				'messageClass' => 'errorClass'
			]);
		}
		
		 // 1. Validate Request
		$request->validate([
			'id' => 'required|exists:work_process_requirements,id',
			'remarks' => 'nullable|string|max:500',
			'meter' => 'nullable|numeric|min:0'
		]);


		try {
			 // 2. Get Auth User
			$userId       = Auth::id();
			$user         = User::find($userId);
			$individualId = $user->individual_id ?? null;

			 // 3. Fetch Work Process Requirement
			$record = WorkProcessRequirement::find($request->id);
			 // echo "<pre>"; print_r($record); exit;
			
			

			 // Prevent duplicate requests
			if ($record->lab_req_status === 'Requested') {
				return response()->json([
					'success' => false,
					'message' => "A lab test request for Lot: {$record->req_lot_no} is already pending.",
					'status'  => $record->lab_req_status,
				], 409);
			}

			 
			 $record->lab_req_status = 'Requested';
			$record->save();

		 
			$workOrder = WorkOrder::select('work_order_id', 'item_name', 'status')
				->where('work_order_id', $record->work_order_id)
				->where('status', '1')
				->with(['WorkOrderItem' => function ($query) {
					$query->select('woi_id', 'work_order_id', 'dyeing_color', 'sale_order_id', 'sale_order_item_id', 'customer_id');
				}])
				->first();

	
			if (!$workOrder) {
				return response()->json([
					'success' => false,
					'message' => 'Work Order not found.',
				], 404);
			}

			$itemName    		= $workOrder->item_name;  
			$dyeingColor 		= optional($workOrder->WorkOrderItem->first())->dyeing_color ?? 'N/A';
			$saleOrderId 		= optional($workOrder->WorkOrderItem->first())->sale_order_id ?? '0';
			$saleOrderItemId 	= optional($workOrder->WorkOrderItem->first())->sale_order_item_id ?? '0';
			$customerId 		= optional($workOrder->WorkOrderItem->first())->customer_id ?? '0';
			
			
			$cusData 			= Individual::where('id', '=', $customerId)->where('status', '=', '1')->where('type', '=', 'customers')->value('name');
			
			 // echo "<pre>"; print_r($cusData); exit;
			 
			$cus_name 			= $cusData ?? 'Customer';				
			$saleOrderNumber 	= SaleOrder::where('sale_order_id', $saleOrderId)->value('sale_order_number');

			 // 5. Save Lab Test Request
			$labTest = new LabTestRequest();
			$labTest->work_order_id   		= $record->work_order_id;
			$labTest->work_pro_req_id 		= $record->id;
			$labTest->sale_order_id 		= $saleOrderId;
			$labTest->sale_order_item_id 	= $saleOrderItemId;
			$labTest->customer_id 			= $customerId; 
			$labTest->cus_name 				= $cus_name; 
			$labTest->po_number 			= $saleOrderNumber; 
			$labTest->req_lot_number  		= $record->req_lot_no;
			$labTest->remarks 				= $request->remarks ?? null;
			$labTest->meter   				= $request->meter;
			$labTest->item_id         		= $record->item_id;
			$labTest->item_name       		= $itemName;
			$labTest->colour          		= $dyeingColor;
			$labTest->created_by      		= $individualId;
			$labTest->lab_req_status  		= 'Requested';
			$labTest->status          		= 1;
			$labTest->created_at      		= now();
			$labTest->save();

			 // 6. Response
			return response()->json([
				'success' => true,
				'message' => "Lab test request has been successfully sent for Lot: {$record->req_lot_no}.",
				'status'  => $record->lab_req_status,
			], 200);

		} catch (\Exception $e) {
			 // Handle any unexpected errors
			return response()->json([
				'success' => false,
				'message' => 'An error occurred while processing the lab request.',
				'error'   => $e->getMessage(),
			], 500);
		}
	}

	 // Approve lab test request
	public function approveLabRequest($id)
	{
		try {
			$labRequest = LabTestRequest::findOrFail($id);

			 // update status
			$labRequest->lab_req_status = 'Approved';
			$labRequest->last_action_by = Auth::id();
			$labRequest->updated_by     = Auth::id();
			$labRequest->updated_at     = now();
			$labRequest->save();

			 // also update WorkProcessRequirement
			if ($labRequest->work_pro_req_id) 
			{
				WorkProcessRequirement::where('id', $labRequest->work_pro_req_id)->update(['lab_req_status' => 'Approved']);
			}

			return redirect()->back()->with('success', "Lab test approved for Lot {$labRequest->req_lot_number}.");
		} catch (\Exception $e) {
			return redirect()->back()->with('error', 'Error approving lab request: '.$e->getMessage());
		}
	}

	 // Reject lab test request
	public function rejectLabRequest($id)
	{
		try {
			$labRequest = LabTestRequest::findOrFail($id);

			$labRequest->lab_req_status = 'Rejected';
			$labRequest->last_action_by = Auth::id();
			$labRequest->updated_by     = Auth::id();
			$labRequest->updated_at     = now();
			$labRequest->save();

			 // also update WorkProcessRequirement
			if ($labRequest->work_pro_req_id) 
			{
				WorkProcessRequirement::where('id', $labRequest->work_pro_req_id)->update(['lab_req_status' => 'Rejected']);
			}

			return redirect()->back()->with('error', "Lab test rejected for Lot {$labRequest->req_lot_number}.");
		} catch (\Exception $e) {
			return redirect()->back()->with('error', 'Error rejecting lab request: '.$e->getMessage());
		}
	}
	
	public function submitRequirementForm(Request $request, $id)
	{
		$v = Validator::make($request->all(), [
			'tests' => 'required|array|min:1',
			'tests.*' => 'string',
			'form_remarks' => 'nullable|string|max:2000'
		]);
		if ($v->fails()) {
			return response()->json(['success' => false, 'message' => $v->errors()->first()], 422);
		}

		try {
			$labReq = LabTestRequest::findOrFail($id);

			 // ensure accepted
			if ($labReq->lab_req_status !== 'Accepted') {
				return response()->json(['success' => false, 'message' => 'Form can be submitted only after acceptance.'], 422);
			}

			 // create lab_tests row (ensure lab_tests has lab_test_request_id, form_data columns)
			$labTest = LabTest::create([
				'work_order_id' => $labReq->work_order_id,
				'tested_by'     => Auth::user()->name ?? Auth::id(),
				'report_date'   => null,
				'form_data'     => json_encode([
					'tests' => $request->input('tests'),
					'remarks' => $request->input('form_remarks'),
					'created_by' => Auth::id()
				]),
				'lab_test_request_id' => $labReq->id,
				'created_by' => Auth::id(),
				'created_at' => now(),
				'updated_at' => now(),
			]);

			 // update request
			$labReq->lab_test_id = $labTest->id;
			$labReq->lab_req_status = 'FormSubmitted';
			$labReq->form_submitted_at = now();
			$labReq->last_action_by = Auth::id();
			$labReq->updated_by = Auth::id();
			$labReq->updated_at = now();
			$labReq->save();

			 // prepare result url
			$resultUrl = url('/lab-test/' . $labTest->id . '/result');

			return response()->json([
				'success' => true,
				'message' => 'Requirement form saved.',
				'lab_test_id' => $labTest->id,
				'result_submit_url' => $resultUrl
			]);
		} catch (\Exception $e) {
			\Log::error('submitRequirementForm error: '.$e->getMessage());
			return response()->json(['success' => false, 'message' => 'Server error.'], 500);
		}
	}
	
	public function acceptRequest($id)
	{
		try {
			 // Find the request
			$labRequest = LabTestRequest::findOrFail($id);

			 // Update status
			$labRequest->lab_req_status = 'Accepted';
			$labRequest->save();

			return redirect()->back()->with('success', 'Lab request accepted successfully.');
		} catch (\Exception $e) {
			return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
		}
	}
	 
    public function showResultForm_old($id)
    { 
        $requirement = LabRequirement::with('labTestRequest')->findOrFail($id); 
        $labRequest  = LabTestRequest::find($requirement->lab_request_id);
		$standards   = DB::table('lab_test_standards')
						->select('id','test_key','test_name','ds_code','iso_code')
						->orderBy('id')
						->get()
						->keyBy('test_key'); 

        return view('html.labrequest.add-lab-test-result', compact('requirement', 'labRequest', 'standards'));
    }
	
	public function showResultForm($id)
	{
		// $requirement = LabRequirement::with('labTestRequest')->findOrFail($id);
		$requirement = LabRequirement::with('labTestRequest','Item')->findOrFail($id);
		
		$itemId 	 = $requirement->item_id;
	       // echo "<pre>"; print_r($requirement); exit;
		
	    $dataLCF 	= LabColourFastness::where('item_id', '=', $itemId)->where('item_id', '=', $itemId)->where('status', '=', '1')->get();
		 // echo "<pre>"; print_r($dataLCF); exit;
		
		 
		
		$cfRubbing   			= LabColourFastness::where('item_id', '=', $itemId)->where('lab_test_standard_id', '=', '7')->where('status', '=', '1')->first();
		$cfWater   				= LabColourFastness::where('item_id', '=', $itemId)->where('lab_test_standard_id', '=', '8')->where('status', '=', '1')->first();
		$cfSeeWater   			= LabColourFastness::where('item_id', '=', $itemId)->where('lab_test_standard_id', '=', '9')->where('status', '=', '1')->first();
		$cfPerspiration   		= LabColourFastness::where('item_id', '=', $itemId)->where('lab_test_standard_id', '=', '10')->where('status', '=', '1')->first();
		$cfChlorinatedWater   	= LabColourFastness::where('item_id', '=', $itemId)->where('lab_test_standard_id', '=', '11')->where('status', '=', '1')->first();
		$cfSliva   				= LabColourFastness::where('item_id', '=', $itemId)->where('lab_test_standard_id', '=', '12')->where('status', '=', '1')->first(); 
		
		
		$labRequest  = LabTestRequest::find($requirement->lab_request_id);

		 // load standards once
		$all = DB::table('lab_test_standards')
					->select('id','test_key','test_name','ds_code','iso_code','astm_code')
					->orderBy('id')
					->get();

		 // two lookup maps for convenience
		$standardsByKey = $all->keyBy('test_key');  
		$standardsByKey['mass_per_unit_area']->id;
		$standardsById  = $all->keyBy('id');        
		$standardsById[2]->test_key;

		 // normalize tests json -> array
		$tests = is_array($requirement->tests)
			? $requirement->tests
			: (json_decode($requirement->tests, true) ?: []);

		 // pass both to view (keeps old variable name $standards for backward compat)
		$standards = $standardsByKey;

		return view('html.labrequest.add-lab-test-result', compact(
			'requirement', 'labRequest', 'standards', 'standardsById', 'standardsByKey', 'tests', 'cfRubbing', 'cfWater', 'cfSeeWater', 'cfPerspiration', 'cfChlorinatedWater', 'cfSliva'
		));
	}
 
    public function submitResult(Request $request, $id)
    {
         // Basic validation — adapt to your exact result fields later
        $request->validate([
             // example: if you add per-test result fields like result[123] => string/number
             'result' => 'required|array',
             'result.*' => 'required|string',
            'remarks' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
			
            $requirement = LabRequirement::findOrFail($id);			
            $labReq 	 = LabTestRequest::findOrFail($requirement->lab_request_id);
            
            $labReq->lab_req_status 		= 'ResultSubmitted';
            $labReq->result_submitted_at 	= now();
            $labReq->last_action_by 		= Auth::id() ?? $labReq->last_action_by; 
            if ($request->has('remarks')) 
			{
                $labReq->remarks = trim($request->remarks);
            }
            $labReq->save();

            DB::commit();
            
            if ($request->wantsJson() || $request->ajax()) 
			{
                return response()->json([
                    'success' => true,
                    'message' => 'Results submitted successfully.',
                ]);
            }
            
            return redirect()->route('lab-test.result', ['id' => $id])->with('message', 'Results submitted successfully.');

        } catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('LabTest submit error: '.$e->getMessage());
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Server error'], 500);
            }
            return back()->withErrors('Server error while submitting results.');
        }
    }

	public function changeStatus(Request $request)
    {
		if (empty(CommonController::checkPageViewPermission())) {
			return redirect()->back()->with([
				'message'      => 'Access denied! You do not have permission to access this page.',
				'messageClass' => 'errorClass'
			]);
		}

        // validate
        $validator = Validator::make($request->all(), [
            'id' => 'required|integer|exists:lab_test_results,id',
            'status' => 'required|string|in:Pass,Fail',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            $ltr = LabTestResult::findOrFail($request->input('id'));
            $ltr->result_status = $request->input('status');
            $ltr->save();

            return response()->json([
                'success' => true,
                'message' => 'Result updated to ' . $ltr->result_status,
                'data' => [
                    'id' => $ltr->id,
                    'status' => $ltr->result_status
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating LabTestResult: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }








    
 
}
