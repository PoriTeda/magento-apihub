<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\GiftRegistry\Test\Unit\Model;

use Magento\GiftRegistry\Model\Person;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\ResourceModel\Person as PersonResource;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\GiftRegistry\Model\Entity;

/**
 * Unit test for Person class.
 */
class PersonTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Person
     */
    private $person;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $contextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $registryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $giftRegistryDataMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $personResourceMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $entityMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $resourceCollectionMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $jsonMock;

    protected function setUp()
    {
        $this->contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->registryMock = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->giftRegistryDataMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->personResourceMock = $this->getMockBuilder(PersonResource::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->entityMock = $this->getMockBuilder(Entity::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->resourceCollectionMock = $this->getMockBuilder(AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(Json::class)
            ->disableOriginalConstructor()
            ->setMethods(['serialize', 'unserialize'])
            ->getMock();

        $this->person = new Person(
            $this->contextMock,
            $this->registryMock,
            $this->giftRegistryDataMock,
            $this->personResourceMock,
            $this->entityMock,
            $this->resourceCollectionMock,
            [],
            $this->jsonMock
        );
    }

    /**
     * @param $expectedCalls
     * @param $expectedParameter
     * @param $returnData
     * @param $expectedCustom
     * @dataProvider unserialiseCustomDataProvider
     */
    public function testUnserialiseCustom($expectedCalls, $expectedParameter, $returnData, $expectedCustom)
    {
        $this->jsonMock->expects($this->exactly($expectedCalls))
            ->method('unserialize')
            ->willReturn($returnData);
        $this->person->setCustomValues($expectedParameter);
        $this->assertEquals($expectedCustom, $this->person->unserialiseCustom()->getCustom());
    }

    /**
     * Data provider for testUnserialiseCustom.
     *
     * @return array
     */
    public function unserialiseCustomDataProvider()
    {
        return [
            [
                0,
                null,
                null,
                null,
            ],
            [
                1,
                json_encode([]),
                [],
                [],
            ],
            [
                1,
                json_encode(['key' => 'val']),
                ['key' => 'val'],
                ['key' => 'val'],
            ],
            [
                1,
                json_encode([
                    'key' => 'val',
                    0 => 3,
                    0.17,
                ]),
                [
                    'key' => 'val',
                    0 => 3,
                    0.17,
                ],
                [
                    'key' => 'val',
                    0 => 3,
                    0.17,
                ],
            ]
        ];
    }
}
