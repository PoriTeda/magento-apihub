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
namespace Riki\SubscriptionFrequency\Controller\Adminhtml\Index;

/**
 * Class NewAction
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Controller\Adminhtml\Index
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class NewAction extends \Riki\SubscriptionFrequency\Controller\Adminhtml\Frequency
{
    /**
     * ResultForwardFactory
     *
     * @var \Magento\Backend\Model\View\Result\ForwardFactory
     */
    protected $resultForwardFactory;

    /**
     * NewAction constructor.
     *
     * @param \Magento\Backend\App\Action\Context               $context              Context
     * @param \Magento\Framework\Registry                       $coreRegistry         CoreRegistry
     * @param \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory ResultForwardFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory
    ) {
        $this->resultForwardFactory = $resultForwardFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * IsAllowed
     *
     * @return bool
     */
    protected function _isAllowed() // @codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionFrequency::save');
    }

    /**
     * Execute
     *
     * @return $this
     */
    public function execute()
    {
        /**
         * ResultForward
         *
         * @var \Magento\Framework\Controller\Result\Forward $resultForward ResultForward
         */
        $resultForward = $this->resultForwardFactory->create();
        return $resultForward->forward('edit');
    }
}
