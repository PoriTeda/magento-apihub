<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Test\Unit\Model\Event;

class ChangesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Logging\Model\Event\Changes
     */
    protected $object;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonMock;

    protected function setUp()
    {
        $eventManagerMock = $this->getMockBuilder(\Magento\Framework\Event\ManagerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder(\Magento\Framework\Model\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getEventDispatcher')
            ->willReturn($eventManagerMock);

        $registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $resourceMock = $this->getMockBuilder(\Magento\Framework\Model\ResourceModel\AbstractResource::class)
            ->disableOriginalConstructor()
            ->setMethods(['getIdFieldName', '_construct', 'getConnection'])
            ->getMock();
        $resourceCollectionMock = $this->getMockBuilder(\Magento\Framework\Data\Collection\AbstractDb::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->object = $objectManager->getObject(
            \Magento\Logging\Model\Event\Changes::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'resource' => $resourceMock,
                'resourceCollection' => $resourceCollectionMock,
                'skipFields' => [],
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    /**
     * We are testing the method with data, where "Original Data" set and "Result Data" set are completely different.
     * First we set some data into the Object with Setters,
     * then call Method Under Test,
     * and then assert that it took the data that we set, converted into Json format and placed back
     */
    public function testBeforeSave()
    {
        $dataOriginal = [
            "name" => "Old Segment",
            "description" => "some",
            "is_active" => "0",
            "apply_to" => 0,
        ];

        $dataResult = [
            "name" => "New Segment",
            "description" => "",
            "is_active" => "1",
            "apply_to" => 1,
            "processing_frequency" => "1",
            "from_date" => null,
            "to_date" => null,
            "segment_id" => "4",
            "id" => "4"
        ];

        $this->jsonMock->expects($this->any())
            ->method('serialize')
            ->willReturnCallback(
                function ($value) {
                    return json_encode($value);
                }
            );

        $this->object->setOriginalData($dataOriginal);
        $this->object->setResultData($dataResult);

        $this->object->beforeSave();

        $this->assertEquals(json_encode($dataOriginal), $this->object->getOriginalData());
        $this->assertEquals(json_encode($dataResult), $this->object->getResultData());
    }
}
