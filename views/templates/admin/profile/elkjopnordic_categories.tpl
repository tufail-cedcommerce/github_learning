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

{if isset($elkjopnordicCategoryList) && !empty($elkjopnordicCategoryList)}
    <option value="">--Select--</option>
    {foreach $elkjopnordicCategoryList as $elkjopnordicCat}
        {if $elkjopnordicCat['level'] == $level}
        {if (isset($profileElkjopnordicCategories) && !empty($profileElkjopnordicCategories))}
            {if isset($profileElkjopnordicCategories['level_'|cat:$level]) && ($profileElkjopnordicCategories['level_'|cat:$level] == $elkjopnordicCat['code'])}
                <option selected="selected" value="{$elkjopnordicCat['code']|escape:'htmlall':'UTF-8'}">{$elkjopnordicCat['label']|escape:'htmlall':'UTF-8'}</option>
            {else}
                <option value="{$elkjopnordicCat['code']|escape:'htmlall':'UTF-8'}">{$elkjopnordicCat['label']|escape:'htmlall':'UTF-8'}</option>
            {/if}
        {else}
            <option value="{$elkjopnordicCat['code']|escape:'htmlall':'UTF-8'}">{$elkjopnordicCat['label']|escape:'htmlall':'UTF-8'}</option>
        {/if}
        {/if}
    {/foreach}
{/if}