<?php

namespace Riki\Subscription\Controller\Profile;

use \Riki\Subscription\Api\ProfileRepositoryInterface;

class SaveFrequency extends \Magento\Framework\App\Action\Action
{
    /* @var \Riki\Subscription\Api\ProfileRepositoryInterface */
    protected $profileRepository;

    /* @var \Riki\Subscription\Helper\Profile\Data */
    protected $profileData;

    /* @var \Magento\Framework\Data\Form\FormKey\Validator */
    protected $_formKeyValidator;

    /* @var \Riki\Subscription\Model\Frequency\Frequency */
    protected $_frequencyModel;

    public function __construct(
        \Riki\Subscription\Model\Frequency\Frequency $frequency,
        \Magento\Framework\Data\Form\FormKey\Validator $validator,
        \Magento\Framework\App\Action\Context $context,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Api\ProfileRepositoryInterface $profileRepositoryInterface
    ){
        $this->_frequencyModel = $frequency;
        $this->_formKeyValidator = $validator;
        $this->profileData = $profileData;
        $this->profileRepository = $profileRepositoryInterface;
        parent::__construct($context);
    }

    public function execute()
    {
        $redirectUrl = null;
        if (!$this->_formKeyValidator->validate($this->getRequest()) || !$this->getRequest()->isPost()) {
            $this->messageManager->addError(__('Have error when save date'));
            return $this->resultRedirectFactory->create()->setPath($this->_redirect->getRefererUrl());
        }
        try {
            $profileId = $this->getRequest()->getParam('profile_id');
            if (!$profileId || !$this->profileData->load($profileId)) {
                $this->messageManager->addError(__('Profile Not Exist'));
                $this->_redirect('*/*');
            }

            $profileModel = $this->profileData->load($profileId);
            $frequencyId = $this->getRequest()->getParam('frequency_id');

            $frequencyModel = $this->_frequencyModel->load($frequencyId);
            if(empty($frequencyModel) || $frequencyModel->getId() == null) {
                $this->messageManager->addError(__("Please choice frequency"));
                return $this->_redirect('*/*/index');
            }

            $this->profileData->UpdateFrequency(
                $profileId, $frequencyModel->getData('frequency_unit'), $frequencyModel->getData('frequency_interval'));
            $this->messageManager->addSuccess(__('Update profile successfully!'));
            $this->profileData->resetProfileSession($profileId);
            return $this->_redirect('*/*/index');
        } catch(\Exception $e) {
            $this->messageManager->addError(__('Not Save Profile'));
            return $this->_redirect('*/*/index');
        }
    }
}