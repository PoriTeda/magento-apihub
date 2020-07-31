<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CustomerCustomAttributes\Test\Unit\Model\Quote\Address;

use Magento\CustomerCustomAttributes\Model\Quote\Address\CustomAttributeList;

class CustomAttributeListTest extends \PHPUnit\Framework\TestCase
{
    /** @var /Magento\Customer\Api\AddressMetadataInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $addressMetadata;

    /** @var \Magento\CustomerCustomAttributes\Model\Quote\Address\CustomAttributeList */
    protected $model;

    protected function setUp()
    {
        $this->addressMetadata = $this->getMockForAbstractClass(
            \Magento\Customer\Api\AddressMetadataInterface::class,
            [],
            '',
            false
        );

        $this->model = new CustomAttributeList($this->addressMetadata);
    }

    public function testGetAttributes()
    {
        $customAttributesMetadata = $this->getMockForAbstractClass(
            \Magento\Customer\Api\Data\AttributeMetadataInterface::class,
            [],
            '',
            false
        );
        $customAttributesMetadata->expects($this->at(0))
            ->method('getAttributeCode')
            ->willReturn('attributeCode');
        $this->addressMetadata->expects($this->at(0))
            ->method('getCustomAttributesMetadata')
            ->with(\Magento\Customer\Api\Data\AddressInterface::class)
            ->willReturn([$customAttributesMetadata]);

        $customAttributesMetadata->expects($this->at(1))
            ->method('getAttributeCode')
            ->willReturn('customAttributeCode');
        $this->addressMetadata->expects($this->at(1))
            ->method('getCustomAttributesMetadata')
            ->with(\Magento\Customer\Api\Data\CustomerInterface::class)
            ->willReturn([$customAttributesMetadata]);

        $this->assertEquals(
            [
                'attributeCode' => $customAttributesMetadata,
                'customAttributeCode' => $customAttributesMetadata,
            ],
            $this->model->getAttributes()
        );
    }
}
