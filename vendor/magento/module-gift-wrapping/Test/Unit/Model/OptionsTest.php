<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftWrapping\Test\Unit\Model;

use Magento\Framework\Serialize\Serializer\Json;

/**
 * Gift wrapping options model's test.
 *
 * @deprecated Currently Options class doesn't used, will be removed in the nearest backward incompatible release.
 */
class OptionsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\GiftWrapping\Model\Options
     */
    protected $subject;

    protected function setUp()
    {
        $objectManagerHelper = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $serializerMock = $this->getMockBuilder(Json::class)
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();
        $serializerMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(function ($parameter) {
                return json_encode($parameter);
            });
        $serializerMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(function ($parameter) {
                return json_decode($parameter, true);
            });
        $this->subject = $objectManagerHelper->getObject(
            \Magento\GiftWrapping\Model\Options::class,
            [
                'serializer' => $serializerMock,
            ]
        );
    }

    public function testSetDataObjectIfItemNotMagentoObject()
    {
        $itemMock = $this->createMock(\stdClass::class);
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));
    }

    public function testSetDataObjectIfItemHasNotWrappingOptions()
    {
        $itemMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getGiftwrappingOptions']);
        $itemMock->expects($this->once())->method('getGiftwrappingOptions')->will($this->returnValue(null));
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));
    }

    public function testSetDataObjectSuccess()
    {
        $wrappingOptions = json_encode(['option' => 'wrapping_option']);
        $itemMock = $this->createPartialMock(\Magento\Framework\DataObject::class, ['getGiftwrappingOptions']);
        $itemMock->expects($this->exactly(2))
            ->method('getGiftwrappingOptions')
            ->will($this->returnValue($wrappingOptions));
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));
    }

    public function testUpdateSuccess()
    {
        $wrappingOptions = json_encode(['option' => 'wrapping_option']);
        $itemMock = $this->createPartialMock(
            \Magento\Framework\DataObject::class,
            ['getGiftwrappingOptions', 'setGiftwrappingOptions']
        );
        $itemMock->expects($this->exactly(2))
            ->method('getGiftwrappingOptions')
            ->will($this->returnValue($wrappingOptions));
        $this->assertEquals($this->subject, $this->subject->setDataObject($itemMock));

        $itemMock->expects($this->once())
            ->method('setGiftwrappingOptions')
            ->with($wrappingOptions)
            ->will($this->returnValue($wrappingOptions));
        $this->assertEquals($this->subject, $this->subject->update());
    }
}
