<?php
namespace Riki\Customer\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\ScopeInterface;
use Riki\Framework\Helper\Logger\LoggerBuilder;

class AddressAfterSaveLogger implements ObserverInterface
{
    /**
     * @var LoggerBuilder
     */
    protected $loggerBuilder;

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * AddressAfterSaveLogger constructor.
     * @param LoggerBuilder $loggerBuilder
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        \Riki\Framework\Helper\Logger\LoggerBuilder $loggerBuilder,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->loggerBuilder = $loggerBuilder;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Customer\Model\Address $address */
        $address = $observer->getEvent()->getCustomerAddress();

        if (!$this->scopeConfig->getValue(
            'loggersetting/customer_logger/logger_customer_address_enable_status',
            ScopeInterface::SCOPE_STORE
        )) {
            return;
        }

        if ($address->getData('is_created_new')
            || ($address->dataHasChangedFor('region_id')
                && !$address->getData('region_id')
            )
            || ($address->dataHasChangedFor('riki_type_address')
                && $address->getData('riki_type_address') == \Riki\Customer\Model\Address\AddressType::SHIPPING
            )
        ) {
            /** @var \Riki\Framework\Helper\Logger\Monolog $logger */
            $logger = $this->loggerBuilder
                ->setName('Customer_Address')
                ->setFileName('address.log')
                ->pushHandlerByAlias(LoggerBuilder::ALIAS_DATE_HANDLER)
                ->create();

            if ($address->getData('is_created_new')) {
                $logger->critical(new LocalizedException(
                    __('A new address has been created, data: %1', json_encode($address->getData()))
                ));
            }

            if ($address->dataHasChangedFor('region_id')
                && !$address->getData('region_id')
            ) {
                $logger->critical(new LocalizedException(
                    __('Region has been updated to empty, ID: %1', $address->getId())
                ));
            }

            if (!$address->getData('is_created_new')
                && $address->dataHasChangedFor('riki_type_address')
                && $address->getData('riki_type_address') == \Riki\Customer\Model\Address\AddressType::SHIPPING
            ) {
                $logger->critical(new LocalizedException(
                    __(
                        'Address type has been changed from %1 to Shipping, ID: %2',
                        $address->getOrigData('riki_type_address'),
                        $address->getId()
                    )
                ));
            }
        }
    }
}