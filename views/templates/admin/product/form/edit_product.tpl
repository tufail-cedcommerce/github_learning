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
<form method="post" id="elkjopnordic-product-form">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-tags"></i>
            {if isset($productId) && !empty($productId)}
                {l s=' Edit product: ' mod='cedelkjopnordic'}{$productId|escape:'htmlall':'UTF-8'}
            {/if}
        </div>
        <div class="panel-body">
            <div class="productTabs">
                <ul class="tab nav nav-tabs">
                    <li class="tab-row active">
                        <a class="tab-page" href="#productAttributes" data-toggle="tab">
                            <i class="icon-file-text"></i> {l s='Attributes' mod='cedelkjopnordic'}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="panel tab-pane fade in active row" id="productAttributes">
                    <div class="panel row">
                        <div class="panel-heading">
                            <div class="row">
                                <div class="col-sm-6 col-lg-6 col-md-6">
                                    {l s='Elkjopnordic Attribute' mod='cedelkjopnordic'}
                                </div>
                                <div class="col-sm-6 col-lg-6 col-md-6">
                                    {l s='Set Default Value' mod='cedelkjopnordic'}
                                </div>
                            </div>
                        </div>
                        <div class="panel-body">
                            <table class="table table-bordered">
                                <tbody>

                                <tr style="">
                                    <td colspan="2">
                                        <div style="font-size: 16px; text-align: center; padding: 5px;">Elkjopnordic Required/Optional Attributes Mapping</div>
                                    </td>
                                </tr>
                                {foreach $elkjopnordicAttributeList as $attr_code => $attribute}
                                    {if in_array($attribute['attribute_code'],$skip_attributes)}{continue}{/if}
                                    {if in_array($attribute['attribute_code'],$elkjopnordicVariantAttributes)}
                                        {$elkjopnordicVariantAttributeList[] = $attribute}
                                        {continue}
                                    {/if}
                                    <tr>
                                        <td>
                                            {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}
                                        </td>

                                        <td>
                                            {if $attribute['attribute_type'] == 'LIST'}
                                                <input type="text" class="form-control"
                                                       name="productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_text]"
                                                       placeholder="Search &amp; Select {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"
                                                       onkeyup="getOptionValues(this)" data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"
                                                       data-valuelist="{$attribute['values_list']|escape:'htmlall':'UTF-8'}"
                                                       data-catId="{$attribute['category_id']|escape:'htmlall':'UTF-8'}"
                                                       value="{if isset($productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_text'])}{$productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_text']|escape:'htmlall':'UTF-8'}{elseif isset($attribute['default_value'])}{$attribute['default_value']|escape:'htmlall':'UTF-8'}{/if}">
                                                <input type="hidden" name="productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"
                                                       value="{if isset($productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value'])}{$productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value']|escape:'htmlall':'UTF-8'}{elseif isset($attribute['default_value'])}{$attribute['default_value']|escape:'htmlall':'UTF-8'}{/if}">

                                            {else}
                                                <input type="text" placeholder="Enter {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"
                                                       class="form-control"
                                                       name="productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"
                                                       data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"
                                                       value="{if isset($productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value'])}{$productAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value']|escape:'htmlall':'UTF-8'}{elseif isset($attribute['default_value'])}{$attribute['default_value']|escape:'htmlall':'UTF-8'}{/if}">
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
        <div class="panel-footer">
            <button  type="submit" onclick="submitProductAttribute(event)" value="1" id="test_form_submit_btn" name="submitElkjopnordicProductSave"
                     class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='cedelkjopnordic'}
            </button>
            <a class="btn btn-default" id="back-elkjopnordic-product-controller" data-token="{$currentToken|escape:'htmlall':'UTF-8'}" href="{$controllerUrl|escape:'htmlall':'UTF-8'}">
                <i class="process-icon-cancel"></i> {l s='Cancel' mod='cedelkjopnordic'}
            </a>
        </div>
    </div>
</form>
<script>
    $.ajaxSetup({ type:"post" });
    function getOptionValues(i) {
        try {
            var code = i.getAttribute('data-id');
            console.log(i.name);
            console.log('input[name="productAttributes[' + code + '][default_text]"]');
            var values_list_code = i.getAttribute('data-valuelist');
            var category_id = i.getAttribute('data-catId');
            $('input[name="productAttributes[' + code + '][default_text]"]').autocomplete('{$profileControllerUrl}', {
                    minChars: 1,
                    max: 15,
                    width: 250,
                    selectFirst: false,
                    scroll: true,
                    dataType: "json",
                    formatItem: function (data, i, max, value, term) {
                        console.log('formatItem');
                        console.log(data);
                        console.log(value);
                        return value;
                    },
                    parse: function (data) {
                        console.log(data);
                        var mytab = new Array();
                        for (var i = 0; i < data.length; i++)
                            mytab[mytab.length] = { value: data[i].value_label, data:data[i]};
                        return mytab;
                    },
                    extraParams: {
                        ajax: "1",
                        token: $('#back-elkjopnordic-profile-controller').attr('data-token'),
                        tab: "AdminCedElkjopnordicProfile",
                        action: "getAttributeOptions",
                        // elkjopnordic_profile_id: $("input[name=elkjopnordicProfileId]").val(),
                        attribute_code: code,
                        filter_name: i.value,
                        category_id :category_id,
                        dataType: "json",
                        values_list_code: values_list_code
                    }
                }
            ).result(function(event, data, formatted) {
                $('input[name="productAttributes[' + code + '][default_text]"]').val(data.value_label);
                $('input[name="productAttributes[' + code + '][default_value]"]').val(data.value_code);
            });
        } elkjopnordic (e) {
            console.log(e.message);
        }
    }
</script>
