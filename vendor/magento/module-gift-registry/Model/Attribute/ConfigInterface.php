<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model\Attribute;

/**
 * Interface \Magento\GiftRegistry\Model\Attribute\ConfigInterface
 *
 */
interface ConfigInterface
{
    /**
     * Return array of attribute types for using as options
     *
     * @return array
     */
    public function getAttributeTypesOptions();

    /**
     * Return array of attribute groups for using as options
     *
     * @return array
     */
    public function getAttributeGroupsOptions();

    /**
     * Return array of attribute groups
     *
     * @return array
     */
    public function getAttributeGroups();

    /**
     * Return array of static attribute types for using as options
     *
     * @return array
     */
    public function getStaticTypes();

    /**
     * Return array of codes of static attribute types
     *
     * @return array
     */
    public function getStaticTypesCodes();

    /**
     * Check if attribute is in registrant group
     *
     * @param string $attribute
     * @return bool
     */
    public function isRegistrantAttribute($attribute);

    /**
     * Return code of static date attribute type
     *
     * @return null|string
     */
    public function getStaticDateType();

    /**
     * Return code of static region attribute type
     *
     * @return null|string
     */
    public function getStaticRegionType();

    /**
     * Return array of custom attribute types for using as options
     *
     * @return array
     */
    public function getAttributeCustomTypesOptions();

    /**
     * Return array of static attribute types for using as options
     *
     * @return array
     */
    public function getAttributeStaticTypesOptions();
}
