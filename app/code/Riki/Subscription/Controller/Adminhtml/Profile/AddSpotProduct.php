<?php

namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\Registry;
use Mirasvit\FraudCheck\Model\RuleFactory;
use Riki\Subscription\Model\Profile\ProfileFactory;
use Riki\SubscriptionCourse\Model\Course\Type as SubscriptionType;

class AddSpotProduct extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $helperProfile;

    /**
     * @var ProfileFactory
     */
    protected $profileFactory;

    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultRawFactory;

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * AddSpotProduct constructor.
     * @param Context $context
     * @param \Magento\Framework\View\Result\PageFactory $pageFactory
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
     * @param \Riki\Subscription\Helper\Profile\Data $helperProfile
     * @param \Magento\Framework\Controller\Result\RawFactory $resultRawFactory
     * @param ProfileFactory $profileFactory
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
        \Riki\Subscription\Helper\Profile\Data $helperProfile,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        ProfileFactory $profileFactory,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
    ) {
        $this->resultPageFactory = $pageFactory;
        $this->productRepository = $productRepository;
        $this->searchCriteriaBuilder  = $searchCriteriaBuilder;
        $this->helperProfile = $helperProfile;
        $this->profileFactory = $profileFactory;
        $this->resultRawFactory = $resultRawFactory;
        $this->courseFactory = $courseFactory;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect|\Magento\Framework\View\Result\Page|\Magento\Framework\Controller\Result\Raw
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        $profileId = $this->getRequest()->getParam('id');
        if (!$profileId) {
            $this->messageManager->addError(__('The subscription profile no longer exists'));
            return $resultRedirect->setPath('customer/index');
        }
        $profile = $this->profileFactory->create()->load($profileId);
        if (!$profile->getId()) {
            $this->messageManager->addError(__('The subscription profile no longer exists'));
            return $resultRedirect->setPath('customer/index');
        }

        // If profile is monthly fee, don't allow add spot product
        $courseModel = $this->courseFactory->create()->load($profile->getCourseId());
        if ($courseModel->getData('subscription_type') == SubscriptionType::TYPE_MONTHLY_FEE) {
            $this->messageManager->addError(__('Can\'t add product to sub profile'));
            return $resultRedirect->setPath(
                'customer/index/edit',
                ['id' => $profile->getCustomerId(), '_current' => true]
            );
        }

        if ($this->helperProfile->isTmpProfileId($profileId, $profile)) {
            return $resultRedirect->setPath(
                'customer/index/edit',
                ['id' => $profile->getCustomerId(), '_current' => true]
            );
        }
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->prepend(__('定期便同梱 商品検索'));

        //load gird ajax
        if ($this->getRequest()->getParam('isAjax')) {
            $resultPage->addHandle('profile_profile_addspotproduct_grid');
            $result = $resultPage->getLayout()->renderElement('content');
            return $this->resultRawFactory->create()->setContents($result);
        }

        return $resultPage;
    }

    /**
     * @return bool
     */
    public function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::add_spot_product');
    }
}
