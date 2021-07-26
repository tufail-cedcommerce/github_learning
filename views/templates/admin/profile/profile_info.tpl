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
    <div class="col-sm-8 col-sm-offset-1">
        <div class="form-wrapper">
            <div class="form-group row">
                <input type="hidden" name="elkjopnordicProfileId"
                        {if isset($profileId) && !empty($profileId)}
                            value="{$profileId|escape:'htmlall':'UTF-8'}"
                        {else}
                            value=""
                        {/if}
                >
            </div>
            <div class="form-group row">
                <label class="control-label col-lg-4 required">
                    {l s='Title' mod='cedelkjopnordic'}
                </label>
                <div class="col-lg-8">
                    <input type="text" name="profileTitle" class="" id="profile-title"
                            {if isset($profileInfo) && isset($profileInfo['profileTitle']) && $profileInfo['profileTitle']}
                        value="{$profileInfo['profileTitle']|escape:'htmlall':'UTF-8'}" {else} value=""
                            {/if}>
                </div>
            </div>

            <div class="form-group row">
                <label class="control-label col-lg-4">
                    {l s='Status' mod='cedelkjopnordic'}
                </label>
                <div class="col-lg-8">
            <span class="switch prestashop-switch fixed-width-lg">
                <input type="radio" name="profileStatus" id="active_on" value="1" checked="checked">
                <label for="active_on">{l s='Enable' mod='cedelkjopnordic'}</label>
				<input type="radio" name="profileStatus" id="active_off" value="0"
                        {if isset($profileInfo) && isset($profileInfo['profileStatus']) && $profileInfo['profileStatus'] == '0'}
                    checked="checked" {/if}>
				<label for="active_off">{l s='Disable' mod='cedelkjopnordic'}</label>
				<a class="slide-button btn"></a>
		    </span>
                </div>
            </div>

            <div class="form-group row">
                <label class="control-label col-lg-4">
                    {l s='Manufacturer' mod='cedelkjopnordic'}
                </label>
                <div class="col-lg-8">
                    <select name='profileManufacturer[]' id="profile-manufacturer" multiple="multiple">
                        {foreach from=$manufacturer_list key=prestaKey item=prestaValue}
                            {if isset($profileInfo) && isset($profileInfo['profileManufacturer']) && is_array($profileInfo['profileManufacturer']) &&
                            in_array($prestaValue.id, $profileInfo['profileManufacturer'])}
                                <option value='{$prestaValue.id|escape:'htmlall':'UTF-8'}' selected>{$prestaValue.name|escape:'htmlall':'UTF-8'}</option>
                               {else}
                            <option value='{$prestaValue.id|escape:'htmlall':'UTF-8'}'>{$prestaValue.name|escape:'htmlall':'UTF-8'}</option>
                        {/if}
                        {/foreach}
                    </select>
                </div>
            </div>

        </div>
    </div>
    <div class="col-sm-1"></div>
</div>
