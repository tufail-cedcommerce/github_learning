<!--
 /**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the End User License Agreement(EULA)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://cedcommerce.com/license-agreement.txt
 *
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE(http://cedcommerce.com/)
 * @license   http://cedcommerce.com/license-agreement.txt
 * @category  Ced
 * @package   CedElkjopnordic
 */
 -->

<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="col-lg-6">
				<div class="panel" id="formAddPaymentPanel">
					<div class="panel-heading">
						<i class="icon-money"></i>
						Order Info
					</div>
					<div class="table-responsive">
						<table class="table">
							<tbody>
							{foreach $order_info as $index => $value}
								<tr>
									<td>
										{$index|escape:'htmlall':'UTF-8'}
									</td>
									<td>{if !is_array($value)}
											{$value|escape:'htmlall':'UTF-8'}
										{/if}
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>

			<div class="col-lg-6">
				<div class="panel" id="formAddPaymentPanel">
					<div class="panel-heading">
						<i class="icon-money"></i>
						Shipping Info
					</div>
					<div class="table-responsive">
						<table class="table">
							<tbody>
							{foreach $shippingInfo as $index => $value}
								<tr>
									<td>
										{$index|escape:'htmlall':'UTF-8'}
									</td>
									<td>
										{if !is_array($value)}
											{$value|escape:'htmlall':'UTF-8'}
										{/if}
									</td>
								</tr>
							{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="panel" id="formAddPaymentPanel">
				<div class="panel-heading">
					<i class="icon-money"></i>
					Products Info
				</div>
				<div class="table-responsive">
					<table class="table">
						<thead>
						<th>order_line_id</th>
						<th>order_line_state</th>
						<th>price</th>
						<th>product_sku</th>
						<th>product_title</th>
						<th>quantity</th>
						<th colspan="3">Action</th>
						</thead>
						<tbody>
						{foreach $orderLines as $index => $value}
							<tr>
								{foreach $value as $ind => $val}
									{if in_array(trim($ind), array('order_line_id','order_line_state','price_unit','product_sku','product_title','quantity'))}
										<td>
											{$val|escape:'htmlall':'UTF-8'}
										</td>
									{/if}
								{/foreach}
								{if isset($order_info['order_state']) && ($order_info['order_state']=='WAITING_ACCEPTANCE')}
									<td>
										<button class="btn btn-primary" onclick="acceptOrder('{$value['order_line_id']|escape:'htmlall':'UTF-8'}')">Accept</button>
									</td>
									<td>
										<button class="btn btn-danger" onclick="cancelOrder('{$value['order_line_id']|escape:'htmlall':'UTF-8'}')">Cancel</button>
									</td>
								{else}
									<td></td>
									<td></td>
								{/if}
							</tr>
						{/foreach}
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
<div class="row">
	<div class="col-lg-12">
		<div class="row">
			<div class="panel" id="formAddPaymentPanel">
				<div class="panel-heading">
					<i class="icon-money"></i>
					Ship Whole Order
				</div>
				<div class="table-responsive">
					<table class="table">
						<tbody>
						<tr>
							<td>Shipping Carrier</td>
							<td>
								<select name="carrier_code" id="whole-carrier_code">
									<option>Select carrier</option>
									{foreach $carriers as $carrier}
									        {if $carrier['code']=='POSTNORD'}
										<option selected="selected" value="{$carrier['code']|escape:'htmlall':'UTF-8'}">{$carrier['label']|escape:'htmlall':'UTF-8'}</option> {else} <option value="{$carrier['code']|escape:'htmlall':'UTF-8'}">{$carrier['label']|escape:'htmlall':'UTF-8'}</option>{/if}
									{/foreach}
								</select>
							</td>
						</tr>
						<tr>
							<td>Tracking Number</td>
							<td><input type="text" name="whole-tracking_number" id="whole-tracking_number"></td>
						</tr>
						{*<tr>*}
						{*<td>carrier_url</td>*}
						{*<td><input type="text" name="carrier_url" id="whole-carrier_url"></td>*}
						{*</tr>*}
						{*<tr>*}
						{*<td>carrier_name</td>*}
						{*<td><input type="text" name="carrier_name" id="whole-carrier_name" /></td>*}
						{*</tr>*}
						{*<tr>*}
						{*<td>carrier_code</td>*}
						{*<td><input type="text" name="carrier_code" id="whole-carrier_code" /></td>*}
						{*</tr>*}
						</tbody>
						<tfoot>
						<tr>
							<td colspan="2">
								<button onclick="shipCompleteOrder('{$order_info['order_id']|escape:'htmlall':'UTF-8'}',document.getElementById('whole-carrier_code').value, document.getElementById('whole-tracking_number').value)" class="btn btn-primary">Add Tracking For Order</button>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<button type="button" id="shipment_button" onclick="shipOrder()" class="btn btn-primary">Ship Order</button>
							</td>
						</tr>
						</tfoot>
					</table>
					<div id="fruugo_shipwhole_response">

					</div>
				</div>
			</div>
		</div>
	</div>
</div>

{if isset($shipping_info['shipment'])}
	<div class="row">
	<div class="col-lg-12">
	<div class="row">
	<div class="panel" id="formAddPaymentPanel">
	<div class="panel-heading">
		<i class="icon-money"></i>
		Shipment Details
	</div>
	<div class="table-responsive">
	{foreach $shipping_info['shipment'] as $shipment}
		<table class="table">
			<tbody>
			{foreach $shipment as $key => $ship}
				<tr>
				{if $key =='shipmentLines'}
					{if !isset($ship['shipmentLine']['0'])}
						{$temp_attributes = $ship['shipmentLine']}
						{$ship['shipmentLine'] = array()}
						{$ship['shipmentLine']['0'] = $temp_attributes}
					{/if}
					{foreach $ship['shipmentLine'] as $k => $s}
						{foreach $s as $i => $v}
							<tr>
								<td>{$i|escape:'htmlall':'UTF-8'}</td>
								<td>{$v|escape:'htmlall':'UTF-8'}</td>
							</tr>
						{/foreach}
					{/foreach}
				{else}
					<td>{$key|escape:'htmlall':'UTF-8'}</td>
					<td>{$ship|escape:'htmlall':'UTF-8'}</td>
				{/if}
				</tr>
			{/foreach}
			</tbody>
		</table>
		</div>
		</div>
		</div>
		</div>
		</div>

	{/foreach}
{/if}

<!-- accept order -->
<div id="acceptOrderPopup" class="modal">
	<div class="modal-content">
		<span class="close" id="close_model_accept">&times;</span>
		<div id="popup_content_cedfruugo_accept_order"> Loading.......</div>
	</div>
</div>

<!-- cancel order -->
<div id="cancelOrderPopup" class="modal">
	<div class="modal-content">
		<span class="close" id="close_model_cancel">&times;</span>
		<div id="popup_content_cedfruugo_cancel_order"> Loading.......</div>
	</div>
</div>


<script type="text/javascript">
	var acceptOrderPopup = document.getElementById('acceptOrderPopup');
	var span = document.getElementById("close_model_accept");
	span.onclick = function() {
		acceptOrderPopup.style.display = "none";
		$("#popup_content_cedfruugo").html('Loading........');
	}

	var cancelOrderPopup = document.getElementById('cancelOrderPopup');
	var span = document.getElementById("close_model_cancel");
	span.onclick = function() {
		cancelOrderPopup.style.display = "none";
		$("#popup_content_cedfruugo").html('Loading........');
	}

</script>
<style type="text/css">
	/* The Modal (background) */
	.modal {
		display: none; /* Hidden by default */
		position: fixed; /* Stay in place */
		z-index: 1; /* Sit on top */
		left: 0;
		top: 0;
		width: 100%; /* Full width */
		height: 100%; /* Full height */
		overflow: auto; /* Enable scroll if needed */
		background-color: rgb(0,0,0); /* Fallback color */
		background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
	}

	/* Modal Content/Box */
	.modal-content {
		background-color: #fefefe;
		margin: 15% auto; /* 15% from the top and centered */
		padding: 20px;
		border: 1px solid #888;
		width: 60%; /* Could be more or less, depending on screen size */
	}

	/* The Close Button */
	.close {
		color: #aaa;
		float: right;
		font-size: 28px;
		font-weight: bold;
	}

	.close:hover,
	.close:focus {
		color: black;
		text-decoration: none;
		cursor: pointer;
	}
</style>
<script type="text/javascript">
	function acceptOrder(row) {
		var url = 'index.php?controller=AdminCedElkjopnordicOrder&token={$token|escape:'htmlall':'UTF-8'}';
		$.ajax({
			type: "POST",
			url: url,
			data: { 'order_line_id':row,'action':'acceptOrder','order_id':'{$order_info['order_id']|escape:'htmlall':'UTF-8'}'},
			success: function(response){
				response = JSON.parse(response);
				if(response.success)
					alert(response.message);
				else
					alert('Error :'+ response.message);
			},
			statusCode: {
				500: function(xhr) {
					if(window.console) console.log(xhr.responseText);
				},
				400: function (response) {
					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				},
				404: function (response) {

					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if(window.console) console.log(xhr.responseText);
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

			},
		});

	}

	function cancelOrder(row) {

		var url = 'index.php?controller=AdminCedElkjopnordicOrder&token={$token|escape:'htmlall':'UTF-8'}';
		$.ajax({
			type: "POST",
			url: url,
			data:  { 'order_line_id':row,'action':'cancelOrder','order_id':'{$order_info['order_id']|escape:'htmlall':'UTF-8'}'},
			success: function(response){
				response = JSON.parse(response);
				if(response.success)
					alert(response.message);
				else
					alert('Error :'+ response.message);
			},
			statusCode: {
				500: function(xhr) {
					if(window.console) console.log(xhr.responseText);
				},
				400: function (response) {
					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				},
				404: function (response) {

					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if(window.console) console.log(xhr.responseText);
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

			},
		});
	}


	function shipOrder() {
		var url = 'index.php?controller=AdminCedElkjopnordicOrder&token={$token|escape:'htmlall':'UTF-8'}';
		$.ajax({
			type: "POST",
			url: url,
			data: { 'action' :'shipOrder','order_id' :'{$order_info['order_id']|escape:'htmlall':'UTF-8'}'},
			success: function(response){
				response = JSON.parse(response);
				if(response.success)
					$('#fruugo_shipment_response').html('<span style="color:green;">'+response.message+'</span>');
				else
					$('#fruugo_shipment_response').html('<span style="color:Red;">'+response.message+'</span>');
			},
			statusCode: {
				500: function(xhr) {
					if(window.console) console.log(xhr.responseText);
				},
				400: function (response) {
					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				},
				404: function (response) {

					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if(window.console) console.log(xhr.responseText);
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			},
		});
	}

	function shipCompleteOrder(orderId, carrier_code, tracking_number){
		var url = 'index.php?controller=AdminCedElkjopnordicOrder&token={$token|escape:'htmlall':'UTF-8'}';
		$.ajax({
			type: "POST",
			url: url,
			data: { 'action' :'shipCompleteOrder','order_id' :orderId, 'carrier_code' : carrier_code, 'tracking_number' : tracking_number },
			success: function(response){
				response = JSON.parse(response);
				if(response.success){
					$('#fruugo_shipwhole_response').innerHTML = '<span style="color:green;">'+'Error : '+response.message+'</span>';
					
				}
				else{
					$('#fruugo_shipwhole_response').innerHTML = '<span style="color:Red;">'+'Error : '+response.message+'</span>';

				}
			},
			statusCode: {
				500: function(xhr) {
					if(window.console) console.log(xhr.responseText);
				},
				400: function (response) {
					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				},
				404: function (response) {

					alert('<span style="color:Red;">Error While Uploading Please Check</span>');
				}
			},
			error: function(xhr, ajaxOptions, thrownError) {
				if(window.console) console.log(xhr.responseText);
				alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);
			},
		});
	}
</script>

