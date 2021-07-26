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

function showError(data, productName)
{

    var reference_message = '';
    modal.style.display = "block";
    var message = '<span><strong style="font-size: 16px;">Product: '+productName+'</strong></span>';
    message += "<table class='table table-bordered table-stripped'><thead style='background: #ECF6FB'>";
    message += "<tr><th><strong>Reference</strong></th><th><strong>Error</strong></th></tr></thead><tbody>";

    if (data.length > 0) {
        for (var i in data) {
            reference_message = data[i].split(':');
            message += "<tr style='height: 30px;'><td style='font-weight: bold;'>"+reference_message[0]+"</td><td>"+reference_message[1]+"</td></tr>";
        }
        message += "</tbody></table>";
    }
    message += "</ul>";
    $("#popup_content_celkjopnordic").html(message);
}


