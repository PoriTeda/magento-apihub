<?php
/**
 * ShippingProvider
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */

namespace Riki\ShippingProvider\Block\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;

/**
 * Locations
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ShippingProvider
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Locations extends AbstractFieldArray
{
    /**
     * Initialise columns for 'Store Locations'
     * Label is name of field
     * Class is storefront validation action for field
     *
     * @return void
     */
    protected function _construct() // @codingStandardsIgnoreLine
    {
        $this->addColumn(
            'title',
            [
                'label' => __('Title'),
                'class' => 'validate-no-empty validate-alphanum-with-spaces'
            ]
        );
        $this->addColumn(
            'street',
            [
                'label' => __('Street Address'),
                'class' => 'validate-no-empty validate-alphanum-with-spaces'
            ]
        );
        $this->addColumn(
            'phone',
            [
                'label' => __('Phone Number'),
                'class' => 'validate-no-empty validate-no-empty validate-phoneStrict'
            ]
        );
        $this->addColumn(
            'message',
            [
                'label' => __('Message'),
                'class' => 'validate-no-empty'
            ]
        );
        $this->_addAfter = false;
        parent::_construct();
    }
}
