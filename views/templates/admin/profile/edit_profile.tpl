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

<div id="elkjopnordic-overlay">
    <div class="overlay-content">
        <img src="{$imgUrl|escape:'htmlall':'UTF-8'}">
    </div>
</div>

<style>
    .control-label{
        text-align: right;
        margin-bottom: 0;
        padding-top: 7px;
    }
    #elkjopnordic-overlay {
        position: fixed; /* Sit on top of the page content */
        display: none; /* Hidden by default */
        width: 100%; /* Full width (cover the whole page) */
        height: 100%; /* Full height (cover the whole page) */
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: rgba(0,0,0,0.1); /* Black background with opacity */
        z-index: 2; /* Specify a stack order in case you're using a different order for other elements */
        cursor: pointer; /* Add a pointer on hover */
    }
    .overlay-content {
        position: relative;
        top: 50%; /* 25% from the top */
        width: 100%; /* 100% width */
        text-align: center; /* Centered text/links */
    }
</style>
<div class="bootstrap" id="error-message" style="display: none;">
    <div class="alert alert-danger" id="error-text">
        <button type="button" class="close" onclick="closeMessage()">×</button>
        <span id="default-error-message-text">Error</span>
    </div>
</div>
<form method="post" id="elkjopnordic-profile-form">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-tags"></i>
            {if isset($profileId) && !empty($profileId)}
                {l s=' Edit profile: ' mod='cedelkjopnordic'}{$profileId|escape:'htmlall':'UTF-8'}
            {else} {l s=' New profile' mod='cedelkjopnordic'}
            {/if}
        </div>
        <div class="panel-body">
            <div class="productTabs">
                <ul class="tab nav nav-tabs">
                    <li class="tab-row active">
                        <a class="tab-page" href="#profileInfo" data-toggle="tab">
                            <i class="icon-file-text"></i> {l s='Profile Info' mod='cedelkjopnordic'}
                        </a>
                    </li>
                    <li class="tab-row">
                        <a class="tab-page" href="#profileCategory" data-toggle="tab">
                            <i class="icon-wrench"></i> {l s='Category Mapping' mod='cedelkjopnordic'}
                        </a>
                    </li>
                    <li class="tab-row">
                        <a class="tab-page" href="#profileAttributes" data-toggle="tab">
                            <i class="icon-wrench"></i> {l s='Attribute Mapping' mod='cedelkjopnordic'}
                        </a>
                    </li>
                    <li class="tab-row">
                        <a class="tab-page" href="#profileElkjopnordicSettings" data-toggle="tab">
                            <i class="icon-bus"></i> {l s='Elkjopnordic Settings' mod='cedelkjopnordic'}
                        </a>
                    </li>
                </ul>
            </div>
            <div class="tab-content">
                <div class="panel tab-pane fade in active row" id="profileInfo">
                    {include file="./profile_info.tpl"}
                </div>
                <div class="panel tab-pane" id="profileCategory">
                    {include file="./profile_category_mapping.tpl"}
                </div>
                <div class="panel tab-pane" id="profileAttributes">
                    {include file="./profile_attribute_mapping.tpl"}
                </div>
                <div class="panel tab-pane" id="profileElkjopnordicSettings">
                    {include file="./profile_elkjopnordic_settings.tpl"}
                </div>
            </div>
        </div>
        <div class="panel-footer">
            <button  type="submit" onclick="submitElkjopnordicProfile(event)" value="1" id="test_form_submit_btn" name="submitElkjopnordicProfileSave"
                     class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='cedelkjopnordic'}
            </button>
            <a class="btn btn-default" id="back-elkjopnordic-profile-controller" data-token="{$currentToken|escape:'htmlall':'UTF-8'}" href="{$controllerUrl|escape:'htmlall':'UTF-8'}">
                <i class="process-icon-cancel"></i> {l s='Cancel' mod='cedelkjopnordic'}
            </a>
        </div>
    </div>
</form>