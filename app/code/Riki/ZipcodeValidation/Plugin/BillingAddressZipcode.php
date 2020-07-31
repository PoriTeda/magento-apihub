<?php
/**
 * ZipcodeValidation
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ZipcodeValidation
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ZipcodeValidation\Plugin;

/**
 * BillingAddressZipcode
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ZipcodeValidation
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class BillingAddressZipcode
{
    /**
     * Change zip code component to custom component
     *
     * @param \Magento\Checkout\Block\Checkout\LayoutProcessor $subject  Checkout LayoutProcessor
     * @param array                                            $jsLayout array
     *
     * @return array
     */
    public function afterProcess(
        \Magento\Checkout\Block\Checkout\LayoutProcessor $subject,
        array $jsLayout
    ) {
        if (isset($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']['payment']['children'])) {
            
            foreach ($jsLayout['components']['checkout']['children']['steps']['children']['billing-step']['children']
                     ['payment']['children']['payments-list']['children'] as &$paymentComponent) {
                if (isset($paymentComponent['children']['form-fields']['children'])) {
                    $paymentComponent['children']['form-fields']['children']['postcode']['component'] = 'Riki_ZipcodeValidation/js/form/element/post-code-checkout';
                    $paymentComponent['children']['form-fields']['children']['postcode']['validation'] = array_merge_recursive(
                        $paymentComponent['children']['form-fields']['children']['postcode']['validation'],
                        ['validate-custom-postal-code' => true]
                    );
                }
            }
        }
        
        return $jsLayout;
    }
}