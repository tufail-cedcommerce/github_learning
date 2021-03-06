<?php
/**
 * CedCommerce
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 *
 * DISCLAIMER
 *
 * Do not edit or add to the file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @author    CedCommerce Core Team <connect@cedcommerce.com>
 * @copyright Copyright CEDCOMMERCE (http://cedcommerce.com/)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

class AdminElkjopnordicConfigController extends ModuleAdminController
{
    public function __construct()
    {
        $this->bootstrap = true;
        $link = new LinkCore();
        $controller_link = $link->getAdminLink('AdminModules');
        Tools::redirectAdmin($controller_link.'&configure=cedelkjopnordic');
        parent::__construct();
    }
}
