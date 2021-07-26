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

class CedElkjopnordicProfile extends ObjectModel
{
    public static $definition = array(
        'table' => 'cedelkjopnordic_profile',
        'primary' => 'id',
        'multilang' => false,
        'fields' => array(
            'id' => array(
                'title' => 'Profile ID',
                'required' => true,
                'type' => self::TYPE_STRING,

            ),
            'title' => array(
                'title' => 'Title',
                'type' => self::TYPE_STRING,
                'required' => true,
                'validate' => 'isGenericName',
            ),
            'status' => array(
                'title' => 'Status',
                'type' => self::TYPE_STRING,
                'required' => true,
                'validate' => 'isGenericName',
            ),
        ),
    );

    /**
     * @param $profile_id
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getProfileDataById($profile_id)
    {
        $db = Db::getInstance();
        $profileData = array(
            'profileInfo' => array(
                'profileTitle' => '',
                'profileStatus' => '',
                'profileManufacturer' => array()
            ),
            'profileElkjopnordicCategories' => array(),
            'profileStoreCategories' => array(),
            'profileAttributes' => array(),
            'profileAdditionalInfo' => array(),
        );
        $sql = "SELECT * FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile` WHERE `id`=" . (int)$profile_id;
        $result = $db->executeS($sql);
        if (isset($result[0]) && $result[0] && is_array($result[0])) {
            $data = $result[0];
            $profileData['profileInfo']['profileTitle'] = isset($data['title']) ? $data['title'] : '';
            $profileData['profileInfo']['profileStatus'] = isset($data['status']) ? $data['status'] : '';

            if (isset($data['product_manufacturer']) && !empty($data['product_manufacturer'])) {
                $profileData['profileInfo']['profileManufacturer'] = json_decode($data['product_manufacturer'], true);
            }

            if (isset($data['elkjopnordic_categories']) && !empty($data['elkjopnordic_categories'])) {
                $profileData['profileElkjopnordicCategories'] = json_decode($data['elkjopnordic_categories'], true);
            }

            if (isset($data['store_category']) && !empty($data['store_category'])) {
                $profileData['profileStoreCategories'] = json_decode($data['store_category'], true);
            }

            if (isset($data['profile_attribute_mapping']) && !empty($data['profile_attribute_mapping'])) {
                $profileData['profileAttributes'] = json_decode($data['profile_attribute_mapping'], true);
            }

            if (isset($data['profile_additional_info']) && !empty($data['profile_additional_info'])) {
                $profileData['profileAdditionalInfo'] = json_decode($data['profile_additional_info'], true);
            }
        }
        return $profileData;
    }

    /**
     * @param $profile_id
     * @return array
     */
    public function getProfileCategoryById($profile_id)
    {
        try {
            $db = Db::getInstance();
            $sql = "SELECT `elkjopnordic_categories` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile` WHERE `id`=" .
                (int)$profile_id;
            $result = $db->executeS($sql);
            if (isset($result[0]['elkjopnordic_categories']) && $result[0]['elkjopnordic_categories']) {
                $elkjopnordic_categories = $result[0]['elkjopnordic_categories'];
                $elkjopnordic_categories = json_decode($elkjopnordic_categories, true);
                $level = count($elkjopnordic_categories);
                $elkjopnordic_category = $elkjopnordic_categories['level_' . $level];
                return array('success' => true, 'message' => $elkjopnordic_category);
            } else {
                return array('success' => false, 'message' => 'Category not found');
            }
        } catch (\Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }

    /**
     * @param $id
     * @return bool
     */
    public function deleteProfile($id)
    {
        $db = Db::getInstance();
        if (!empty($id)) {
            $res = $db->delete(
                'cedelkjopnordic_profile_products',
                'id_cedelkjopnordic_profile=' . (int)$id
            );
            if ($res) {
                $res = $db->delete(
                    'cedelkjopnordic_profile',
                    'id=' . (int)$id
                );
            }
            if ($res) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param $profile_id
     * @return array|mixed
     * @throws PrestaShopDatabaseException
     */
    public function getMappedElkjopnordicCategoryById($profile_id)
    {
        $mapped_elkjopnordic_categories = array();
        $db = Db::getInstance();
        $sql = "SELECT `elkjopnordic_categories` FROM `" . _DB_PREFIX_ . "cedelkjopnordic_profile` WHERE `id`=" . (int)$profile_id;
        $result = $db->executeS($sql);

        if (isset($result[0]) && $result[0]) {
            $mapped_elkjopnordic_categories = json_decode($result[0], true);
        }
        return $mapped_elkjopnordic_categories;
    }

    /**
     * @param array $profileData
     * @return array
     */
    public function validateProfile($profileData = array())
    {
        $errors = array();
        $valid = true;

        if (!isset($profileData['profileTitle']) || empty($profileData['profileTitle'])) {
            $valid = false;
            $errors[] = 'Profile Name Is Required';
        }
		
        if (!isset($profileData['elkjopnordicCategory']['level_1']) || empty($profileData['elkjopnordicCategory']['level_1'])) {
            $valid = false;
            $errors[] = 'Please Map Elkjopnordic Leaf Category';
        }

        if (!isset($profileData['profileAttributes'])
            || !is_array($profileData['profileAttributes'])
            || empty($profileData['profileAttributes'])
        ) {
            $valid = false;
            $errors[] = 'Please Map Elkjopnordic Attributes With Prestashop Attributes';
        }

        if (!isset(
            $profileData['profileelkjopnordicInfo']['product_offer_state'],
            $profileData['profileelkjopnordicInfo']['product_reference_type'],
            $profileData['profileelkjopnordicInfo']['shippingtime'],
            $profileData['profileelkjopnordicInfo']['min_quantity_alert']
        ) || empty($profileData['profileelkjopnordicInfo']['product_offer_state'])
            || empty($profileData['profileelkjopnordicInfo']['product_reference_type'])
            || empty($profileData['profileelkjopnordicInfo']['shippingtime'])
            || empty($profileData['profileelkjopnordicInfo']['product_offer_state'])) {
            $valid = false;
            $errors[] = 'Please Fill Elkjopnordic Settings';
        }

        return array(
            'valid' => $valid,
            'errors' => $errors
        );
    }

    /**
     * @return array
     */
    public function getVariantAttributes()
    {
        $variant_array = array(
            'variant-size-value',
            'variant-colour-value'
        );

        return $variant_array;
    }

    /**
     * @return array
     */
    public function productOfferState()
    {
        $product_offer_state = array(
            array('value' => '11', 'label' => 'New'),
            array('value' => '10', 'label' => 'Refurbished'));

        return $product_offer_state;
    }

    /**
     * @return array
     */
    public function productReferenceType()
    {
        $product_reference_type = array(
            array('value' => 'EAN', 'label' => 'EAN'),
            array('value' => 'SHOP_SKU', 'label' => 'SHOP_SKU'),
            array('value' => 'MPN', 'label' => 'MPN'),
            array('value' => 'UPC', 'label' => 'UPC')
        );
        return $product_reference_type;
    }

    /**
     * @return array
     */
    public function productLogisticClass()
    {
        $product_logistic_class = array(
            array('value' => 'FLAT', 'label' => 'Flat Rate'),
            array('value' => 'FREE', 'label' => 'FREE'),
            array('value' => 'SLW', 'label' => 'Small - Light Weight'),
            array('value' => 'SMW', 'label' => 'Small - Medium Weight'),
            array('value' => 'SHW', 'label' => 'Small - Heavy Weight'),
            array('value' => 'MLW', 'label' => 'Medium - Light Weight'),
            array('value' => 'MMW', 'label' => 'Medium - Medium Weight'),
            array('value' => 'MHW', 'label' => 'Medium - Heavy Weight'),
            array('value' => 'MSHW', 'label' => 'Medium - Super Heavy Weight'),
            array('value' => 'LLW', 'label' => 'Large - Light Weight'),
            array('value' => 'LMW', 'label' => 'Large - Medium Weight'),
            array('value' => 'LHW', 'label' => 'Large - Heavy Weight'),
            array('value' => 'LSHW', 'label' => 'Large - Super Heavy Weight'),
            array('value' => 'L2MC', 'label' => 'Large - 2 Men Carry'),
            array('value' => 'OLW', 'label' => 'Oversize - Light Weight'),
            array('value' => 'OMW', 'label' => 'Oversize - Medium Weight'),
            array('value' => 'OHW', 'label' => 'Oversize - Heavy Weight'),
            array('value' => 'OSHW', 'label' => 'Oversize - Super Heavy Weight'),
            array('value' => 'O2MC', 'label' => 'Oversize - 2 Men Carry'),
            array('value' => 'SOA', 'label' => 'Super Oversize - A'),
            array('value' => 'SOB', 'label' => 'Super Oversize - B')
        );

        return $product_logistic_class;
    }

    /**
     * @return array
     */
    public function elkjopnordicClubEligible()
    {
        $shippingtime = array(
            array('value' => 'false', 'label' => 'No'),
            array('value' => 'true', 'label' => 'Yes')
        );

        return $shippingtime;
    }
}
