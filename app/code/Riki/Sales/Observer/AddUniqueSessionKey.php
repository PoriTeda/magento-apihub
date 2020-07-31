<?php
namespace Riki\Sales\Observer;

use Magento\Framework\Event\ObserverInterface;

class AddUniqueSessionKey implements ObserverInterface
{
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $session;

    /**
     * @var \Magento\Framework\Math\Random
     */
    protected $mathRandom;

    /**
     * AddUniqueSessionKey constructor.
     * @param \Magento\Backend\Model\Session\Quote $session
     * @param \Magento\Framework\Math\Random $mathRandom
     */
    public function __construct(
        \Magento\Backend\Model\Session\Quote $session,
        \Magento\Framework\Math\Random $mathRandom
    ) {
        $this->session = $session;
        $this->mathRandom = $mathRandom;
    }

    /**
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Framework\App\RequestInterface $request */
        $request = $observer->getEvent()->getRequest();

        if (!$request->isAjax()) {
            $this->session->setSessionUniqueKey($this->mathRandom->getUniqueHash());
        }
    }
}