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
 * @package   Cedelkjopnordic
 */
-->

<div class="panel">

    <div class="panel-body">
        <div class="form-group row">
            <label class="control-label col-lg-3 required text-right" for="product_offer_state">
                {l s='Offer State' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <select name="profileelkjopnordicInfo[product_offer_state]" id="product_offer_state" required>
                    {foreach from=$productOfferState key=prestaKey item=prestaValue}
                        {if isset($profileAdditionalInfo['product_offer_state']) &&
                        ($profileAdditionalInfo['product_offer_state']== $prestaValue['value'])}
                            <option value="{$prestaValue['value']|escape:'htmlall':'UTF-8'}" selected>{$prestaValue['label']|escape:'htmlall':'UTF-8'}</option>
                        {else}
                            <option value="{$prestaValue['value']|escape:'htmlall':'UTF-8'}">{$prestaValue['label']|escape:'htmlall':'UTF-8'}</option>
                        {/if}
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="control-label col-lg-3 required text-right" for="product_reference_type">
                {l s='Product Reference Type' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <select name="profileelkjopnordicInfo[product_reference_type]" id="product_reference_type" required>

                    {foreach from=$productReferenceType key=prestaKey item=prestaValue}
                        {if isset($profileAdditionalInfo['product_reference_type']) &&
                        ($profileAdditionalInfo['product_reference_type']== $prestaValue['value'])}
                            <option value="{$prestaValue['value']|escape:'htmlall':'UTF-8'}" selected>{$prestaValue['label']|escape:'htmlall':'UTF-8'}</option>
                        {else}
                            <option value="{$prestaValue['value']|escape:'htmlall':'UTF-8'}">{$prestaValue['label']|escape:'htmlall':'UTF-8'}</option>
                        {/if}
                    {/foreach}

                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="control-label col-lg-3 required text-right" for="shippingtime">
                {l s='Shipping Time' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
             <input type="number" name="profileelkjopnordicInfo[shippingtime]" id="shippingtime"
                       value="{if isset($profileAdditionalInfo['shippingtime']) && $profileAdditionalInfo['shippingtime']}{$profileAdditionalInfo['shippingtime']|escape:'htmlall':'UTF-8'}{else}{'5'|escape:'htmlall':'UTF-8'}{/if}"
                       class="form-control" required>
                
            </div>
        </div>
        <div class="form-group row">
            <label class="control-label col-lg-3 required text-right" for="min_quantity_alert">
                {l s='Min Quantity Alert' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <input type="number" name="profileelkjopnordicInfo[min_quantity_alert]" id="min_quantity_alert"
                       value="{if isset($profileAdditionalInfo['min_quantity_alert'])}{$profileAdditionalInfo['min_quantity_alert']|escape:'htmlall':'UTF-8'}{else}1{/if}"
                       class="form-control" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="control-label col-lg-3 text-right" for="description">
                {l s='Offer Description' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <textarea name="profileelkjopnordicInfo[description]" id="description" rows="4" cols="50" class="form-control">{if isset($profileAdditionalInfo['description'])}{$profileAdditionalInfo['description']|escape:'htmlall':'UTF-8'}{/if}</textarea>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-lg-3 text-right" for="internal-description">
                {l s='Offer Internal Description' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <textarea name="profileelkjopnordicInfo[internal-description]" id="internal-description" rows="4" cols="50" class="form-control">{if isset($profileAdditionalInfo['internal-description'])}{$profileAdditionalInfo['internal-description']|escape:'htmlall':'UTF-8'}{/if}</textarea>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-lg-3 text-right" for="price-variant-type">
                {l s='Price variant Type' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <select name="profileelkjopnordicInfo[price-variant-type]" id="price-variant-type">
                    <option value="0">--  Select Price Variation --</option>
                    <option value="1" {if isset($profileAdditionalInfo['price-variant-type']) &&
                    ($profileAdditionalInfo['price-variant-type']== 1)}selected {/if}>Default Price</option>
                    <option value="2" {if isset($profileAdditionalInfo['price-variant-type']) &&
                    ($profileAdditionalInfo['price-variant-type']== 2)}selected {/if}>Increase Fixed Amount</option>
                    <option value="3" {if isset($profileAdditionalInfo['price-variant-type']) &&
                    ($profileAdditionalInfo['price-variant-type']== 3)}selected {/if}>Decrease Fix Amount</option>
                    <option value="4" {if isset($profileAdditionalInfo['price-variant-type']) &&
                    ($profileAdditionalInfo['price-variant-type']== 4)}selected {/if}>Increase Fix Percent</option>
                    <option value="5" {if isset($profileAdditionalInfo['price-variant-type']) &&
                    ($profileAdditionalInfo['price-variant-type']== 5)}selected {/if}>Decrease Fix Percent</option>
                </select>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-lg-3 text-right" for="price-variant-amount">
                {l s='Price Variant Amount' mod='cedelkjopnordic'}
            </label>
            <div class="col-lg-4">
                <input type="number" name="profileelkjopnordicInfo[price-variant-amount]" id="price-variant-amount"
                       value="{if isset($profileAdditionalInfo['price-variant-amount'])}{$profileAdditionalInfo['price-variant-amount']|escape:'htmlall':'UTF-8'}{else}0{/if}"
                       class="form-control" >
            </div>
        </div>
    </div>
</div>
