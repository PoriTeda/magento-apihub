<?php

namespace Riki\Coupons\Plugin\Checkout\Controller\Cart;


class CouponPostValidate
{

    /**
     * @var \Magento\Framework\Data\Form\FormKey\Validator
     */
    protected $formKeyValidator;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Checkout\Model\Session $checkoutSession
     */
    protected $checkoutSession;

    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;

    /**
     * CouponPost constructor.
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
     */
    public function __construct(
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Framework\Controller\Result\RedirectFactory $resultRedirectFactory
    ) {
        $this->formKeyValidator = $formKeyValidator;
        $this->messageManager = $messageManager;
        $this->checkoutSession = $checkoutSession;
        $this->resultRedirectFactory = $resultRedirectFactory;
    }


    public function aroundExecute(
        \Magento\Checkout\Controller\Cart\CouponPost $subject,
        \Closure $proceed
    ){
        if (!$this->formKeyValidator->validate($subject->getRequest())) {
            $subject->getRequest()->setParam('coupon_code', '');
            $this->checkoutSession->getQuote()->setCouponCode('')->save();
            $this->messageManager->addError(
                __(
                    'Your request is invalid. Please check the data again'
                )
            );
            $lastCode = $subject->getRequest()->getParam('last_code');
            $subject->getRequest()->setParam('remove_coupon', $lastCode);
            return $this->resultRedirectFactory->create()->setPath('checkout/cart/');
        }
        return $proceed();
    }
}