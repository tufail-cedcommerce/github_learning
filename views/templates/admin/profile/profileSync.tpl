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
 * @package   CedVidaXL
 */
-->

{if $value}
     <button class="btn btn-primary" title="sync attributes" type="button" onclick="getAttributeSynced(this, '{$profile_id|escape:'htmlall':'UTF-8'}','{$shop_id|escape:'htmlall':'UTF-8'}','{$category_id|escape:'htmlall':'UTF-8'}','{$token|escape:'htmlall':'UTF-8'}')"><i class="process-icon-refresh"></i></button>
     <button class="btn btn-primary" title="sync options" type="button" onclick="getOptionSynced(this, '{$profile_id|escape:'htmlall':'UTF-8'}','{$shop_id|escape:'htmlall':'UTF-8'}','{$category_id|escape:'htmlall':'UTF-8'}','{$token|escape:'htmlall':'UTF-8'}')"><i class="process-icon-refresh"></i></button>
{/if}
