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
 * Class Index
 *
 * @category  RIKI
 * @package   Riki\SubscriptionFrequency\Controller\Adminhtml\Index
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Index extends \Riki\SubscriptionFrequency\Controller\Adminhtml\Frequency
{
    /**
     * ResultPageFactory
     *
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * Index constructor.
     *
     * @param \Magento\Backend\App\Action\Context        $context           Context
     * @param \Magento\Framework\Registry                $coreRegistry      CoreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory ResultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->resultPageFactory = $resultPageFactory;
        parent::__construct($context, $coreRegistry);
    }

    /**
     * IsAllowed
     *
     * @return bool
     */
    protected function _isAllowed() // @codingStandardsIgnoreLine
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionFrequency::frequency');
    }


    /**
     * Execute
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        /**
         * ResultPage
         *
         * @var \Magento\Backend\Model\View\Result\Page $resultPage ResultPage
         */
        $resultPage = $this->resultPageFactory->create();
        $this->initPage($resultPage)->getConfig()->getTitle()->prepend(__('Frequency management'));
        return $resultPage;
    }
}
