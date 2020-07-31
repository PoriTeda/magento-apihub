<?php
namespace Riki\Subscription\Controller\Profile;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Riki\Subscription\Helper\Profile\Data as SubscriptionHelperProfileData;
use Riki\ThirdPartyImportExport\Ui\Component\Listing\Column\SubscriptionType;
use Riki\SubscriptionCourse\Model\Course\Type as CourseType;

class View extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $subHelperProfileData;

    public function __construct(
        SubscriptionHelperProfileData $subscriptionHelperProfileData,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        CustomerSession $customerSession
    ) {
        $this->subHelperProfileData = $subscriptionHelperProfileData;
        $this->_coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->_customerSession = $customerSession;
        parent::__construct($context);
    }

    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        /** Is have param */
        if (!($profileId = $this->getRequest()->getParam('id'))) {
            $this->_redirect('*/*');
            return;
        } //ok

        // if tmp profile id redirect
        if ($this->subHelperProfileData->isTmpProfileId($this->getRequest()->getParam('id'))) {
            $this->_redirect('*/*');
            return;
        }


        /** is Login */
        if (!$this->_customerSession->isLoggedIn()) {
            $resultRedirect = $this->resultRedirectFactory->create();
            $resultRedirect->setPath('customer/account/login');
            return $resultRedirect;
        } // ok

        try {
            if ($this->subHelperProfileData->getTmpProfile($profileId) !== false) {
                $profileId = $this->subHelperProfileData->getTmpProfile($profileId)->getData('linked_profile_id');
            }
            /** Is have data  @var  $objProfileHelper */
            $objProfileHelper = $this->_objectManager->get('Riki\Subscription\Helper\Profile\Data');
            $objProfile = $objProfileHelper->load($profileId);

            if(empty($objProfile) || empty($objProfile->getId())) {
                throw new LocalizedException(__("The Subscription profile do not exists!"));
            }//ok

            // check profile status
            if ($objProfile->getData('status') == 0) {
                $this->_redirect('*/*');
                return;
            }

            // Not Allow Subscription Hanpukai
            if ($this->subHelperProfileData->getCourseData($objProfile->getData('course_id'))
                    ->getData('subscription_type') == CourseType::TYPE_HANPUKAI) {
                $this->_redirect('*/*');
                return;
            }
            $this->_coreRegistry->register('subscription_profile_obj', $objProfile);

            $customerId = $this->_customerSession->getCustomerId();

            /** Is Have permission to view profile */
            if( ! $objProfileHelper->isHaveViewProfilePermission($customerId, $profileId)) {
                throw new LocalizedException(__("Do not have permission"));
            } //ok

            $this->_coreRegistry->register('riki_subscription_profile_view', $objProfile);
            return $this->resultPageFactory->create();

        }
        catch (\Exception $e) {
            $this->messageManager->addError($e->getMessage());
            $resultRedirect = $this->resultRedirectFactory->create();

            return $resultRedirect->setPath('*/*');
        }
    }


}