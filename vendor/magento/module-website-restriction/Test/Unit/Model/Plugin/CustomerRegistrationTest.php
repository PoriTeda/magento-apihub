<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\WebsiteRestriction\Test\Unit\Model\Plugin;

class CustomerRegistrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\WebsiteRestriction\Model\Plugin\CustomerRegistration
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $restrictionConfig;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    protected function setUp()
    {
        $this->restrictionConfig = $this->createMock(\Magento\WebsiteRestriction\Model\ConfigInterface::class);
        $this->subjectMock = $this->createMock(\Magento\Customer\Model\Registration::class);
        $this->model = new \Magento\WebsiteRestriction\Model\Plugin\CustomerRegistration($this->restrictionConfig);
    }

    public function testAfterIsRegistrationIsAllowedRestrictsRegistrationIfRestrictionModeForbidsIt()
    {
        $storeMock = $this->createPartialMock(\Magento\Store\Model\Store::class, ['isAdmin']);
        $storeMock->expects($this->any())->method('isAdmin')->will($this->returnValue(false));
        $this->restrictionConfig->expects(
            $this->any()
        )->method(
            'isRestrictionEnabled'
        )->will(
            $this->returnValue(true)
        );
        $this->restrictionConfig->expects(
            $this->once()
        )->method(
            'getMode'
        )->will(
            $this->returnValue(\Magento\WebsiteRestriction\Model\Mode::ALLOW_NONE)
        );
        $this->assertFalse($this->model->afterIsAllowed($this->subjectMock, true));
    }
}
