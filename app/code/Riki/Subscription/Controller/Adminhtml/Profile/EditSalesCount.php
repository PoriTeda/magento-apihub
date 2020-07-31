<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Riki\Subscription\Model\Profile\ProfileFactory;

class EditSalesCount extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;


    /* @var \Magento\Framework\Registry */
    protected $_registry;

    public function __construct
    (
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        ProfileFactory $profileFactory
    )
    {
        $this->_registry = $registry;
        $this->resultPageFactory = $pageFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->helperProfile = $helperProfile;
        $this->profileFactory = $profileFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $profileId = $this->getRequest()->getParam('id');
        $urlRefer = $this->_request->getServer('HTTP_REFERER');
        if(!$profileId){
            $this->messageManager->addError(__('The subscription profile no longer exists'));
            $this->_redirect($urlRefer);
            return;
        }
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addError(__('The subscription profile no longer exists'));
            $this->_redirect($urlRefer);
            return;
        }
        if($this->helperProfile->isTmpProfileId($profileId,$profile)){
            return $this->_redirect($urlRefer);
        }
        $resultPage = $this->resultPageFactory->create();
        $this->_registry->register('subscription-profile-id', $profileId);
        $resultPage->getConfig()->getTitle()->prepend(__('Edit Sales Count'));

        return $resultPage;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::edit_sales_count');
    }
}