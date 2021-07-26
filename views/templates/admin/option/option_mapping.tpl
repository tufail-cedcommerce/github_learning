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
<form method="post">
    <div class="panel">
        <div class="panel-heading">
            Elkjopnordic Options Mapping
        </div>
        <div class="panel-body">
          <div class="row">
                {if $selected_profile}
                <input type="hidden" value="{$selected_profile}" name="product_data_profile_selected" id="product_data_profile_selected" />
                {else}
                <input type="hidden" value="{$already_mapped_attributes['id_profile']}" name="product_data_profile_selected" id="product_data_profile_selected" />
                {/if}
                <select id="product_data_profile" name="product_data_profile" onchange="updateProfie(this);">
                    <option></option>
                    {foreach $profileIds as $key => $profileId}
                        {if isset($profileId['id']) && (($selected_profile==$profileId['id']) || ($already_mapped_attributes['id_profile']==$profileId['id'])) }
                            <option selected="selected"
                                    value="{$controllerUrl}&updateproduct&id_product={$product_id|escape:'htmlall':'UTF-8'}&id_profile={$profileId['id']|escape:'htmlall':'UTF-8'}">{$profileId['profile_name']|escape:'htmlall':'UTF-8'}</option>
                        {else}
                            <option value="{$controllerUrl}&updateproduct&id_product={$product_id|escape:'htmlall':'UTF-8'}&id_profile={$profileId['id']|escape:'htmlall':'UTF-8'}">{$profileId['profile_name']|escape:'htmlall':'UTF-8'}</option>
                        {/if}
                    {/foreach}
                </select>
                <input type="hidden" name="account_id" value="{$account_id|escape:'htmlall':'UTF-8'}">
                <input type="hidden" name="current_profile" value="{$current_profile|escape:'htmlall':'UTF-8'}">
            </div>
            <div class="table-responsive">
                <div id="content table-responsive-row clearfix">
                    <table id="attribute" class="table list">
                        <thead>
                        <tr>
                            <td class="text-center" >{l s='Prestashop Option' mod='cedelkjopnordic'}  </td>
                            <td class="text-center" >{l s='Elkjopnordic Option' mod='cedelkjopnordic'}  </td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td class="text-center" >
                                <select class="form-control store-option-change" id="store_option_id" name="store_option_id">
                                    <option value="0"> -- Please Select Store Attribute -- </option>
                                    {foreach $features as $option}
                                        {if (isset($already_mapped_attributes['id_attribute']) && ($option['id_attribute'] == $already_mapped_attributes['id_attribute'])) || ($option['id_attribute'] == $selected_option_id)}
                                            <option selected="selected" value="{$option['id_attribute']|escape:'htmlall':'UTF-8'}">{$option['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="{$option['id_attribute']|escape:'htmlall':'UTF-8'}">{$option['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}

                                </select>
                            </td>
                            
                            <td class="text-center" >
                                <select class="form-control elkjopnordic-option-change" id="cedelkjopnordic_option_id" name="cedelkjopnordic_option_id">
                                    <option value="0">{l s='Please Select Elkjopnordic Attribute' mod='cedelkjopnordic'}</option>
                                      
                                    {foreach $elkjopnordic_attributes as $key => $option}
                                        {if isset($already_mapped_attributes['marketplace_attribute']) &&  ($option['attribute_code'] == $already_mapped_attributes['marketplace_attribute'])}
                                            <option selected="selected" value="{$option['attribute_code']|escape:'htmlall':'UTF-8'}">{$option['attribute_label']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="{$option['attribute_code']|escape:'htmlall':'UTF-8'}">{$option['attribute_label']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                    <option value="custom_value">{l s='Add Custom Value For Store' mod='cedelkjopnordic'} </option>
                                </select>
                            </td>
                            <td class="text-center" >
                            <button type="button" class="form-control" onclick="getvaluesbyattribute(this);">Re Sync Values</button>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
                <div class="table-responsive">
                    <table id="mapping-values" class="list table table-bordered table-hover">
                        <thead>
                        <tr>
                            <td class="text-center" >{l s=' Prestshop Values' mod='cedelkjopnordic'} </td>
                            <td class="text-center" >{l s=' Elkjopnordic Values' mod='cedelkjopnordic'} </td>
                            <td class="text-center" > {l s='Action' mod='cedelkjopnordic'} </td>
                        </tr>
                        </thead>
                        <tbody>
                        
                        {assign var="option_value_row" value=0}
                        {if isset($already_mapped_attributes['mapped_options'])}
                            {$mapped_options = $already_mapped_attributes['mapped_options']}
                            {if !empty($mapped_options)}
                                {foreach $mapped_options as $k => $mapped_option}
                                    <tr  id="option-value-row{$option_value_row|escape:'htmlall':'UTF-8'}" >
                                        <td class="text-left">
                                            <select name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][store_option_value]" class="form-control store-options" >
                                                <option value="0"> -- Please Select Store Option -- </option>
                                                {foreach $option_values[$already_mapped_attributes['id_attribute']] as $key => $option}
                                                    {if $mapped_option['store_value'] == $option}
                                                        <option selected="selected" value="{$option|escape:'htmlall':'UTF-8'}">{$option|escape:'htmlall':'UTF-8'}</option>
                                                    {else}
                                                        <option value="{$option|escape:'htmlall':'UTF-8'}">{$option|escape:'htmlall':'UTF-8'}</option>
                                                    {/if}
                                                {/foreach}
                                            </select>
                                        </td>
                                        <td class="text-left elkjopnordic-options-avaible">
                                        {if isset($already_mapped_attributes['marketplace_attribute']) && $already_mapped_attributes['marketplace_attribute']!='custom_value'}

                                            <input type="text" name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][cedelkjopnordic_option_value]" placeholder="Autocomplete search and map" class="form-control attr-value cedautocomplete" value="{$mapped_option['marketplace_value']|escape:'htmlall':'UTF-8'}"/>
                                            <input type="hidden" name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][cedelkjopnordic_option_code]" class="form-control attr-value" value="{$mapped_option['marketplace_code']|escape:'htmlall':'UTF-8'}"/>
                                            
                                        {else}   
                                        <input type="text" name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][cedelkjopnordic_option_value]" class="form-control attr-value" id="cedelkjopnordic_option_mapping[{$option_value_row}][marketplace_value]" value="{$mapped_option['marketplace_value']|escape:'htmlall':'UTF-8'}"> 
                                        {/if}
                                        <td class="text-left"><button type="button" onclick="$('#option-value-row{$option_value_row|escape:'htmlall':'UTF-8'}').remove();" data-toggle="tooltip" rel="tooltip" title="Remove" class="btn btn-danger">Delete</button></td>
                                    </tr>
                                    {$option_value_row =$option_value_row+1|escape:'htmlall':'UTF-8'}
                                {/foreach}
                           
                            {/if}
                         {elseif $show_option}
                               
                                {foreach $option_values[$selected_option_id] as $k => $mapped_option}
                                    <tr  id="option-value-row{$option_value_row|escape:'htmlall':'UTF-8'}" >
                                        <td class="text-left">
                                            <select name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][store_option_value]" class="form-control store-options" >
                                                <option value="0"> -- Please Select Store Option -- </option>
                                                {foreach $option_values[$selected_option_id] as $key => $option}
                                                    {if $mapped_option == $option}
                                                        <option selected="selected" value="{$option|escape:'htmlall':'UTF-8'}">{$option|escape:'htmlall':'UTF-8'}</option>
                                                    {else}
                                                        <option value="{$option|escape:'htmlall':'UTF-8'}">{$option|escape:'htmlall':'UTF-8'}</option>
                                                    {/if}
                                                {/foreach}
                                            </select>
                                        </td>
                                        <td class="text-left elkjopnordic-options-avaible">
                                        {if isset($already_mapped_attributes['marketplace_attribute']) && $already_mapped_attributes['marketplace_attribute']!='custom_value'}

                                            <input type="text" name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][cedelkjopnordic_option_value]" placeholder="Autocomplete search and map" class="form-control attr-value cedautocomplete" />
                                            <input type="hidden" name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][cedelkjopnordic_option_code]" class="form-control attr-value" />
                                            
                                        {else}   
                                        <input type="text" name="cedelkjopnordic_option_mapping[{$option_value_row|escape:'htmlall':'UTF-8'}][cedelkjopnordic_option_value]" class="form-control attr-value" id="cedelkjopnordic_option_mapping[{$option_value_row}][marketplace_value]" > 
                                        {/if}
                                        <td class="text-left"><button type="button" onclick="$('#option-value-row{$option_value_row|escape:'htmlall':'UTF-8'}').remove();" data-toggle="tooltip" rel="tooltip" title="Remove" class="btn btn-danger">Delete</button></td>
                                    </tr>
                                    {$option_value_row =$option_value_row+1|escape:'htmlall':'UTF-8'}
                                {/foreach}
                             
                        {/if}
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="3"></td>
                            <td class="button text-left"><button type="button" onclick="addOptionValue(0);" data-toggle="tooltip" title="" class="button btn btn-primary" id="add-btn-id">Add</button></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" value="1" id="test_form_submit_btn" name="savemapping"
                    class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='cedelkjopnordic'}
            </button>
            <a class="btn btn-default" id="back-option-controller" data-token="{$token|escape:'htmlall':'UTF-8'}" href="{$controllerUrl|escape:'htmlall':'UTF-8'}">
                <i class="process-icon-cancel"></i> {l s='Cancel' mod='cedelkjopnordic'}
            </a>
        </div>
    </div>
