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

<div class="row">

    <div class="col-sm-8">
        <table class="table">
            <thead>
            <tr>
                <th><strong>{l s='Cron Name' mod='cedelkjopnordic'}</strong></th>
                <th><strong>{l s='Cron Url' mod='cedelkjopnordic'}</strong></th>
                <th><strong>{l s='Recommended Time' mod='cedelkjopnordic'}</strong></th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>Sync Price at Elkjopnordic</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedelkjopnordic/syncprice.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>ONCE A DAY</td>
            </tr>
            <tr>
                <td>Fetch Order</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedelkjopnordic/fetchorder.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>PER 1 HOUR</td>
            </tr>
            <tr>
                <td>Order Import</td>
                <td scope="row">{$base_url|escape:'htmlall':'UTF-8'}modules/cedelkjopnordic/createOrder.php?secure_key={$cron_secure_key|escape:'htmlall':'UTF-8'}</td>
                <td>ONCE A DAY</td>
            </tr>
            </tbody>
        </table>
    </div>
</div>