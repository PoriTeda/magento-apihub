<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Test\Unit\Plugin\Customer\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Unit test for plugin for customer session model.
 */
class SessionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * Subject of testing.
     *
     * @var \Magento\Staging\Plugin\Customer\Model\Session
     */
    private $subject;

    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\Staging\Model\VersionManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $versionManagerMock;

    /**
     * @var \Magento\Customer\Model\Session|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSessionMock;

    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);

        $this->versionManagerMock = $this->createMock(\Magento\Staging\Model\VersionManager::class);

        $this->customerSessionMock = $this->createMock(\Magento\Customer\Model\Session::class);

        $this->subject = $this->objectManager->getObject(
            \Magento\Staging\Plugin\Customer\Model\Session::class,
            [
                'versionManager' => $this->versionManagerMock
            ]
        );
    }

    /**
     * @param bool $isPreview
     *
     * @dataProvider dataProvider
     */
    public function testAroundRegenerateId($isPreview)
    {
        $closureMock = function () {
            return 'Closure executed';
        };

        $expectedResult = $closureMock();

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        // Assertions.
        if ($isPreview) {
            $expectedResult = $this->customerSessionMock;
        }

        $this->assertEquals(
            $expectedResult,
            $this->subject->aroundRegenerateId(
                $this->customerSessionMock,
                $closureMock
            )
        );
    }

    /**
     * @param bool $isPreview
     *
     * @dataProvider dataProvider
     */
    public function testAroundDestroy($isPreview)
    {
        $closureMock = function ($options) {
            return $options;
        };

        $options = ['option' => 'value'];

        $expectedResult = $closureMock($options);

        $this->versionManagerMock->expects($this->once())
            ->method('isPreviewVersion')
            ->willReturn($isPreview);

        // Assertions.
        if ($isPreview) {
            $expectedResult = $this->customerSessionMock;
        }

        $this->assertEquals(
            $expectedResult,
            $this->subject->aroundDestroy(
                $this->customerSessionMock,
                $closureMock,
                $options
            )
        );
    }

    /**
     * @return array
     */
    public function dataProvider()
    {
        return [[false], [true]];
    }
}