</form>
<script type="text/javascript">
    function updateProfie(selected_profile){
    if(selected_profile && selected_profile.value)
        location.href = selected_profile.value;
    }
    var options='{addslashes(json_encode($option_values))|escape:'quotes':'UTF-8'}';
    var option_value_row = '{$option_value_row|escape:'htmlall':'UTF-8'}';
    var cedelkjopnordicoptions = [];
    var value_json = "{addslashes(json_encode($elkjopnordic_attributes_values))|escape:'quotes':'UTF-8'}";
    value_json = JSON.parse(value_json);

    function addOptionValue(selected_store_value) {
        html  = '<tr id="option-value-row'+option_value_row+'">';
        html += '  <td class="text-left"><select name="cedelkjopnordic_option_mapping[' + option_value_row + '][store_option_value]" class="form-control store-options" >';
        html += $('#option-values').html();
        html += '  </select></td>';
        html += '  <td class="text-left elkjopnordic-options-avaible" >';
        if ($('#cedelkjopnordic_option_id').val()=='custom_value') {
            html += '<input type="text" name="cedelkjopnordic_option_mapping[' + option_value_row + '][cedelkjopnordic_option_value]" placeholder="Add custom Value" class="form-control attr-value" />';
        } else {
          html += '<input type="text" name="cedelkjopnordic_option_mapping[' + option_value_row + '][cedelkjopnordic_option_value]" placeholder="Autocomplete search and map" class="form-control attr-value cedautocomplete" /><input type="hidden" name="cedelkjopnordic_option_mapping[' + option_value_row + '][cedelkjopnordic_option_code]" class="form-control attr-value" />';
        }
        html += '  </td>';
        html += '  <td class="text-left"><button type="button" onclick="$(\'#option-value-row' + option_value_row + '\').remove();" data-toggle="tooltip" rel="tooltip" title="Remove" class="btn btn-danger button">Remove</button></td>';
        html += '</tr>';
        $('#mapping-values' + ' tbody').append(html);
        getValues(option_value_row,selected_store_value);
        attributeautocomplete(option_value_row)
        option_value_row++;
    }

    $("document").ready(function() {
        if ($("#store_option_id").val()==0 || $("#cedelkjopnordic_option_id").val()==0)
            $("#add-btn-id").prop( "disabled", true );
        else
            $("#add-btn-id").prop( "disabled", false );

        $(".store-option-change").change(function() { 
            if ($("#store_option_id").val()==0 || $("#cedelkjopnordic_option_id").val()==0)
                $("#add-btn-id").prop( "disabled", true );
            else
                $("#add-btn-id").prop( "disabled", false );
               
            
            $(".store-options").empty();
            $(".store-options").append('<option value="">Please select Value </option>');
            var result = $.parseJSON(options);

            var option_id=$("#store_option_id").val();
            if (result) {
                for (var event in result[option_id]) {
                    $(".store-options").append('<option value=' + event + '>' + result[option_id][event] + '</option>');
                }
            }
            if($(".store-options") && ($(".store-options").length==0)){
                 selected_profile = document.getElementById('product_data_profile');
                 if(selected_profile && selected_profile.value){
                        location.href = selected_profile.value+'&selected_option_id='+option_id+'&show_option=true';
                 }
                 
            }
        });
        $(".elkjopnordic-option-change").change(function() {
            if (this.value=='custom_value') {
                if ($("#elkjopnordic-option-change").val()==0 || $("#cedelkjopnordic_option_id").val()==0)
                    $("#add-btn-id").prop( "disabled", true );
                else
                    $("#add-btn-id").prop( "disabled", false );
                var alltr = $(".elkjopnordic-options-avaible").parent().parent().parent().children('tbody').children();
                alltr.each(function() {
                    var rowid=this.id;
                    var value_option_id = rowid.split("option-value-row")['1'];
                    var name='cedelkjopnordic_option_mapping['+value_option_id+'][cedelkjopnordic_option_value]';
                    var newdivselect = document.createElement('text');
                    newdivselect.setAttribute('name',name);
                    newdivselect.setAttribute('class', 'form-control attr-value');
                    newdivselect.setAttribute('id',name);
                    $("#option-value-row"+value_option_id).children('td.elkjopnordic-options-avaible').html(newdivselect);
                });
            } else {
            
                if ($("#elkjopnordic-option-change").val()==0 || $("#cedelkjopnordic_option_id").val()==0)
                    $("#add-btn-id").prop( "disabled", true );
                else
                    $("#add-btn-id").prop( "disabled", false );
                    
                var alltr = $(".elkjopnordic-options-avaible").parent().parent().parent().children('tbody').children();
                alltr.each(function() {
                    var rowid=this.id;
                    var value_option_id = rowid.split("option-value-row")['1'];
                    var name='cedelkjopnordic_option_mapping['+value_option_id+'][cedelkjopnordic_option_value]';
                    var newdivselect = document.createElement('input');
                    newdivselect.setAttribute('name',name);
                    newdivselect.setAttribute('type','text');
                    newdivselect.setAttribute('class', 'form-control attr-value cedautocomplete ui-autocomplete-input');
                    newdivselect.setAttribute('id',name);
                    
                    var value_option_id = rowid.split("option-value-row")['1'];
                    var name='cedelkjopnordic_option_mapping['+value_option_id+'][cedelkjopnordic_option_code]';
                    var newdivselect1 = document.createElement('input');
                    newdivselect1.setAttribute('name',name);
                    newdivselect1.setAttribute('type','hidden');
                    newdivselect1.setAttribute('class', 'form-control attr-value');
                    newdivselect1.setAttribute('id',name);
                    $("#option-value-row"+value_option_id).children('td.elkjopnordic-options-avaible').html(newdivselect);
                                        $("#option-value-row"+value_option_id).children('td.elkjopnordic-options-avaible').append(newdivselect1);
                    attributeautocomplete(value_option_id);
                });
            }
        });

    });
    function getValues(option_value_row,selected_store_value) {
        var option_id=$("#store_option_id").val();

        $("#option-value-row"+option_value_row).children('td').children('select.store-options').empty();
        $("#option-value-row"+option_value_row).children('td').children('select.store-options').append('<option value="">Please select Value </option>');
        var result = $.parseJSON(options);
        if (result) {
            for (var event in result[option_id]) {
                if(selected_store_value && (selected_store_value==result[option_id][event])){
$("#option-value-row"+option_value_row).children('td').children('select.store-options').append('<option selected="selected" value="' + result[option_id][event]  + '">' + result[option_id][event] + '</option>');                
                }                
                else {
                 $("#option-value-row"+option_value_row).children('td').children('select.store-options').append('<option value="' + result[option_id][event]  + '">' + result[option_id][event] + '</option>');
                 }
            }
        }
    }

