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

{if is_array($value)}
    {$oldKey[] = $key}
    <tr><td colspan="2" style="text-align: center;"><b>{$key|escape:'htmlall':'UTF-8'}</b></td></tr>
    {foreach $value as $next_level_key => $next_level_value}

        {$key = $next_level_key}
        {$value = $next_level_value}
        {include file="./elkjopnordic_failed_order_form.tpl"}
    {/foreach}
{else}
    <tr><td>{$key|escape:'htmlall':'UTF-8'}</td>
        <td>
            {if $value === false || $value === true}
                <input type="radio" name="order_data{foreach $oldKey as $old_key => $old_value}[{$old_value|escape:'htmlall':'UTF-8'}]{/foreach}[{$key|escape:'htmlall':'UTF-8'}]" value="true" {if $value === true}checked{/if}> true<br>
                <input type="radio" name="order_data{foreach $oldKey as $old_key => $old_value}[{$old_value|escape:'htmlall':'UTF-8'}]{/foreach}[{$key|escape:'htmlall':'UTF-8'}]" value="false"{if $value === false}checked{/if}> false<br>
            {else}
                <input type="text" class="form-control" value="{$value|escape:'htmlall':'UTF-8'}" name="order_data{foreach $oldKey as $old_key => $old_value}[{$old_value|escape:'htmlall':'UTF-8'}]{/foreach}[{$key|escape:'htmlall':'UTF-8'}]"/>
            {/if}
        </td></tr>
{/if}
