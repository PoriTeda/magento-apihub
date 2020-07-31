<?php

namespace Riki\Customer\Observer;

use Magento\Framework\App\Http\Context;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

class EmptyHttpContextAfterLogout implements ObserverInterface
{
    /**
     * @var Context
     */
    protected $httpContext;

    /**
     * EmptyHttpContextAfterLogout constructor.
     *
     * @param Context $httpContext
     */
    public function __construct(
        \Magento\Framework\App\Http\Context $httpContext
    )
    {
        $this->httpContext = $httpContext;
    }

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $data = $this->httpContext->getData();
        foreach ($data as $name => $value) {
            $this->httpContext->unsValue($name);
        }
    }
}
