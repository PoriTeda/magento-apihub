<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Rma\Api;

/**
 * Interface RmaAttributesManagementInterface
 * @api
 * @since 100.0.2
 */
interface RmaAttributesManagementInterface extends \Magento\Customer\Api\MetadataInterface
{
    /**
     * Attribute set id
     */
    const ATTRIBUTE_SET_ID = 9;

    /**
     * Entity type
     */
    const ENTITY_TYPE = 'rma_item';

    /**
     * Data object class name
     */
    const DATA_OBJECT_CLASS_NAME = \Magento\Rma\Api\Data\ItemInterface::class;

    /**
     * Retrieve all attributes filtered by form code
     *
     * @param string $formCode
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface[]
     */
    public function getAttributes($formCode);

    /**
     * Retrieve attribute metadata.
     *
     * @param string $attributeCode
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface
     */
    public function getAttributeMetadata($attributeCode);

    /**
     * Get all attribute metadata.
     *
     * @return \Magento\Customer\Api\Data\AttributeMetadataInterface[]
     */
    public function getAllAttributesMetadata();

    /**
     *  Get custom attribute metadata for the given Data object's attribute set
     *
     * @param string $dataObjectClassName Data object class name
     * @return \Magento\Framework\Api\MetadataObjectInterface[]
     */
    public function getCustomAttributesMetadata($dataObjectClassName = self::DATA_OBJECT_CLASS_NAME);
}
