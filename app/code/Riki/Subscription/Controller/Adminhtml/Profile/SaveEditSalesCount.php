<?php
namespace Riki\Subscription\Controller\Adminhtml\Profile;

use Symfony\Component\Config\Definition\Exception\Exception;

class SaveEditSalesCount extends \Magento\Backend\App\Action
{
    /* @var \Riki\Subscription\Api\ProfileRepositoryInterface */
    protected $profileRepository;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $profileData;

    /* @var \Magento\Framework\Data\Form\FormKey\Validator */
    protected $_formKeyValidator;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepositoryInterface
    ){
        $this->_formKeyValidator = $context->getFormKeyValidator();
        $this->profileData = $profileData;
        $this->profileRepository = $profileRepositoryInterface;
        parent::__construct($context);
    }

    public function execute()
    {
        $redirectUrl = null;
        $urlRefer = $this->_request->getServer('HTTP_REFERER');
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('Error Formkey.Please refresh page'));
            return $this->resultRedirectFactory->create()->setPath($urlRefer);
        }

        $profileId = $this->getRequest()->getParam('profile_id');
        if (!$profileId || !$this->profileData->loadProfileModel($profileId)) {
            $this->messageManager->addError(__('Profile Not Exist'));
            return $this->resultRedirectFactory->create()->setPath($urlRefer);
        }

        $salesCount = $this->getRequest()->getParam('sales_count');
        $salesValueCount = $this->getRequest()->getParam('sales_value_count');

        $profileModelData = $this->profileData->loadProfileModel($profileId);
        $currentSalesCount = $profileModelData->getData('sales_count');
        $currentSalesValueCount = $profileModelData->getData('sales_value_count');
        if (!$this->validateDataPost($salesCount, $salesValueCount, $currentSalesCount, $currentSalesValueCount)) {
            $this->messageManager->addError(__('Sales count and sales amount value can be only decrease '));
            return $this->resultRedirectFactory->create()->setPath($urlRefer);
        }
        $this->profileData->updateSalesCount(
            $profileId, $salesCount, $salesValueCount);
        $this->messageManager->addSuccess(__('Update profile successfully!'));
        $this->profileData->resetProfileSession($profileId);
        return $this->_redirect('customer/index/edit', ["id" => $profileModelData->getData('customer_id')]);
    }

    /**
     * @param $salesCountChange
     * @param $salesValueCountChange
     * @param $currentSalesCount
     * @param $currentSalesValueCount
     *
     * return bool
     */
    public function validateDataPost($salesCountChange, $salesValueCountChange, $currentSalesCount, $currentSalesValueCount)
    {
        if ($salesCountChange > $currentSalesCount || $salesValueCountChange > $currentSalesValueCount) {
            return false;
        }
        return true;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_Subscription::edit_sales_count');
    }


}