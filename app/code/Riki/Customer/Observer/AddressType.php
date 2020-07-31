<?php
namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Riki\Framework\Helper\Logger\LoggerBuilder;

class AddressType implements ObserverInterface
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Riki\Customer\Helper\Data
     */
    protected $dataHelper;

    /**
     * @var \Riki\Framework\Helper\Logger\LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * AddressType constructor.
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Riki\Customer\Helper\Data $dataHelper
     * @param LoggerBuilder $loggerBuilder
     */
    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Riki\Customer\Helper\Data $dataHelper,
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder
    ) {
        $this->customerSession = $customerSession;
        $this->dataHelper = $dataHelper;
        $this->loggerBuilder = $loggerBuilder;
    }

    /**
     * Force address type to shipping when customer create new address
     *
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $observer->getEvent()->getCustomerAddress();
        if (!$address->getData('riki_type_address')) {
            $address->setRikiTypeAddress(\Riki\Customer\Model\Address\AddressType::SHIPPING);

            if ($address->getId()) {
                /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
                $logger = $this->loggerBuilder
                    ->setName('Customer_Address')
                    ->setFileName('address.log')
                    ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                    ->create();

                $logger->critical(
                    new LocalizedException(__('Riki type address value is empty, address ID: %1', $address->getId()))
                );
            }
        }
    }
}