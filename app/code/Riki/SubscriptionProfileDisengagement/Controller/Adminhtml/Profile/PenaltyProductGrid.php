<?php
namespace Riki\SubscriptionProfileDisengagement\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class PenaltyProductGrid extends \Magento\Backend\App\Action
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    protected $_reasonFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $_profileData;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_registry;

    /**
     * @param Context $context
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Magento\Framework\Registry $registry
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Magento\Framework\Registry $registry,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);

        $this->_profileData = $profileData;
        $this->_registry = $registry;
        $this->resultPageFactory = $resultPageFactory;
    }

    public function execute()
    {
        $profileId = $this->getRequest()->getParam('id', 0);

        /** @var \Riki\Subscription\Model\Profile\Profile $objProfile */
        $objProfile  = $this->_profileData->load($profileId);

        $this->_registry->register('subscription_profile_obj', $objProfile);

        $resultLayout = $this->resultPageFactory->create();
        return $resultLayout;
    }

    /**
     *
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionProfileDisengagement::profile_disengage');
    }
}