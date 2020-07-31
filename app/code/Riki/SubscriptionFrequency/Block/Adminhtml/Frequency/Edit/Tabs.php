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
namespace Riki\SubscriptionFrequency\Block\Adminhtml\Frequency\Edit;

/**
 * Class Tabs
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Block\Adminhtml\Frequency\Edit
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Tabs extends \Magento\Backend\Block\Widget\Tabs
{
    /**
     * Construct
     *
     * @return mixed
     */
    protected function _construct() // @codingStandardsIgnoreLine
    {
        parent::_construct();
        $this->setId('frequency_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(__('Frequency information'));
    }
}
