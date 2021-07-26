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

<div class="bootstrap" id="" style="">
    <div class="alert alert-info" id="">
        <span id="">Map all the Required Elkjopnordic attributes with Prestashop attributes in order to prevent error at the time of product upload</span>
    </div>
</div>
<div class="panel row">
    <div class="panel-heading">
        <div class="row">
            <div class="col-sm-4 col-lg-4 col-md-4">
                {l s='Elkjopnordic Attribute' mod='cedelkjopnordic'}
            </div>
            <div class="col-sm-4 col-lg-4 col-md-4">
                {l s='Store Attributes' mod='cedelkjopnordic'}
            </div>
            <div class="col-sm-4 col-lg-4 col-md-4">
                {l s='Set Default Value' mod='cedelkjopnordic'}
            </div>
        </div>
    </div>
    <div class="panel-body">
        <table class="table table-bordered">
            <tbody>
           <!-- <tr style="">
                <td colspan="3">
                    <div style="font-size: 16px; text-align: center; padding: 5px;">Elkjopnordic Default Attributes Mapping</div>
                </td>
            </tr>
            <tr>
                <td>
                    <span style="color: red">*</span>SKU
                </td>
                <td>
                    <select name="profileAttributes[internal-sku][mapping]">
                        <option value="system-reference" selected="selected">{l s='Reference' mod='cedelkjopnordic'}</option>
                    </select>
                </td>
                <td>

                    {*<input type="text" placeholder="Enter Internal SKU"*}
                           {*class="form-control"*}
                           {*name="profileAttributes[internal-sku][default_value]"*}
                           {*data-id="internal-sku"*}
                           {*value="{if isset($profileAttributes['internal-sku']['default_value'])}{$profileAttributes['internal-sku']['default_value']}{/if}">*}

                    {*<input type="hidden" name="profileAttributes[internal-sku][required]"*}
                           {*value="1">*}
                </td>
            </tr>-->

            {foreach $elkjopnordicDefaultAttributeList as $attr_code => $attribute}

                <tr>
                    <td>
                        {if $attribute['required']}
                            <span style="color: red">*</span>
                        {/if}
                        {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}
                    </td>
                    <td>
                        <select name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][mapping]"
                                id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}" >
                            <option value=""></option>
                            <optgroup value="0" label="System (Default)">
                                {foreach $storeDefaultAttributes as $key => $system_attribute}
                                    {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="system-{$key}") }
                                        <option selected="selected"
                                                value="system-{$key|escape:'htmlall':'UTF-8'}">{$system_attribute|escape:'htmlall':'UTF-8'}</option>
                                    {else}
                                        <option value="system-{$key|escape:'htmlall':'UTF-8'}">{$system_attribute|escape:'htmlall':'UTF-8'}</option>
                                    {/if}
                                {/foreach}
                            </optgroup>
                            <optgroup value="0" label="Features">
                                {if isset($storeFeatures)}
                                    {foreach $storeFeatures as $feature}
                                        {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="feature-{$feature['id_feature']}") }
                                            <option selected="selected"
                                                    value="feature-{$feature['id_feature']|escape:'htmlall':'UTF-8'}">{$feature['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="feature-{$feature['id_feature']|escape:'htmlall':'UTF-8'}">{$feature['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </optgroup>
                            <optgroup value="0" label="Attributes(Variants)">
                                {if isset($storeAttributes)}
                                    {foreach $storeAttributes as $store_attribute}
                                        {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="attribute-{$store_attribute['id_attribute']}") }
                                            <option selected="selected"
                                                    value="attribute-{$store_attribute['id_attribute']|escape:'htmlall':'UTF-8'}">{$store_attribute['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="attribute-{$store_attribute['id_attribute']|escape:'htmlall':'UTF-8'}">{$store_attribute['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </optgroup>
                        </select>
                    </td>
                    <td>

                            {*<input type="text" placeholder="Enter {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"*}
                                   {*class="form-control"*}
                                   {*name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"*}
                                   {*data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"*}
                                   {*value="{if isset($profileAttributes[{$attribute['attribute_code']}]['default_value'])}{$profileAttributes[{$attribute['attribute_code']}]['default_value']}{elseif isset($attribute['default_value'])}{$attribute['default_value']}{/if}">*}

                        {*<input type="hidden" name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][required]"*}
                               {*value="{$attribute['required']|escape:'htmlall':'UTF-8'}">*}
                    </td>
                </tr>
            {/foreach}

            <tr style="">
                <td colspan="3">
                    <div style="font-size: 16px; text-align: center; padding: 5px;">Elkjopnordic Required/Optional Attributes Mapping</div>
                </td>
            </tr>
           <tr>
               <td>
                   <span style="color: red">*</span>SHOP_SKU
               </td>
               <td>
                   <select name="profileAttributes[Shop_SKU][mapping]">
                       <option value="system-reference" selected="selected">{l s='Reference' mod='cedelkjopnordic'}</option>
                   </select>
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
                        {if $attribute['required']}
                            <span style="color: red">*</span>
                        {/if}
                        {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}
                    </td>
                    <td>
                        <select name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][mapping]"
                                id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}" >
                            <option value=""></option>
                            <optgroup value="0" label="System (Default)">
                                {foreach $storeDefaultAttributes as $key => $system_attribute}
                                    {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="system-{$key}") }
                                        <option selected="selected"
                                                value="system-{$key|escape:'htmlall':'UTF-8'}">{$system_attribute|escape:'htmlall':'UTF-8'}</option>
                                    {else}
                                        <option value="system-{$key|escape:'htmlall':'UTF-8'}">{$system_attribute|escape:'htmlall':'UTF-8'}</option>
                                    {/if}
                                {/foreach}
                            </optgroup>
                            <optgroup value="0" label="Features">
                                {if isset($storeFeatures)}
                                    {foreach $storeFeatures as $feature}
                                        {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="feature-{$feature['id_feature']}") }
                                            <option selected="selected"
                                                    value="feature-{$feature['id_feature']|escape:'htmlall':'UTF-8'}">{$feature['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="feature-{$feature['id_feature']|escape:'htmlall':'UTF-8'}">{$feature['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </optgroup>
                            <optgroup value="0" label="Attributes(Variants)">
                                {if isset($storeAttributes)}
                                    {foreach $storeAttributes as $store_attribute}
                                        {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="attribute-{$store_attribute['id_attribute']}") }
                                            <option selected="selected"
                                                    value="attribute-{$store_attribute['id_attribute']|escape:'htmlall':'UTF-8'}">{$store_attribute['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="attribute-{$store_attribute['id_attribute']|escape:'htmlall':'UTF-8'}">{$store_attribute['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </optgroup>
                        </select>
                    </td>
                    <td>
                        {if $attribute['attribute_type'] == 'LIST'}
                            <input type="text" class="form-control"
                                   name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_text]"
                                   placeholder="Search &amp; Select {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"
                                   onkeyup="getOptionValues(this)" data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"
                                   data-valuelist="{$attribute['values_list']|escape:'htmlall':'UTF-8'}"
                                   data-catId="{$attribute['category_id']|escape:'htmlall':'UTF-8'}"
                                   value="{if isset($profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_text'])}{$profileAttributes[{$attribute['attribute_code']}]['default_text']|escape:'htmlall':'UTF-8'}{elseif isset($attribute['default_value'])}{$attribute['default_value']|escape:'htmlall':'UTF-8'}{/if}">
                            <input type="hidden" name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"
                                   value="{if isset($profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value'])}{$profileAttributes[{$attribute['attribute_code']}]['default_value']|escape:'htmlall':'UTF-8'}{elseif isset($attribute['default_value'])}{$attribute['default_value']|escape:'htmlall':'UTF-8'}{/if}">

                        {else}
                            <input type="text" placeholder="Enter {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"
                                   class="form-control"
                                   name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"
                                   data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"
                                   value="{if isset($profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value'])}{$profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}]['default_value']}{elseif isset($attribute['default_value'])}{$attribute['default_value']|escape:'htmlall':'UTF-8'}{/if}">
                        {/if}

                        <input type="hidden" name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][required]"
                               value="{$attribute['required']|escape:'htmlall':'UTF-8'}">
                    </td>
                </tr>
            {/foreach}
            <tr style="">
                <td colspan="3">
                    <div style="font-size: 16px; text-align: center; padding: 5px;">Elkjopnordic Variant Attributes Mapping</div>
                </td>
            </tr>
            {foreach $elkjopnordicVariantAttributeList as $attr_code => $attribute}
                <tr>
                    <td>
                        {if $attribute['required']}
                            <span style="color: red">*</span>
                        {/if}
                        {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}
                    </td>
                    <td>
                        <select name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][mapping]"
                                id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}" {if $attribute['required']} required {/if}>
                            <option value=""></option>
                            <optgroup value="0" label="Attributes(Variants)">
                                {if isset($storeAttributes)}
                                    {foreach $storeAttributes as $store_attribute}
                                        {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="attribute-{$store_attribute['id_attribute']}") }
                                            <option selected="selected"
                                                    value="attribute-{$store_attribute['id_attribute']|escape:'htmlall':'UTF-8'}">{$store_attribute['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="attribute-{$store_attribute['id_attribute']|escape:'htmlall':'UTF-8'}">{$store_attribute['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </optgroup>
                            <optgroup value="0" label="Features">
                                {if isset($storeFeatures)}
                                    {foreach $storeFeatures as $feature}
                                        {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="feature-{$feature['id_feature']}") }
                                            <option selected="selected"
                                                    value="feature-{$feature['id_feature']|escape:'htmlall':'UTF-8'}">{$feature['name']|escape:'htmlall':'UTF-8'}</option>
                                        {else}
                                            <option value="feature-{$feature['id_feature']|escape:'htmlall':'UTF-8'}">{$feature['name']|escape:'htmlall':'UTF-8'}</option>
                                        {/if}
                                    {/foreach}
                                {/if}
                            </optgroup>
                            <optgroup value="0" label="System (Default)">
                                {foreach $storeDefaultAttributes as $key => $system_attribute}
                                    {if isset($profileAttributes[{$attribute['attribute_code']}]['mapping']) && ($profileAttributes[{$attribute['attribute_code']}]['mapping']=="system-{$key}") }
                                        <option selected="selected"
                                                value="system-{$key|escape:'htmlall':'UTF-8'}">{$system_attribute|escape:'htmlall':'UTF-8'}</option>
                                    {else}
                                        <option value="system-{$key|escape:'htmlall':'UTF-8'}">{$system_attribute|escape:'htmlall':'UTF-8'}</option>
                                    {/if}
                                {/foreach}
                            </optgroup>
                        </select>
                    </td>
                    <td>
                        {*{if $attribute['attribute_type'] == 'LIST'}*}
                            {*<input type="text" class="form-control"*}
                                   {*name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_text]"*}
                                   {*placeholder="Search &amp; Select {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"*}
                                   {*onkeyup="getOptionValues(this)" data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"*}
                                   {*data-valuelist="{$attribute['values_list']|escape:'htmlall':'UTF-8'}"*}
                                   {*data-catId="{$attribute['category_id']|escape:'htmlall':'UTF-8'}"*}
                                    {*value="{if isset($profileAttributes[{$attribute['attribute_code']}]['default_text'])}{$profileAttributes[{$attribute['attribute_code']}]['default_text']}{elseif isset($attribute['default_value'])}{$attribute['default_value']}{/if}">*}
                            {*<input type="hidden" name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"*}
                                   {*value="{if isset($profileAttributes[{$attribute['attribute_code']}]['default_value'])}{$profileAttributes[{$attribute['attribute_code']}]['default_value']}{elseif isset($attribute['default_value'])}{$attribute['default_value']}{/if}">*}

                        {*{else}*}
                            {*<input type="text" placeholder="Enter {$attribute['attribute_label']|escape:'htmlall':'UTF-8'}"*}
                                   {*class="form-control"*}
                                   {*name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][default_value]"*}
                                   {*data-id="{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}"*}
                                   {*value="{if isset($profileAttributes[{$attribute['attribute_code']}]['default_value'])}{$profileAttributes[{$attribute['attribute_code']}]['default_value']}{elseif isset($attribute['default_value'])}{$attribute['default_value']}{/if}">*}
                        {*{/if}*}

                        {*<input type="hidden" name="profileAttributes[{$attribute['attribute_code']|escape:'htmlall':'UTF-8'}][required]"*}
                               {*value="{$attribute['required']|escape:'htmlall':'UTF-8'}">*}
                    </td>
                </tr>
            {/foreach}
            </tbody>
        </table>
    </div>
</div>

<script>
    $.ajaxSetup({ type:"post" });
    function getOptionValues(i) {
        try {
            var code = i.getAttribute('data-id');
            var values_list_code = i.getAttribute('data-valuelist');
            var category_id = i.getAttribute('data-catId');
            $('input[name="profileAttributes[' + code + '][default_text]"]').autocomplete('{$controllerUrl}', {
                    minChars: 1,
                    max: 15,
                    width: 250,
                    selectFirst: false,
                    scroll: true,
                    dataType: "json",
                    formatItem: function (data, i, max, value, term) {
                        return value;
                    },
                    parse: function (data) {
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
                        elkjopnordic_profile_id: $("input[name=elkjopnordicProfileId]").val(),
                        attribute_code: code,
                        filter_name: i.value,
                        category_id :category_id,
                        dataType: "json",
                        values_list_code: values_list_code
                    }
                }
            ).result(function(event, data, formatted) {
                console.log(data);
                $('input[name="profileAttributes[' + code + '][default_text]"]').val(data.value_label);
                $('input[name="profileAttributes[' + code + '][default_value]"]').val(data.value_code);
            });
        } catch (e) {
            console.log(e.message);
        }
    }
</script>
