<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Logging\Test\Unit\Block\Adminhtml;

class DetailsTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Logging\Block\Adminhtml\Details
     */
    protected $object;

    /**
     * @var \Magento\Logging\Model\Event
     */
    protected $eventMock;

    /**
     * @var \Magento\Framework\Serialize\Serializer\Json
     */
    protected $jsonMock;

    protected function setUp()
    {
        $buttonListMock = $this->getMockBuilder(\Magento\Backend\Block\Widget\Button\ButtonList::class)
            ->disableOriginalConstructor()
            ->getMock();
        $urlBuilder = $this->getMockBuilder(\Magento\Framework\UrlInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $contextMock = $this->getMockBuilder(\Magento\Backend\Block\Widget\Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock->method('getButtonList')
            ->willReturn($buttonListMock);
        $contextMock->method('getUrlBuilder')
            ->willReturn($urlBuilder);

        $this->eventMock = $this->getMockBuilder(\Magento\Logging\Model\Event::class)
            ->setMethods(['getInfo'])
            ->disableOriginalConstructor()
            ->getMock();

        $registryMock = $this->getMockBuilder(\Magento\Framework\Registry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $registryMock->method('registry')
            ->willReturn($this->eventMock);

        $userFactory = $this->getMockBuilder(\Magento\User\Model\UserFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->jsonMock = $this->getMockBuilder(\Magento\Framework\Serialize\Serializer\Json::class)
            ->disableOriginalConstructor()
            ->getMock();

        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->object = $objectManager->getObject(
            \Magento\Logging\Block\Adminhtml\Details::class,
            [
                'context' => $contextMock,
                'registry' => $registryMock,
                'userFactory' => $userFactory,
                'data' => [],
                'json' => $this->jsonMock
            ]
        );
    }

    public function testGetEventInfo()
    {
        $data = json_encode([
            "string" => "phrase",
            "number" => 42,
            "bool" => true,
            "collection" => [
                "fibo"=> [1, 2, 3, 5, 8]
            ]
        ]);

        $this->jsonMock->expects($this->any())
            ->method('unserialize')
            ->willReturnCallback(
                function ($value) {
                    return json_decode($value, true);
                }
            );

        $this->eventMock->method('getInfo')
            ->willReturn($data);

        $this->assertNotEmpty($this->object->getEventInfo());
        $this->assertEquals(json_decode($data, true), $this->object->getEventInfo());
    }
}
