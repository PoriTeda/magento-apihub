<?php

namespace Riki\Subscription\Controller\Profile;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Exception\LocalizedException;
use Magento\Setup\Exception;


class ValidateCouponCode extends Action
{
    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $_resultJsonFactory;

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Riki\Subscription\Model\Simulator\CouponSimulator
     */
    protected $_couponSimulator;


    protected $_messageManager;

    /**
     * ValidateCouponCode constructor.
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Subscription\Model\Simulator\CouponSimulator $couponSimulator
     * @param Context $context
     */
    public function __construct(
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Subscription\Model\Simulator\CouponSimulator $couponSimulator,
        \Magento\Framework\App\Action\Context $context
    )
    {
        $this->_resultJsonFactory = $resultJsonFactory;
        $this->_customerSession = $customerSession;
        $this->_couponSimulator = $couponSimulator;
        parent::__construct($context);
    }

    /**
     * @return $this|\Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function execute()
    {
        if ($this->_customerSession->isLoggedIn() && $this->getRequest()->getMethod() == 'POST' && $this->getRequest()->isXmlHttpRequest()) {
            $profileId  = $this->getRequest()->getParam('profile_id');
            $couponCode = $this->getRequest()->getParam('coupon_code');
            $action     = $this->getRequest()->getParam('action');

            if ($profileId !=null && $couponCode !=null && $action!=null  )
            {
                $arrData    = $this->_couponSimulator->couponApplied($profileId,$couponCode,$action);
                $arrData['dataHtml'] = $this->getHtmlListCouponApplied($profileId,$this->_couponSimulator->getListCouponApplied());
                $arrData['showInputCoupon'] = false;
                if($action=='delete') {
                    $arrData['showInputCoupon'] = true;
                }
            }else{
                $arrData = [
                    'is_validate' => false,
                    'message' => __('Coupon code is not valid')
                ];
            }

            $resultJson = $this->_resultJsonFactory->create();
            return $resultJson->setData([
                \Zend_Json::encode($arrData)
            ]);
        }
        //default redirect 404
        $this->_redirect('404');
    }

    /**
     * @param $profileId
     * @param $listCouponApplied
     * @return string
     */
    public function getHtmlListCouponApplied($profileId,$listCouponApplied=[])
    {
        $html = [];
        if (is_array($listCouponApplied) && count($listCouponApplied) > 0) {
            foreach ($listCouponApplied as $couponCode) {
                if ($couponCode != '') {
                    $html[] = '
                        <div class="applied-coupon">
                            <div class="title">' . __('Coupon use') . '</div>
                            <div class="applied-coupon-item">
                                <input name="data_coupon_code[]" type="hidden" class="amCouponsCode" value="' . $couponCode . '" />
                                <span>' . $couponCode . '</span>
                                <a data-profile-id="'.trim($profileId).'" data-coupon-code="'.trim($couponCode).'" class="delete-coupon" data-bind="click: function() {deleteCouponCode(\''.trim($profileId).'\', \''.trim($couponCode).'\')}" href="javascript:;">' . __('Cancel Coupon') . '</a>
                            </div>
                        </div>                
                    ';
                }
            }
        }
        return implode('', $html);
    }


}