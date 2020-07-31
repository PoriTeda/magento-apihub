<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Controller\Address;

use Magento\Framework\Phrase;
use Magento\Customer\Model\Session;
use Magento\Directory\Helper\Data as HelperData;
use Magento\Directory\Model\RegionFactory;

class Delete extends \Magento\Customer\Controller\Address
{

    protected  $_productCartModel;

    /**
     * Delete constructor.
     * @param \Riki\Subscription\Model\ProductCart\ProductCart $productCartModel
     */
    public function __construct(
        \Riki\Subscription\Model\ProductCart\ProductCart $productCartModel,
        \Magento\Framework\App\Action\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Customer\Model\Metadata\FormFactory $formFactory,
        \Magento\Customer\Api\AddressRepositoryInterface $addressRepository,
        \Magento\Customer\Api\Data\AddressInterfaceFactory $addressDataFactory,
        \Magento\Customer\Api\Data\RegionInterfaceFactory $regionDataFactory,
        \Magento\Framework\Reflection\DataObjectProcessor $dataProcessor,
        \Magento\Framework\Api\DataObjectHelper $dataObjectHelper,
        \Magento\Framework\Controller\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory
    ) {
        $this->_productCartModel = $productCartModel;
        parent::__construct($context,
            $customerSession,
            $formKeyValidator,
            $formFactory,
            $addressRepository,
            $addressDataFactory,
            $regionDataFactory,
            $dataProcessor,
            $dataObjectHelper,
            $resultForwardFactory,
            $resultPageFactory
        );
    }

    /**
     * @return \Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        $addressId = $this->getRequest()->getParam('id', false);

        if ($this->_formKeyValidator->validate($this->getRequest())) {
            $result = $this->_productCartModel->validateAddress($addressId);
            if ($addressId && $result == true) {
                try {
                    $address = $this->_addressRepository->getById($addressId);
                    if ($address->getCustomerId() == $this->_customerSession->getCustomerId()) {
                        $this->_addressRepository->deleteById($addressId);
                        $this->messageManager->addSuccess(__('You deleted the address.'));
                    } else {
                        $this->messageManager->addError(__('We can\'t delete the address right now.'));
                    }
                } catch (\Exception $other) {
                    $this->messageManager->addException($other, __('We can\'t delete the address right now.'));
                }
            } else {
                $this->messageManager->addError(__('We can\'t delete the address exist in subcription right now.'));
            }
        }
        return $this->resultRedirectFactory->create()->setPath('*/*/index');
    }
}
