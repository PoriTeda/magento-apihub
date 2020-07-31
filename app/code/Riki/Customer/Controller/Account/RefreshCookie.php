<?php

namespace Riki\Customer\Controller\Account;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;

class RefreshCookie extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $decoder;

    /**
     * @var \Riki\Customer\Model\Cookie
     */
    protected $cookie;

    /**
     * RefreshCookie constructor.
     *
     * @param Context $context
     * @param \Riki\Customer\Model\Cookie $cookie
     * @param \Magento\Framework\Url\DecoderInterface $decoder
     */
    public function __construct(
        Context $context,
        \Riki\Customer\Model\Cookie $cookie,
        \Magento\Framework\Url\DecoderInterface $decoder
    )
    {
        parent::__construct($context);

        $this->cookie = $cookie;
        $this->decoder = $decoder;
    }

    /**
     * Dispatch request.
     *
     * @return \Magento\Framework\Controller\ResultInterface|ResponseInterface
     * @throws \Magento\Framework\Exception\InputException
     * @throws \Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException
     * @throws \Magento\Framework\Stdlib\Cookie\FailureToSendException
     */
    public function execute()
    {
        $this->cookie->sendInvalidatePrivateCache();

        $redirectUrl = $this->getRequest()->getParam(\Magento\Framework\App\ActionInterface::PARAM_NAME_URL_ENCODED);

        $resultRedirect = $this->resultRedirectFactory->create();
        $resultRedirect->setUrl($redirectUrl ? $this->decoder->decode($redirectUrl) : $this->_redirect->getRefererUrl());

        return $resultRedirect;
    }
}
