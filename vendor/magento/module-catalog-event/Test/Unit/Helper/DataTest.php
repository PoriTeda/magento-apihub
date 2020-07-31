<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogEvent\Test\Unit\Helper;

use Magento\CatalogEvent\Helper\Data;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;

/**
 * Unit test for Magento\CatalogEvent\Helper\Data
 */
class DataTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\CatalogEvent\Helper\Data
     */
    protected $data;

    /**
     * @var \Magento\Framework\App\Helper\Context|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextMock;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->contextMock = (new ObjectManager($this))->getObject(\Magento\Framework\App\Helper\Context::class);

        $this->data = new Data(
            $this->contextMock
        );
    }

    /**
     * @param string|bool|null $getImageResult
     * @param \PHPUnit\Framework\MockObject\Invocation $getImageUrlCalls
     * @param string|bool $result
     * @return void
     * @dataProvider getEventImageUrlDataProvider
     */
    public function testGetEventImageUrl($getImageResult, $getImageUrlCalls, $result)
    {
        $eventMock = $this->createPartialMock(\Magento\CatalogEvent\Model\Event::class, ['getImage', 'getImageUrl']);
        $eventMock
            ->expects($this->once())
            ->method('getImage')
            ->willReturn($getImageResult);

        $eventMock
            ->expects($getImageUrlCalls)
            ->method('getImageUrl')
            ->willReturn($result);

        $this->assertEquals($result, $this->data->getEventImageUrl($eventMock));
    }

    /**
     * @return array
     */
    public function getEventImageUrlDataProvider()
    {
        return [
            [null, $this->never(), false],
            [false, $this->never(), false],
            [0, $this->never(), false],
            ['data', $this->once(), 'data']
        ];
    }

    /**
     * @return void
     */
    public function testIsEnabled()
    {
        $this->contextMock
            ->getScopeConfig()
            ->expects($this->any())
            ->method('isSetFlag')
            ->with(Data::XML_PATH_ENABLED, ScopeInterface::SCOPE_STORE)
            ->willReturn('result');

        $this->assertEquals('result', $this->data->isEnabled());
    }
}
