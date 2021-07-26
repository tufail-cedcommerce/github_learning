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
 * @package   CedElkjopnordic
 */

include_once _PS_MODULE_DIR_ . 'cedelkjopnordic/classes/CedElkjopnordicProduct.php';

class CedElkjopnordicHelper
{

    protected $api_url = '';
    protected $user = '';
    protected $timestamp;
    protected $product_info = array();

    public function init()
    {
        $this->_api_url = Configuration::get('ELKJOPNORDIC_API_URL');
        $this->user = Configuration::get('ELKJOPNORDIC_API_KEY');
    }

    /**
     * @return array
     */
    public function methodCodeArrray()
    {
        return array(
            'Standard' => 'Standard',
            'Express' => 'Express',
            'Oneday' => 'Oneday',
            'Freigh' => 'Freigh',
            'WhiteGlove' => 'WhiteGlove',
            'Value' => 'Value'
        );
    }

    /**
     * @return array
     */
    public function getElkjopnordicCarriers()
    {
        $method = 'shipping/carriers';
        $response = $this->WGetRequest($method, array(), 'json');
        return $response;
    }

    /**
     * @return array
     */
    public function carrierNameArray()
    {
        return array(
            'UPS' => 'UPS',
            'USPS' => 'USPS',
            'FedEx' => 'FedEx',
            'Airborne' => 'Airborne',
            'OnTrac' => 'OnTrac'
        );
    }

    /**
     * @return bool
     */
    public function isEnabled()
    {

        $flag = false;

        if (Configuration::get('ELKJOPNORDIC_LIVE_MODE')) {
            $flag = true;
            $this->init();
        }
        return $flag;
    }

    /**
     * Get Request on https://marketplace.Elkjopnordicapis.com/
     * @param $url
     * @param array $params
     * @param string $response_type
     * @return array
     */
    public function WGetRequest($url, $params = array(), $response_type = 'xml')
    {
        $enable = $this->isEnabled();

        if ($enable) {
            try {
                // $header = '';
                $url = Configuration::get('ELKJOPNORDIC_API_URL') . $url;
                if (!empty($params)) {
                    $url = $url . '?' . http_build_query($params);
                }
                $headers = array();

                $headers[] = "Authorization: $this->user";
                $headers[] = "Content-Type: application/json";

                if ($response_type == 'json') {
                    $headers[] = "Accept: application/json";
                } else {
                    $headers[] = "Accept: application/xml";
                }

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_HEADER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec($ch);


                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                Tools::substr($server_output, 0, $header_size);
                $body = Tools::substr($server_output, $header_size);
                if (!curl_errno($ch)) {
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($http_code == 200) {
                        return array('success' => true, 'response' => $server_output);
                    } else {
                        if ($body) {
                            $this->log(
                                __METHOD__,
                                'Info',
                                'response of get request',
                                json_encode(
                                    array(
                                        'Request Param' => $params,
                                        'Response' => $body
                                    )
                                ),
                                true
                            );
                            return array('success' => false, 'message' => $body);
                        } else {
                            $this->log(
                                __METHOD__,
                                'Info',
                                'response of get request',
                                json_encode(
                                    array(
                                        'Request Param' => $params,
                                        'Response' => $server_output
                                    )
                                ),
                                true
                            );
                            return array('success' => false, 'message' => $server_output);
                        }
                    }
                }
                curl_close($ch);
            } catch (Exception $e) {
                $this->log(
                    __METHOD__,
                    'Exception',
                    'response of get request',
                    json_encode(
                        array(
                            'Request Param' => $params,
                            'Response' => $body
                        )
                    ),
                    true
                );
                return array('success' => false, 'message' => $e->getMessage());
            }
        } else {
            $this->log(
                __METHOD__,
                'Info',
                ' Module Not Enabled',
                json_encode(
                    array(
                         'Request Param' => $params,
                         'Response' => 'Module Not Enable'
                     )
                ),
                true
            );
            return array('success' => false, 'message' => 'Module is not enable.');
        }
    }

