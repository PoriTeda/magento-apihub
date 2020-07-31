<?php

namespace Riki\Customer\Model\Data;

use \Magento\Framework\Api\AttributeValueFactory;

/**
 * Class Customer
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Customer extends \Magento\Customer\Model\Data\Customer implements
    \Riki\Customer\Api\Data\CustomerInterface
{


    /**
     * Initialize dependencies.
     *
     * @param \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory
     * @param AttributeValueFactory $attributeValueFactory
     * @param \Magento\Customer\Api\CustomerMetadataInterface $metadataService
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Api\ExtensionAttributesFactory $extensionFactory,
        AttributeValueFactory $attributeValueFactory,
        \Magento\Customer\Api\CustomerMetadataInterface $metadataService
    ) {
        $this->metadataService = $metadataService;
        parent::__construct($extensionFactory, $attributeValueFactory, $metadataService);
    }

    /**
     * Set is active
     *
     * @param $isActive
     * @return $this
     */
    public function setIsActive($isActive)
    {
        return $this->setData(self::IS_ACTIVE, $isActive);
    }

    /**
     * Get is active
     *
     * @return mixed|null
     */
    public function getIsActive()
    {
        return $this->_get(self::IS_ACTIVE);
    }

    /**
     * Set flag export bi
     *
     * @param $flagExportBi
     * @return $this
     */
    public function setFlagExportBi($flagExportBi)
    {
        return $this->setData(self::FLAG_EXPORT_BI, $flagExportBi);
    }

    /**
     * Get flag export bi
     *
     * @return mixed|null
     */
    public function getFlagExportBi()
    {
        return $this->_get(self::FLAG_EXPORT_BI);
    }

    /**
     * @param $flagSsoLoginAction
     * @return $this
     */
    public function setFlagSsoLoginAction($flagSsoLoginAction)
    {
        return $this->setData(self::FLAG_SSO_LOGIN, $flagSsoLoginAction);
    }

    /**
     * @return mixed|null
     */
    public function getFlagSsoLoginAction()
    {
        return $this->_get(self::FLAG_SSO_LOGIN);
    }
}
