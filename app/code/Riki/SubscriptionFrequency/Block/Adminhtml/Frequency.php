<?php
/**
 * SubscriptionFrequency
 *
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\SubscriptionFrequency\Block\Adminhtml;

/**
 * Class Frequency
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Block\Adminhtml
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Frequency extends \Magento\Backend\Block\Widget\Grid\Container
{
    /**
     * Construct
     *
     * @return mixed
     */
    protected function _construct() // @codingStandardsIgnoreLine
    {
        $this->_blockGroup = 'Riki_SubscriptionFrequency';
        $this->_controller = 'adminhtml';
        $this->_headerText = __('Frequency');
        $this->_addButtonLabel = __('Add New Frequency');
        parent::_construct();
    }
}
