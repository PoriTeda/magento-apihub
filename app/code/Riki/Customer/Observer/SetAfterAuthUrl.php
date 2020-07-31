<?php

namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;

class SetAfterAuthUrl implements ObserverInterface
{
    protected $request;

    protected $session;
    protected $urlDecoder;

    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Customer\Model\Session $session,
        \Magento\Framework\Url\DecoderInterface $decoder
    ) {
        $this->request = $request;
        $this->session = $session;
        $this->urlDecoder = $decoder;
    }

    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($returnUrl = $this->request->getParam(\Magento\Customer\Model\Url::REFERER_QUERY_PARAM_NAME)) {
            $this->session->setAfterAuthUrl($this->urlDecoder->decode($returnUrl));
        }
    }
}
