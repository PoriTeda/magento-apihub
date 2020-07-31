<?php
namespace Riki\Logging\Observer;
class LoggingSessionObserver implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Session\SessionManager
     */
    protected $_session;
    /**
     * LoggingSessionObserver constructor.
     * @param \Magento\Framework\Session\SessionManager $session
     */
    public function __construct(
        \Magento\Framework\Session\SessionManager $session
    )
    {
        $this->_session = $session;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $object = $observer->getObject();
        $object->setData('session_hash', $this->_session->getSessionId());
    }
}