<?php
namespace Riki\Subscription\Controller\Profile;
use Magento\Customer\Model\Session as CustomerSession;

class Ajax extends \Magento\Framework\App\Action\Action
{
    /* @var \Riki\Subscription\Helper\Profile\Data  */
    protected $_profileData;

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

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * Ajax constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param CustomerSession $customerSession
     */
    public function __construct(
        \Riki\Subscription\Helper\Profile\Data $subHelperProfileData,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        CustomerSession $customerSession
    ) {
        $this->_profileData = $subHelperProfileData;
        $this->_coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * Default customer account page
     *
     * @return void
     */
    public function execute()
    {
        $profile_id = $this->getRequest()->getPost('profile_id');
        if ($this->_profileData->getTmpProfile($profile_id) !== false) {
            $profile_id = $this->_profileData->getTmpProfile($profile_id)->getData('linked_profile_id');
        }
        $skip = $this->getRequest()->getPost('skip_next_delivery');
        $model = $this->_objectManager->create('Riki\Subscription\Model\Profile\Profile')->load($profile_id);
        $model->setSkipNextDelivery($skip);
        // try to save it
        try {
            // save the data
            $model->save();
            $response = ['success' => 'true'];

        } catch (\Exception $e) {
            // display error message
            $response = ['error' => 'true', 'message' => $e->getMessage()];
        }
        return $this->resultJsonFactory->create()->setData($response);
    }


    /**
     * Retrieve customer data object
     *
     * @return int
     */
    protected function getCustomerId()
    {
        return $this->customerSession->getCustomerId();
    }

}