</script>
<script type="text/javascript"><!--
function attributeautocomplete(attribute_row) {
	$('input[name=\'cedelkjopnordic_option_mapping[' + attribute_row + '][cedelkjopnordic_option_value]\']').autocomplete({
		'source': function(request, response) {
		
			 $.ajax({
                            type: "POST",
                            url: 'ajax-tab.php',
                            data: {
                                ajax: true,
                                controller: 'AdminCedElkjopnordicMapping',
                                action: 'getAttributeValues',
                                token: token,
                                type:request.term,
                                profile:$("#product_data_profile_selected").val(),
                                cedelkjopnordic_option_id:$("#cedelkjopnordic_option_id").val(),
                                store_option_id:$("#store_option_id").val(),
                            },
                            success: function(json) {
                            json = JSON.parse(json);
                                response($.map(json, function(item) {
						return {
							label: item.value_label,
							value: item.value_code
						}
					}));
                            },
                            statusCode: {
                                500: function(xhr) {
                                    if (window.console) console.log(xhr.responseText);
                                },
                                400: function (response) {
                                    if (window.console) console.log(xhr.responseText);
                                },
                                404: function (response) {
                                   if (window.console) console.log(xhr.responseText);
                                }
                            },
                            error: function(xhr, ajaxOptions, thrownError) {
                                if (window.console) console.log(xhr.responseText);
                                alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

                            },
                        });
		},
		'select': function(item,selected) {
			$('input[name=\'cedelkjopnordic_option_mapping[' + attribute_row + '][cedelkjopnordic_option_value]\']').val(selected.item['label']);
			$('input[name=\'cedelkjopnordic_option_mapping[' + attribute_row + '][cedelkjopnordic_option_code]\']').val(selected.item['value']);
			return false;
		}
	});
}
//--></script>
<script>
                                            var rowCount = '{$rowCount|escape:'htmlall':'UTF-8'}'; 
                                          
                                            for(i=0;i<rowCount;i++){
                                                                                       
                                             attributeautocomplete(i);
                                            }
                                            function getvaluesbyattribute(button_obj){
                                             
                                             $.ajax({
                                                    type: "POST",
                                                    url: 'ajax-tab.php',
                                                    data: {
                                                        ajax: true,
                                                        controller: 'AdminCedElkjopnordicMapping',
                                                        action: 'getAttributeValuesRefresh',
                                                        token: token,
                                                        profile:$("#product_data_profile_selected").val(),
                                                        cedelkjopnordic_option_id:$("#cedelkjopnordic_option_id").val(),
                                                        store_option_id:$("#store_option_id").val(),
                                                    },
                                                    beforeSend: function() {
			                                        $(button_obj).attr('disabled', true);
			                                        $(button_obj).after('<span class="cedelkjpnordic-loading fa fa-spinner" style="margin-left:2px"></span>');
		                                        },
		                                        complete: function() {
			                                        $(button_obj).attr('disabled', false);
			                                        $('.cedelkjpnordic-loading').remove();
		                                        },
                                                    success: function(json) {
                                                   
                                                       location.href = location;     
                                                    },
                                                    statusCode: {
                                                        500: function(xhr) {
                                                            if (window.console) console.log(xhr.responseText);
                                                        },
                                                        400: function (response) {
                                                            if (window.console) console.log(xhr.responseText);
                                                        },
                                                        404: function (response) {
                                                           if (window.console) console.log(xhr.responseText);
                                                        }
                                                    },
                                                    error: function(xhr, ajaxOptions, thrownError) {
                                                        if (window.console) console.log(xhr.responseText);
                                                        alert(thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText);

                                                    },
                                                });
                                            }
                                            </script>
