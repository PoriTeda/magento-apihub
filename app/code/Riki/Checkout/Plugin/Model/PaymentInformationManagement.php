<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Checkout\Plugin\Model;

use Riki\Customer\Model\Address\AddressType;

/**
 * Class Validation
 */
class PaymentInformationManagement
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;
    /**
     * @var \Riki\Questionnaire\Helper\Data
     */
    protected $_dataHelper;
    /**
     * @var \Riki\Checkout\Helper\Address
     */
    protected $addressHelper;
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Riki\Questionnaire\Helper\Data $dataHelper,
        \Riki\Checkout\Helper\Address $addressHelper,
        \Magento\Customer\Model\Session $customerSession
    )
    {
        $this->_request = $request;
        $this->_dataHelper = $dataHelper;
        $this->addressHelper = $addressHelper;
        $this->customerSession = $customerSession;
    }
    public function aroundSavePaymentInformationAndPlaceOrder(
        \Magento\Checkout\Api\PaymentInformationManagementInterface $subject,
        \Closure $proceed,
        $cartId,
        \Magento\Quote\Api\Data\PaymentInterface $paymentMethod,
        \Magento\Quote\Api\Data\AddressInterface $billingAddress
    ) {
        if($paymentMethod->getExtensionAttributes() != null &&  $paymentMethod->getExtensionAttributes()->getQuestionare()){
            $this->_dataHelper->logQuestionOrder(' Area FO Set param Cart Id:'.$cartId,$paymentMethod->getExtensionAttributes()->getQuestionare());
            $this->_request->setParams(['questionnaire'=>$paymentMethod->getExtensionAttributes()->getQuestionare()]);
        }

        $customerId = $this->customerSession->getCustomerId();
        if($addressType = $billingAddress->getCustomAttribute('riki_type_address')){

            $addressType = $addressType->getValue();

            if($addressType != AddressType::OFFICE && $addressType != AddressType::HOME){

                $homeQuoteAddress = $this->addressHelper->getDefaultHomeQuoteAddressByCustomerId($customerId);

                if($homeQuoteAddress){
                    $billingAddress = $homeQuoteAddress;
                }
            }
        }

        return $proceed($cartId,$paymentMethod,$billingAddress);
    }


}