    /**
     * @param $data
     * @param int $step
     * @param bool $force_log
     * @return int
     * @throws PrestaShopDatabaseException
     */

    public function log($method = '', $type = '', $message = '', $response = '', $force_log = false,$shop_id=0)
    {
        $createdAt = date('Y-m-d H:i:s');
        if (Configuration::get('ELKJOPNORDIC_DEBUG_ENABLE')) {
            $db = Db::getInstance();
            $db->insert(
                'cedelkjopnordic_logs',
                array(
                    'method' => pSQL($method),
                    'type' => pSQL($type),
                    'message' => pSQL($message),
                    'data' => pSQL($response, true),
                    'created_at' => pSQL($createdAt),
                    'id_shop' => (int)$shop_id,
                )
            );
        }
    }

    /**
     * @param $contents
     * @param int $get_attributes
     * @param string $priority
     * @return array
     */
    public function xml2array($contents, $get_attributes = 1, $priority = 'tag')
    {
        if (!$contents) {
            return array();
        }
        if (is_array($contents)) {
            return array();
        }
        if (!function_exists('xml_parser_create')) {
            // print "'xml_parser_create()' function not found!";
            return array();
        }
        // Get the XML parser of PHP - PHP must have this module for the parser to work
        $parser = xml_parser_create('');
        xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, "UTF-8"); // http://minutillo.com/steve/weblog/2004/6/17/php-xml-and-character-encodings-a-tale-of-sadness-rage-and-data-loss
        xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
        xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
        xml_parse_into_struct($parser, trim($contents), $xml_values);
        xml_parser_free($parser);
        if (!$xml_values) {
            return; //Hmm...
        }
        // Initializations
        $xml_array = array();
//        $parents = array();
//        $opened_tags = array();
//        $arr = array();
        $current = &$xml_array; //Refference
        // Go through the tags.
        $repeated_tag_index = array(); //Multiple tags with same name will be turned into an array
        $attributes = array();
        $value = array();
        $parent = array();
        $type = '';
        $level = '';
        $tag = '';
        foreach ($xml_values as $data) {
            unset($attributes, $value); //Remove existing values, or there will be trouble
            // This command will extract these variables into the foreach scope
            // tag(string), type(string), level(int), attributes(array).
            extract($data); //We could use the array by itself, but this cooler.
            $result = array();
            $attributes_data = array();
            if (isset($value)) {
                if ($priority == 'tag') {
                    $result = $value;
                } else {
                    $result['value'] = $value; //Put the value in a assoc array if we are in the 'Attribute' mode
                }
            }
            // Set the attributes too.
            if (isset($attributes) and $get_attributes) {
                foreach ($attributes as $attr => $val) {
                    if ($attr == 'ResStatus') {
                        $current[$attr][] = $val;
                    }
                    if ($priority == 'tag') {
                        $attributes_data[$attr] = $val;
                    } else {
                        $result['attr'][$attr] = $val; //Set all the attributes in a array called 'attr'
                    }
                }
            }
            // See tag status and do the needed.
            //echo"<br/> Type:".$type;
            if ($type == "open") { //The starting of the tag '<tag>'
                $parent[$level - 1] = &$current;
                if (!is_array($current) or (!in_array($tag, array_keys($current)))) { //Insert New tag
                    $current[$tag] = $result;
                    if ($attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    $current = &$current[$tag];
                } else { //There was another element with the same tag name
                    if (isset($current[$tag][0])) { //If there is a 0th element it is already an array
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else { //This section will make the value an array if multiple tags with the same
                        // name appear together
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //This will combine the existing item and the new item together to make an array
                        $repeated_tag_index[$tag . '_' . $level] = 2;
                        //The attribute of the last(0th) tag must be moved as well
                        if (isset($current[$tag . '_attr'])) {
                            $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                            unset($current[$tag . '_attr']);
                        }
                    }
                    $last_item_index = $repeated_tag_index[$tag . '_' . $level] - 1;
                    $current = &$current[$tag][$last_item_index];
                }
            } elseif ($type == "complete") { //Tags that ends in 1 line '<tag />'
                // See if the key is already taken.
                if (!isset($current[$tag])) { //New Key
                    $current[$tag] = $result;
                    $repeated_tag_index[$tag . '_' . $level] = 1;
                    if ($priority == 'tag' and $attributes_data) {
                        $current[$tag . '_attr'] = $attributes_data;
                    }
                } else { //If taken, put all things inside a list(array)
                    if (isset($current[$tag][0]) and is_array($current[$tag])) { //If it is already an array...
                        // ...push the new element into that array.
                        $current[$tag][$repeated_tag_index[$tag . '_' . $level]] = $result;
                        if ($priority == 'tag' and $get_attributes and $attributes_data) {
                            $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                        }
                        $repeated_tag_index[$tag . '_' . $level]++;
                    } else { //If it is not an array...
                        $current[$tag] = array(
                            $current[$tag],
                            $result
                        ); //...Make it an array using using the existing value and the new value
                        $repeated_tag_index[$tag . '_' . $level] = 1;
                        if ($priority == 'tag' and $get_attributes) {
                            if (isset($current[$tag . '_attr'])) {
                                $current[$tag]['0_attr'] = $current[$tag . '_attr'];
                                unset($current[$tag . '_attr']);
                                //The attribute of the last(0th) tag must be moved as well
                            }
                            if ($attributes_data) {
                                $current[$tag][$repeated_tag_index[$tag . '_' . $level] . '_attr'] = $attributes_data;
                            }
                        }
                        $repeated_tag_index[$tag . '_' . $level]++; //0 and 1 index is already taken
                    }
                }
            } elseif ($type == 'close') { //End of tag '</tag>'
                $current = &$parent[$level - 1];
            }
        }
        return ($xml_array);
    }

    /**
     * @param string $hierarchy
     * @param string $max_level
     * @return array
     */
    public function getElkjopnordicAttributes($hierarchy = '', $max_level = '')
    {
        $response = array();
        $response = $this->WGetRequest('products/attributes', array('hierarchy' => $hierarchy,
            'max_level' => $max_level), 'json');
        if (isset($response['success']) && $response['success'] && $response['response']) {
            return array('success' => true, 'message' => $response['response']);
        } else {
            return array('success' => false, 'message' => $response['message']);
        }
    }

    /**
     * @param $url
     * @param string $post_field
     * @param array $params
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function WPutRequest($url, $post_field = array(), $params = array())
    {
        $this->log(
            __METHOD__,
            'Info',
            'Starting PUT request for ',
            json_encode(
                array(
                    'url'=>$url,
                    'Request Param' => $params,
                    'Response' => ''
                )
            ),
            true
        );
        $enable = $this->isEnabled();
        if ($enable) {
            $url = Configuration::get('ELKJOPNORDIC_API_URL') . $url;
            $this->log(
                __METHOD__,
                'Info',
                'Starting PUT request for ',
                json_encode(
                    array(
                        'url'=>$url,
                        'Request Param' => $params,
                        'Response' => ''
                    )
                ),
                true
            );
            $this->log(
                __METHOD__,
                'Info',
                'POST FIELD',
                json_encode(
                    array(
                        'url'=>$url,
                        'Request Param' => $params,
                        'Response' => ''
                    )
                ),
                true
            );
            $ch = curl_init($url);

            $headers = array();

            $headers[] = "Authorization: $this->user";

            if (isset($params['file']) && !empty($params['file'])) {
                $headers[] = "Content-Type: multipart/form-data;";
            } elseif (isset($params['data']) && !empty($params['data'])) {
                $headers[] = "Content-Type: application/xml";
            } else {
                $headers[] = "Content-Type: application/json";
            }
            $headers[] = "Accept: application/xml";
            // $curlError = false;
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_field));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $server_output = curl_exec($ch);
            curl_error($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $body = Tools::substr($server_output, $header_size);


            if (!curl_errno($ch)) {
                $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $this->log(
                    __METHOD__,
                    'Info',
                    'Response PUT request for ',
                    json_encode(
                        array(
                            'url'=>$url,
                            'Request Param' => $params,
                            'Response' => $http_code
                        )
                    ),
                    true
                );
                if ($http_code == 204) {
                    curl_close($ch);
                    return array('success' => true, 'response' => $server_output);
                } else {
                    curl_close($ch);
                    $this->log(
                        __METHOD__,
                        'Info',
                        'Response PUT request for ',
                        json_encode(
                            array(
                                'url'=>$url,
                                'Request Param' => $params,
                                'Response' => $body
                            )
                        ),
                        true
                    );
                    return array('success' => false, 'message' => $body);
                }
            }
        } else {
            $this->log(
                __METHOD__,
                'Info',
                'Response PUT request for ',
                json_encode(
                    array(
                        'url'=>$url,
                        'Request Param' => $params,
                        'Response' => ''
                    )
                ),
                true
            );
            return array('success' => false, 'message' => 'Module is not enable.');
        }
    }


    /**
     * @param $product
     * @return int
     * @throws PrestaShopDatabaseException
     */
    public function getElkjopnordicQuantity($product)
    {
        $quantity = 0;
        $db = Db::getInstance();
        $result = $db->ExecuteS("SELECT `quantity` FROM `" . _DB_PREFIX_ . "product` where `id_product` = '" .
            (int)$product . "'");
        if (is_array($result) && isset($result['0']['quantity'])) {
            $quantity = $result['0']['quantity'];
        }
        return $quantity;
    }

    /**
     * @param $product_id
     * @return array|bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getVariantProducts($product_id)
    {
        if ($product_id) {
            $product = new Product($product_id);
            $default_lang = Configuration::get('PS_LANG_DEFAULT');
            $combinations = $product->getAttributeCombinations($default_lang);
            if (count($combinations)) {
                return $combinations;
            }
        }
        return false;
    }

    /**
     * @param int $product_id
     * @return string
     * @throws PrestaShopDatabaseException
     */
    public function getSku($product_id = 0)
    {
        if ($product_id) {
            $db = Db::getInstance();
            $sku = '';
            $sql = "SELECT `reference` FROM `" . _DB_PREFIX_ . "product` where `id_product`='" . (int)$product_id . "'";
            $result = $db->ExecuteS($sql);
            if (is_array($result) && count($result) && isset($result['0']['reference'])) {
                $sku = $result['0']['reference'];
            }
            return $sku;
        }
    }

    /**
     * @param $product_id
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getElkjopnordicPrice($product_id)
    {
        $specialPrice = 0;
        $db = Db::getInstance();
        $Execute_price = $db->ExecuteS("SELECT `price` FROM `" . _DB_PREFIX_ . "product` WHERE `id_product` = '" .
            (int)$product_id . "'");
        $product_price = 0;
        if (is_array($Execute_price) && count($Execute_price) && isset($Execute_price['0']['price'])) {
            $Elkjopnordic_CURRENCY_CONVERTER_RATE = (float)Configuration::get('ELKJOPNORDIC_CURRENCY_CONVERTER_RATE');
            $product_price = $Execute_price['0']['price'];
        }

        $price = (float)$product_price;

        if ($specialPrice == 0) {
            $specialPrice = $price;
        }

        $Elkjopnordic_PRICE_VARIANT_TYPE = trim(Tools::getValue(
            'Elkjopnordic_PRICE_VARIANT_TYPE'
        ));

        switch ($Elkjopnordic_PRICE_VARIANT_TYPE) {
            case '2':
                $fixedIncement = trim(Configuration::get('ELKJOPNORDIC_PRICE_VARIANT_AMOUNT'));
                $price = $price + $fixedIncement;
                $specialPrice = $specialPrice + $fixedIncement;
                break;

            case '3':
                $fixedIncement = trim(Configuration::get('ELKJOPNORDIC_PRICE_VARIANT_AMOUNT'));
                $price = $price - $fixedIncement;
                $specialPrice = $specialPrice + $fixedIncement;
                break;


            case '4':
                $percentPrice = trim(Configuration::get('ELKJOPNORDIC_PRICE_VARIANT_AMOUNT'));
                $price = (float)($price + (($price / 100) * $percentPrice));
                $specialPrice = (float)($specialPrice + (($specialPrice / 100) * $percentPrice));
                break;

            case '5':
                $percentPrice = trim(Configuration::get('ELKJOPNORDIC_PRICE_VARIANT_AMOUNT'));
                $price = (float)($price - (($price / 100) * $percentPrice));
                $specialPrice = (float)($specialPrice - (($specialPrice / 100) * $percentPrice));
                break;

            default:
                return array(
                    'price' => (string)$price * $Elkjopnordic_CURRENCY_CONVERTER_RATE,
                    'specialPrice' => (string)$specialPrice * $Elkjopnordic_CURRENCY_CONVERTER_RATE,
                );
        }
        return array(
            'price' => (string)$price * $Elkjopnordic_CURRENCY_CONVERTER_RATE,
            'specialPrice' => (string)$specialPrice * $Elkjopnordic_CURRENCY_CONVERTER_RATE,
        );
    }

    /**
     * @param $feed_id
     * @return bool|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getPriceFeedById($feed_id)
    {
        if ($feed_id) {
            $db = Db::getInstance();
            $result = $db->ExecuteS("SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_price_feed` where `import_id`='" .
                pSQL($feed_id). "'");
            if (is_array($result) && count($result)) {
                return $result['0'];
            } else {
                return false;
            }
        }
    }

    /**
     * Build an XML Data Set
     *
     * @param array $data Associative Array containing values to be parsed into an XML Data Set(s)
     * @param string $startElement Root Opening Tag, default data
     * @return string XML String containing values
     * @return mixed Boolean false on failure, string XML result on success
     */
    public function buildXML($data, $path)
    {
        if (!is_array($data)) {
            $err = 'Invalid variable type supplied, expected array not found on line ' . __LINE__ .
                ' in Class: ' . __CLASS__ . ' Method: ' . __METHOD__;
            trigger_error($err);
            return false;
        }
        $xml = new XmlWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->startDocument('1.0', 'UTF-8');
        $this->writeAttr($xml, $data);
        $this->writeEl($xml, $data);
        $xml->endElement();
        $content = $xml->outputMemory(true);
        $content = $this->replaceProblemCharacters($content);
        file_put_contents($path, $content);
    }

    /**
     * Write keys in $data prefixed with @ as XML attributes, if $data is an array.
     * When an @ prefixed key is found, a '%' key is expected to indicate the element itself,
     * and '#' prefixed key indicates CDATA content
     *
     * @param XMLWriter $xml object
     * @param array $data with attributes filtered out
     * @return array $data | $nonAttributes
     */
    protected function writeAttr(XMLWriter $xml, $data)
    {
        if (is_array($data)) {
            $nonAttributes = array();
            foreach ($data as $key => $val) {
                //handle an attribute with elements
                if ($key[0] == '@') {
                    $xml->writeAttribute(Tools::substr($key, 1), $val);
                } elseif ($key[0] == '%') {
                    if (is_array($val)) {
                        $nonAttributes = $val;
                    } else {
                        $xml->text($val);
                    }
                } elseif ($key[0] == '#') {
                    if (is_array($val)) {
                        $nonAttributes = $val;
                    } else {
                        $xml->startElement(Tools::substr($key, 1));
                        $xml->writeCData($val);
                        $xml->endElement();
                    }
                } else {
                    $nonAttributes[$key] = $val;
                }
            }
            return $nonAttributes;
        } else {
            return $data;
        }
    }

    /**
     * Write XML as per Associative Array
     *
     * @param XMLWriter $xml object
     * @param array $data Associative Data Array
     */
    protected function writeEl(XMLWriter $xml, $data)
    {
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0])) { //numeric array
                foreach ($value as $itemValue) {
                    if (is_array($itemValue)) {
                        $xml->startElement($key);
                        $itemValue = $this->writeAttr($xml, $itemValue);
                        $this->writeEl($xml, $itemValue);
                        $xml->endElement();
                    } else {
                        $itemValue = $this->writeAttr($xml, $itemValue);
                        $xml->writeElement($key, "$itemValue");
                    }
                }
            } elseif (is_array($value)) { //associative array
                $xml->startElement($key);
                $value = $this->writeAttr($xml, $value);
                $this->writeEl($xml, $value);
                $xml->endElement();
            } else { //scalar
                $value = $this->writeAttr($xml, $value);
                $xml->writeElement($key, "$value");
            }
        }
    }

    /**
     * @param $text
     * @return mixed
     */
    protected function replaceProblemCharacters($text)
    {

        $text = str_replace('&lt;', '<', $text);
        $text = str_replace('&gt;', '>', $text);
        $text = str_replace('&amp;', '&', $text);
        return $text;
    }

    /**
     * @param $url
     * @param array $params
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function WPostRequest($url, $params = array())
    {
        $enable = $this->isEnabled();
        if ($enable) {
            try {
                $url = Configuration::get('ELKJOPNORDIC_API_URL') . $url;

                $body = array();
                if (isset($params['file'])) {
                    if (function_exists('curl_file_create')) {
                        $cFile = curl_file_create($params['file']);
                    } else {
                        $cFile = '@' . realpath($params['file']);
                    }
                    $body = array('file' => $cFile);
                    if (isset($params['import_mode'])) {
                        $body['import_mode'] = $params['import_mode'];
                    }
                } elseif (isset($params['data'])) {
                    $body = json_encode($params['data']);
                }

                $headers = array();

                $headers[] = "Authorization: $this->user";

                if (isset($params['file']) && (!empty($params['file']))) {
                    $headers[] = "Content-Type: multipart/form-data";
                } elseif (isset($params['data']) && !empty($params['data'])) {
                    $headers[] = "Content-Type: application/json";
                } else {
                    $headers[] = "Content-Type: application/json";
                }
                $headers[] = "Accept: application/json";

                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $url);
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                $server_output = curl_exec($ch);

                //   $curlError = curl_error($ch);

                $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
                //  $header = Tools::substr($server_output, 0, $header_size);

                $body = Tools::substr($server_output, $header_size);

                if (!curl_errno($ch)) {
                    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    if ($http_code == 201) {
                        return array('success' => true, 'response' => $server_output);
                    } else {
                        if ($body) {
                            $this->log(
                                __METHOD__,
                                'Info',
                                'Response Post request for ',
                                json_encode(
                                    array(
                                        'url'=>$url,
                                        'Request Param' => $params,
                                        'Response' => $server_output
                                    )
                                ),
                                true
                            );
                            return array('success' => false, 'message' => $body);
                        } else {
                            $this->log(
                                __METHOD__,
                                'Info',
                                'Response Post request for ',
                                json_encode(
                                    array(
                                        'url'=>$url,
                                        'Request Param' => $params,
                                        'Response' => $server_output
                                    )
                                ),
                                true
                            );
                            return array('success' => false, 'message' => $server_output);
                        }
                    }
                }
                curl_close($ch);
            } catch (Exception $e) {
                $this->log(
                    __METHOD__,
                    'Exception',
                    $e->getMessage(),
                    json_encode(
                        array(
                            'url'=>$url,
                            'Request Param' => $params,
                            'Response' => $server_output
                        )
                    ),
                    true
                );
                return array('success' => false, 'message' => $e->getMessage());
            }
        } else {
            $this->log(
                __METHOD__,
                'Info',
                'Response Post request for ',
                json_encode(
                    array(
                        'url'=>$url,
                        'Request Param' => $params,
                        'Response' => ''
                    )
                ),
                true
            );
            return array('success' => false, 'message' => 'Module is not enable.');
        }
    }

    public function getOrderStatusId($name)
    {
        return Configuration::get($name);
    }

    /**
     * @param $order_id
     * @param $id_order_state
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function addOrderHistory($order_id, $id_order_state)
    {
        //  $current_order_state = 0;
        $order_state = new OrderState($id_order_state);
        $order = new Order((int)$order_id);
        $order->getCurrentOrderState();
        $history = new OrderHistory();
        $history->id_order = $order->id;
        $history->id_employee = (int)Context::getContext()->employee->id;
        $use_existings_payment = !$order->hasInvoice();
        $history->changeIdOrderState((int)$order_state->id, $order, $use_existings_payment);
        if (Configuration::get('PS_ADVANCED_STOCK_MANAGEMENT')) {
            foreach ($order->getProducts() as $product) {
                if (StockAvailable::dependsOnStock($product['product_id'])) {
                    StockAvailable::synchronize($product['product_id'], (int)$product['id_shop']);
                }
            }
        }
    }

    /**
     * @return array
     */
    public function getOfferAdditionalFields()
    {
        $response = $this->WGetRequest('additional_fields', array('entities' => 'OFFER'), 'json');
        if (isset($response['success']) && $response['success'] && $response['response']) {
            return array('success' => true, 'message' => $response['response']);
        } else {
            return array('success' => false, 'message' => $response['message']);
        }
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getFeatures()
    {
        $db = Db::getInstance();
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $sql = "SELECT `id_feature`,`name` FROM `" . _DB_PREFIX_ . "feature_lang` 
        where `id_lang`='" . (int)$default_lang . "'";
        $result = $db->ExecuteS($sql);
        $result = (array)$result;
        if (count($result)) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * @return array|false|mysqli_result|null|PDOStatement|resource
     * @throws PrestaShopDatabaseException
     */
    public static function getAttributes()
    {
        $db = Db::getInstance();
        $default_lang = (int)Configuration::get('PS_LANG_DEFAULT');
        $sql = "SELECT `id_attribute_group` as `id_attribute`,`name` 
        FROM `" . _DB_PREFIX_ . "attribute_group_lang` where `id_lang`='" . (int)$default_lang . "'";
        $result = $db->ExecuteS($sql);
        if (is_array($result) && count($result)) {
            return $result;
        } else {
            return array();
        }
    }

    /**
     * @return array
     */
    public static function getSystemAttributes()
    {
        $languages = Language::getLanguages();
        $default_attributes = array();
        foreach ($languages as $language){
            $default_attributes['name-'.$language['id_lang']] = 'Name '.$language['language_code'];
        }
        foreach ($languages as $language){
            $default_attributes['description_short-'.$language['id_lang']] = 'Short Description '.$language['language_code'];
        }
        foreach ($languages as $language){
            $default_attributes['description-'.$language['id_lang']] = 'Long Description '.$language['language_code'];
        }

        $default_attributes['ean13'] = 'EAN';
        $default_attributes['id_manufacturer'] = 'Manufacture';
        $default_attributes['width']     = 'Assembled_width';
        $default_attributes['height'] = 'Assembled_height';
        $default_attributes['length'] = 'Assembled_length';
        $default_attributes['weight'] = 'Weight_KG';
        $default_attributes['elcategory'] = 'Category';

        return $default_attributes;

    }
}
