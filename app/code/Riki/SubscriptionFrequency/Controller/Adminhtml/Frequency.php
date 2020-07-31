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
namespace Riki\SubscriptionFrequency\Controller\Adminhtml;

/**
 * Class Frequency
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Model\ResourceModel\Frequency\Grid
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
abstract class Frequency extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * Frequency constructor.
     *
     * @param \Magento\Backend\App\Action\Context $context      Context
     * @param \Magento\Framework\Registry         $coreRegistry CoreRegistry
     */
    public function __construct(\Magento\Backend\App\Action\Context $context, \Magento\Framework\Registry $coreRegistry)
    {
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context);
    }

    /**
     * InitPage
     *
     * @param object $resultPage ResultPage
     *
     * @return mixed
     */
    protected function initPage($resultPage)
    {
        $resultPage->setActiveMenu('Riki_SubscriptionFrequency::frequency')
            ->addBreadcrumb(__('Frequency'), __('Frequency'));
        return $resultPage;
    }

    /**
     * Check the permission to run it
     *
     * @return boolean
     */
    protected function _isAllowed() // @codingStandardsIgnoreLine
    {
        return true;
    }
}
