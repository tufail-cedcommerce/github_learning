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

 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE(http://cedcommerce.com/)
 * @license   http://cedcommerce.com/license-agreement.txt
 * @category  Ced
 * @package   CedElkjopnordic
 */
-->
<div>
    {if isset($validationData) && count($validationData)}
        <span class="btn-group-action">
                <a class="btn btn-danger"
                   onclick="showError({$validationJson|escape:'htmlall': 'UTF-8'}, '{$productName|escape:'htmlall': 'UTF-8'}')">
                    {l s='View Errors' mod='cedelkjopnordic'}
                </a>
            </span>
    {else}
        <span class="btn-group-action">
                <span class="btn btn-success">
                    {l s='No Error' mod='cedelkjopnordic'}
                </span>
            </span>
    {/if}
</div>
