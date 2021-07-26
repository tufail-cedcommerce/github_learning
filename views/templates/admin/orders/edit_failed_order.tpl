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

<form method="post" id="elkjopnordic-order-form">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-tags"></i>
            {if isset($id) && !empty($id)}
                {l s=' Edit Order: ' mod='cedelkjopnordic'}{$id|escape:'htmlall':'UTF-8'}
                <input type="hidden" name="id" value="{$id|escape:'htmlall':'UTF-8'}">
                <p style="text-align: center;">{l s=' Error: ' mod='cedelkjopnordic'}{$reason|escape:'htmlall':'UTF-8'}</p>
            {/if}

        </div>
        <div class="panel-body">
            <table class="table table-bordered">
                <tbody>
                {foreach $failedOrderData as $key => $value}
                    {$oldKey = array()}
                    {include file="./elkjopnordic_failed_order_form.tpl"}
                {/foreach}
                </tbody>
            </table>
        </div>
        <div class="panel-footer">
            <button  type="submit" value="1" id="test_form_submit_btn" name="submitElkjopnordicOrderSave"
                     class="btn btn-default pull-right">
                <i class="process-icon-save"></i> {l s='Save' mod='cedelkjopnordic'}
            </button>
            <a class="btn btn-default" id="back-elkjopnordic-profile-controller" data-token="{$currentToken|escape:'htmlall':'UTF-8'}" href="{$controllerUrl|escape:'htmlall':'UTF-8'}">
                <i class="process-icon-cancel"></i> {l s='Cancel' mod='cedelkjopnordic'}
            </a>
        </div>
    </div>
</form>