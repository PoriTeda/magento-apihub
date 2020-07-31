<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Test\Unit\Model;

class EventTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \Magento\Logging\Model\Event
     */
    protected $object;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonMock;

    protected function setUp()
    {
        $event = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getEventDispatcher')
            ->willReturn($event);

        $registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userFactoryMock = $this->getMockBuilder(\Magento\User\Model\UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $resourceMock = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName', '_construct', 'getConnection'])
            ->getMock();
        $resourceMock->method('getIdFieldName')
            ->willReturn('some_id');

        $resourceCollectionMock = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->object = $this->objectManager->getObject(
            \Magento\Logging\Model\Event::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'userFactory' => $userFactoryMock,
                'resource' => $resourceMock,
                'resourceCollection' => $resourceCollectionMock,
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * We set some initial data in the format that the method will use,
     * then we run the method and ensure that the initial data is not lost and is converted into Json string
     */
    public function testBeforeSave()
    {
        $info = [
            "string" => "value",
            "number" => 42
        ];
        $additionalInfo = [
            "bool" => true,
            "collection" => [1, 2, 3]
        ];

        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $resultData = json_encode(["general" => $info, "additional" => $additionalInfo]);

        $this->object->setId(1);
        $this->object->setInfo($info);
        $this->object->setAdditionalInfo($additionalInfo);

        $this->object->beforeSave();

        $this->assertNotEmpty($this->object->getInfo());
        $this->assertEquals($resultData, $this->object->getInfo());
    }
}
