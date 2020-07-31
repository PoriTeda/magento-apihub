<?php

namespace Riki\Customer\Plugin\Helper;

use \Magento\Customer\Api\Data\CustomerInterface;

class ViewModifyData
{
    protected $_customerMetadataService;
    /**@var \Magento\Store\Api\Data\StoreInterface **/
    protected $_resolver;

    /**
     * @var \Magento\Backend\Model\Locale\Resolver\Proxy
     */
    protected $proxyLocale;

    public function __construct(
        \Magento\Customer\Api\CustomerMetadataInterface $customerMetadata,
        \Magento\Backend\Model\Locale\Resolver\Proxy $proxyLocale,
        \Magento\Framework\Locale\Resolver $resolver
    )
    {
        $this->_customerMetadataService = $customerMetadata;
        $this->proxyLocale = $proxyLocale;
        $this->_resolver = $resolver;
    }

    /**
     * JP name style
     *
     * @param \Magento\Customer\Helper\View $subject
     * @param $proceed
     * @param CustomerInterface $customerData
     *
     * @return string
     */
    public function aroundGetCustomerName(\Magento\Customer\Helper\View $subject, $proceed, CustomerInterface $customerData)
    {
        $proceed($customerData);

        $name = '';
        $prefixMetadata = $this->_customerMetadataService->getAttributeMetadata('prefix');
        if ($prefixMetadata->isVisible() && $customerData->getPrefix()) {
            $name .= $customerData->getPrefix() . ' ';
        }

        // For Admin || front end
        if (($this->proxyLocale->getLocale() != 'en_US') || $this->_resolver->getLocale() != 'en-US') {
            $name .= $customerData->getLastname();
        } else {
            $name .= $customerData->getFirstname();
        }


        $middleNameMetadata = $this->_customerMetadataService->getAttributeMetadata('middlename');
        if ($middleNameMetadata->isVisible() && $customerData->getMiddlename()) {
            $name .= ' ' . $customerData->getMiddlename();
        }

        // For Admin || front end
        if (($this->proxyLocale->getLocale() != 'en_US') || $this->_resolver->getLocale() != 'en-US') {
            $name .= $customerData->getFirstname();
        } else {
            $name .= $customerData->getLastname();
        }


        $suffixMetadata = $this->_customerMetadataService->getAttributeMetadata('suffix');
        if ($suffixMetadata->isVisible() && $customerData->getSuffix()) {
            $name .= ' ' . $customerData->getSuffix();
        }

        return $name;
    }
}