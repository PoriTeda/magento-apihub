<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Helper;

use Magento\Catalog\Model\Product;
use Magento\Quote\Model\Quote\Item;

/**
 * Gift Registry helper
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @api
 * @since 100.0.2
 */
class Data extends \Magento\Framework\App\Helper\AbstractHelper
{
    const XML_PATH_ENABLED = 'magento_giftregistry/general/enabled';

    const XML_PATH_SEND_LIMIT = 'magento_giftregistry/sharing_email/send_limit';

    const XML_PATH_MAX_REGISTRANT = 'magento_giftregistry/general/max_registrant';

    const ADDRESS_PREFIX = 'gr_address_';

    /**
     * Option for address source selector
     * @var string
     */
    const ADDRESS_NEW = 'new';

    /**
     * Option for address source selector
     * @var string
     */
    const ADDRESS_NONE = 'none';

    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $customerSession;

    /**
     * @var \Magento\GiftRegistry\Model\EntityFactory
     */
    protected $entityFactory;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;

    /**
     * @var \Magento\Framework\Escaper
     */
    protected $_escaper;

    /**
     * @var \Magento\Framework\Locale\ResolverInterface
     */
    protected $_localeResolver;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @param \Magento\Framework\App\Helper\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\GiftRegistry\Model\EntityFactory $entityFactory
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Escaper $escaper
     * @param \Magento\Framework\Locale\ResolverInterface $localeResolver
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\GiftRegistry\Model\EntityFactory $entityFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\Locale\ResolverInterface $localeResolver,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        parent::__construct($context);
        $this->customerSession = $customerSession;
        $this->entityFactory = $entityFactory;
        $this->_localeDate = $localeDate;
        $this->_escaper = $escaper;
        $this->_localeResolver = $localeResolver;
        $this->_storeManager = $storeManager;
    }

    /**
     * Check whether gift registry is enabled
     *
     * @return bool
     * @codeCoverageIgnore
     */
    public function isEnabled()
    {
        return (bool)$this->scopeConfig->getValue(
            self::XML_PATH_ENABLED,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve sharing recipients limit config data
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getRecipientsLimit()
    {
        return $this->scopeConfig->getValue(
            self::XML_PATH_SEND_LIMIT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Retrieve address prefix
     *
     * @return int
     * @codeCoverageIgnore
     */
    public function getAddressIdPrefix()
    {
        return self::ADDRESS_PREFIX;
    }

    /**
     * Retrieve Max Recipients
     *
     * @param int $store
     * @return int
     * @codeCoverageIgnore
     */
    public function getMaxRegistrant($store = null)
    {
        return (int)$this->scopeConfig->getValue(
            self::XML_PATH_MAX_REGISTRANT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Validate custom attributes values
     *
     * @param array $customValues
     * @param array $attributes
     * @return array|bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function validateCustomAttributes($customValues, $attributes)
    {
        $errors = [];
        foreach ($attributes as $field => $data) {
            if (empty($customValues[$field])) {
                if (!empty($data['frontend']) && is_array(
                    $data['frontend']
                ) && !empty($data['frontend']['is_required'])
                ) {
                    $errors[] = __('Please enter the "%1".', $data['label']);
                }
            } else {
                if ($data['type'] == 'select' && is_array($data['options'])) {
                    $found = false;
                    foreach ($data['options'] as $option) {
                        if ($customValues[$field] == $option['code']) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        $errors[] = __('Please enter the correct "%1".', $data['label']);
                    }
                }
            }
        }
        if (empty($errors)) {
            return true;
        }
        return $errors;
    }

    /**
     * Return list of gift registries
     *
     * @return \Magento\GiftRegistry\Model\ResourceModel\GiftRegistry\Collection
     */
    public function getCurrentCustomerEntityOptions()
    {
        $result = [];
        $entityCollection = $this->entityFactory->create()->getCollection()->filterByCustomerId(
            $this->customerSession->getCustomerId()
        )->filterByIsActive(
            1
        );

        if (count($entityCollection)) {
            foreach ($entityCollection as $entity) {
                $result[] = new \Magento\Framework\DataObject(
                    ['value' => $entity->getId(), 'title' => $this->_escaper->escapeHtml($entity->getTitle())]
                );
            }
        }
        return $result;
    }

    /**
     * Format custom dates to internal format
     *
     * @param array|string $data
     * @param array $fieldDateFormats
     *
     * @return array|string
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function filterDatesByFormat($data, $fieldDateFormats)
    {
        if (!is_array($data)) {
            return $data;
        }
        foreach ($data as $id => $field) {
            if (!empty($data[$id])) {
                if (!is_array($field)) {
                    if (isset($fieldDateFormats[$id])) {
                        $data[$id] = $this->_filterDate($data[$id], $fieldDateFormats[$id]);
                    }
                } else {
                    foreach ($field as $id2 => $field2) {
                        if (!empty($data[$id][$id2]) && !is_array($field2) && isset($fieldDateFormats[$id2])) {
                            $data[$id][$id2] = $this->_filterDate($data[$id][$id2], $fieldDateFormats[$id2]);
                        }
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Convert date in from <$formatIn> to internal format
     *
     * @param string $value
     * @param string|bool $formatIn  -  FORMAT_TYPE_FULL, FORMAT_TYPE_LONG, FORMAT_TYPE_MEDIUM, FORMAT_TYPE_SHORT
     * @return string
     */
    public function _filterDate($value, $formatIn = false)
    {
        if ($formatIn === false) {
            return $value;
        } else {
            $formatIn = $this->_localeDate->getDateFormat($formatIn);
        }
        $filterInput = new \Zend_Filter_LocalizedToNormalized(
            ['date_format' => $formatIn, 'locale' => $this->_localeResolver->getLocale()]
        );
        $filterInternal = new \Zend_Filter_NormalizedToLocalized(
            ['date_format' => \Magento\Framework\Stdlib\DateTime::DATE_INTERNAL_FORMAT]
        );

        $value = $filterInput->filter($value);
        $value = $filterInternal->filter($value);

        return $value;
    }

    /**
     * Return frontend registry link
     *
     * @param \Magento\GiftRegistry\Model\Entity $entity
     * @return string
     * @codeCoverageIgnore
     */
    public function getRegistryLink($entity)
    {
        return $this->_storeManager->getStore($entity->getStoreId())
            ->getUrl(
                'giftregistry/view/index',
                ['id' => $entity->getUrlKey()]
            );
    }

    /**
     * Check if product can be added to gift registry
     *
     * @param Item|Product $item
     * @return bool
     */
    public function canAddToGiftRegistry($item)
    {
        return !$item->getIsVirtual();
    }
}
