<?php

namespace Riki\ShoppingPoint\Controller\Index;

use Magento\Framework\Controller\ResultFactory;

class Save extends \Magento\Framework\App\Action\Action
{
    /**
     * @var
     */
    protected $_formKeyValidator;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */


    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
    ) {

        $this->_customerSession = $customerSession;
        $this->_formKeyValidator = $formKeyValidator;
        parent::__construct($context);
    }

    /**
     * Save settings
     *
     * @return \Magento\Framework\App\ResponseInterface
     */
    public function execute()
    {
        if (!$this->getRequest()->isAjax()) {
            $backTo = $this->_redirect->getRefererUrl();
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setUrl($backTo);
        }
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $response['err'] = true;

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $response['msg'] = __('Form key is not valid.');
            return $resultJson->setData($response);
        }
        $customer = $this->_getCustomer();
        if ($customer->getId()) {
            $response['err'] = false;
            $customer->setRewardUserSetting($this->getRequest()->getParam('reward_user_setting'));
            $customer->setRewardUserRedeem(intval($this->getRequest()->getParam('reward_user_redeem')));
            $customer->getResource()->saveAttribute($customer, 'reward_user_setting');
            $customer->getResource()->saveAttribute($customer, 'reward_user_redeem');
            $response['msg'] = __('You saved the settings.');
        }
        return $resultJson->setData($response);
    }

    public function _getCustomer()
    {
        return $this->_customerSession->getCustomer();
    }

}