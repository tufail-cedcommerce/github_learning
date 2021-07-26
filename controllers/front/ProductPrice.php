<?php
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
 * @package   CedAmazon
 */

require_once _PS_MODULE_DIR_ . 'cedamazon/classes/cedamazon.class.helper.php';
require_once _PS_MODULE_DIR_ . 'cedamazon/classes/cedamazon.class.profile.php';
require_once _PS_MODULE_DIR_ . 'cedamazon/classes/cedamazon.class.account.php';
require_once _PS_MODULE_DIR_ . 'cedamazon/classes/cedamazon.class.product.php';
require_once _PS_MODULE_DIR_ . 'cedamazon/classes/cedamazon.class.order.php';
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
class CedamazonProductPriceModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        if (!Tools::getIsset('secure_key')
            || Tools::getValue('secure_key') != Configuration::get('CEDAMAZON_CRON_SECURE_KEY')
        ) {
            die('Secure key does not matched');
        }
        try {
            $chunk_number = Tools::getValue('chunk_number');
            if(!$chunk_number)
            $chunk_number=0;
            $db = Db::getInstance();
            if(Tools::getIsset('chunk_number')){
            if($chunk_number)
$sql = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "cedamazon_profile_products` LIMIT ".($chunk_number+100).",100";            
else 
$sql = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "cedamazon_profile_products` LIMIT ".$chunk_number.",100";            
            }
            
            else
            $sql = "SELECT `id_product` FROM `" . _DB_PREFIX_ . "cedamazon_profile_products`";
            $results = $db->executeS($sql);
           

            $inventoryChunks = array();
           
            if (!empty($results)) {
                foreach ($results as $result) {
                    if (isset($result['id_product'])) {
                        
                        $inventoryChunks[] = $result['id_product'];
                    }
                   
                }
            }
           
            if (!empty($inventoryChunks)) {
                $inventoryChunkFinal = array();
                $cedAmazonHelper = new CedAmazonHelper();
                $cedAmazonProduct = new CedAmazonProduct();
               
               
                if(!empty($inventoryChunks)){
                $inventoryChunks = array_unique($inventoryChunks);
                    $response = $cedAmazonProduct->updatePrice($inventoryChunks);
                   
                   
                    $cedAmazonHelper->log(
                        'CronUpdatePrice',
                        'Info',
                        'Update Price',
                        json_encode(
                            array(
                                'data' => $inventoryChunks,
                                'response' => $response,
                            )
                        ),
                        true
                    );
                }
            }

        } catch (Exception $e) {
            $cedAmazonHelper->log(
                'CronUpdateProduct',
                'Exception',
                $e->getMessage(),
                json_encode(
                    array(
                        'Trace' => $e->getMessage()
                    )
                ),
                true
            );
        }
        parent::initContent();
        $this->setTemplate('module:cedelkjopnordic/views/templates/front/orderstatus.tpl');
        die('Executed Successfully.');
    }
}
