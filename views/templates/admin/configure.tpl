
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

<div class="panel">
    <h3><i class="icon icon-credit-card"></i> {l s='Elkjopnordic Integration' mod='cedelkjopnordic'}</h3>
    <p>
        <strong>{l s='Here is my new generic module!' mod='cedelkjopnordic'}</strong><br/>
        {l s='Thanks to PrestaShop, now I have a great module.' mod='cedelkjopnordic'}<br/>
        {l s='I can configure it using the following configuration form.' mod='cedelkjopnordic'}
    </p>
    <br/>
    <p>
        {l s='This module will boost your sales!' mod='cedelkjopnordic'}
    </p>
</div>
<script type="text/javascript">
    window.onload = function () {
        if (document.getElementById('Elkjopnordic_PRICE_VARIANT_TYPE') && document.getElementById('Elkjopnordic_PRICE_VARIANT_TYPE').value) {
            if (parseInt(document.getElementById('Elkjopnordic_PRICE_VARIANT_TYPE').value) != 1) {
                document.getElementById('Elkjopnordic_PRICE_VARIANT_AMOUNT').parentNode.parentNode.parentNode.style.display = 'block';
            } else {
                document.getElementById('Elkjopnordic_PRICE_VARIANT_AMOUNT').parentNode.parentNode.parentNode.style.display = 'none';
            }
            document.getElementById('Elkjopnordic_PRICE_VARIANT_TYPE').addEventListener("change", onchnageprice);
        }
    };
    function onchnageprice() {
        if (parseInt(document.getElementById('Elkjopnordic_PRICE_VARIANT_TYPE').value) != 1) {
            document.getElementById('Elkjopnordic_PRICE_VARIANT_AMOUNT').parentNode.parentNode.parentNode.style.display = 'block';
        } else {
            document.getElementById('Elkjopnordic_PRICE_VARIANT_AMOUNT').parentNode.parentNode.parentNode.style.display = 'none';
        }
    }
</script>
