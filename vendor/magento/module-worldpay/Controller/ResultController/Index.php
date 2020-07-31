<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Worldpay\Controller\ResultController;

/**
 * Redirects to checkout cart page with appropriate message
 *
 * Class Index
 */
class Index extends \Magento\Framework\App\Action\Action
{
    /**
     * Redirect types.
     *
     * @var string
     */
    private static $cancelRedirectType = 'cancel';

    /**
     * @var string
     */
    private static $failureRedirectType = 'failure';

    /**
     * @var string
     */
    private static $successRedirectType = 'success';

    /**
     * Relative urls for different redirect types.
     *
     * @var string
     */
    private static $defaultRedirectUrl = 'checkout/cart';

    /**
     * @var string
     */
    private static $successRedirectUrl = 'checkout/onepage/success';

    /**
     * @inheritdoc
     */
    public function execute()
    {
        $params = $this->getRequest()->getParams();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        $redirectUrl = self::$defaultRedirectUrl;
        if (!isset($params['type'])) {
            return $resultRedirect->setPath($redirectUrl);
        }

        switch (trim($params['type'], '/')) {
            case self::$successRedirectType:
                $redirectUrl = self::$successRedirectUrl;
                break;
            case self::$cancelRedirectType:
                $this->messageManager->addSuccessMessage(__('Your purchase process has been cancelled.'));
                break;
            case self::$failureRedirectType:
            default:
                $this->messageManager
                    ->addErrorMessage(__('Something went wrong while processing your order. Please try again later.'));
        }

        return $resultRedirect->setPath($redirectUrl);
    }
}
