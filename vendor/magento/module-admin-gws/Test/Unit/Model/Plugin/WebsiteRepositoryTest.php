<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdminGws\Test\Unit\Model\Plugin;

class WebsiteRepositoryTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Plugin\WebsiteRepository
     */
    private $model;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subjectMock;

    /**
     * @var string
     */
    private $returnValue;

    protected function setUp()
    {
        $this->subjectMock = $this->getMockBuilder(\Magento\Store\Api\WebsiteRepositoryInterface::class)
            ->setMethods(['getById'])
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->returnValue = 'randomValue';
        $this->model = new \Magento\AdminGws\Model\Plugin\WebsiteRepository();
    }

    public function testGetDefaultNoException()
    {
        $closure = function () {
            return $this->returnValue;
        };

        $this->subjectMock->expects($this->never())
            ->method('getById');

        $this->assertEquals($this->returnValue, $this->model->aroundGetDefault($this->subjectMock, $closure));
    }

    public function testGetDefaultDomainExceptionThrown()
    {
        $closure = function () {
            throw new \DomainException();
        };

        $websiteMock = $this->getMockBuilder(\Magento\Store\Api\Data\WebsiteInterface::class)
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->subjectMock->expects($this->once())
            ->method('getById')
            ->will($this->returnValue($websiteMock));

        $this->assertSame($websiteMock, $this->model->aroundGetDefault($this->subjectMock, $closure));
    }
}
