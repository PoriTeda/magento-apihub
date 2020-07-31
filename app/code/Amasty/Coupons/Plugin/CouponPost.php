<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2016 Amasty (https://www.amasty.com)
 * @package Amasty_Coupons
 */

/**
 * Copyright © 2015 Amasty. All rights reserved.
 */

namespace Amasty\Coupons\Plugin;

class CouponPost
{

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Amasty\Coupons\Helper\Data
     */
    protected $amHelper;

    /**
     * CouponPost constructor.
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Amasty\Coupons\Helper\Data $helper
     */
    public function __construct(
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Framework\App\RequestInterface $request,
        \Amasty\Coupons\Helper\Data $helper
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->_request = $request;
        $this->messageManager = $messageManager;
        $this->_objectManager = $objectManager;
        $this->amHelper = $helper;
    }

    public function afterExecute($subject, $back)
    {
        $messages = $this->messageManager->getMessages();
        $appliedCodes = $this->amHelper->getRealAppliedCodes();
        $escaper = $this->_objectManager->get('Magento\Framework\Escaper');
        $lastCode = trim($this->_request->getParam('last_code'));
        if (is_array($appliedCodes)) {
            foreach ($messages->getItems() as $type => $message) {
                $message->setIdentifier('amCoupons');
                $fullCode =  trim($this->_request->getParam('coupon_code'));
                $isRemoved =  $this->_request->getParam('remove_coupon');
                $messageText = $message->getText();
                $messageText = str_replace($fullCode, $lastCode, $messageText);
                $message->setText($messageText);
                if (!in_array($lastCode, $appliedCodes)) {
                    $messages->deleteMessageByIdentifier('amCoupons');
                    if ($isRemoved) {
                        $this->messageManager->addSuccess(
                            __(
                                'You canceled the coupon code "%1".',
                                $escaper->escapeHtml($lastCode)
                            )
                        );
                    } else {
                        $this->messageManager->addSuccess(
                            __(
                                'You used coupon code "%1".',
                                $escaper->escapeHtml($lastCode)
                            )
                        );
                    }
                }
            }
        } else {
            $isRemoved = $this->_request->getParam('remove_coupon');
            if (!$isRemoved) {
                $this->messageManager->addError(
                    __(
                        'The coupon code "%1" is not valid.',
                        $escaper->escapeHtml($lastCode)
                    )
                );
            }
        }
        return $back;
        //return $this->controllerCart->_goBack();
    }
}
