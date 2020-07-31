<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Reward\Test\Unit\Observer\PlaceOrder\Restriction;

class ApiTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Reward\Observer\PlaceOrder\Restriction\Api
     */
    protected $_model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $userContextMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendMock;

    protected function setUp()
    {
        $this->userContextMock = $this->createMock(\Magento\Authorization\Model\UserContextInterface::class);
        $this->frontendMock = $this->createMock(\Magento\Reward\Observer\PlaceOrder\Restriction\Frontend::class);
        $this->backendMock = $this->createMock(\Magento\Reward\Observer\PlaceOrder\Restriction\Backend::class);

        $this->_model = new \Magento\Reward\Observer\PlaceOrder\Restriction\Api(
            $this->frontendMock,
            $this->backendMock,
            $this->userContextMock
        );
    }

    /**
     * @param int $userType
     *
     * @dataProvider backendUserDataProvider
     */
    public function testIsAllowedWithBackendUser($userType)
    {
        $this->userContextMock->expects($this->once())->method('getUserType')->willReturn($userType);
        $this->backendMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->frontendMock->expects($this->never())->method('isAllowed');
        $this->assertTrue($this->_model->isAllowed());
    }

    public function backendUserDataProvider()
    {
        return [
            'admin' => [\Magento\Authorization\Model\UserContextInterface::USER_TYPE_ADMIN],
            'integration' => [\Magento\Authorization\Model\UserContextInterface::USER_TYPE_INTEGRATION]
        ];
    }

    public function frontendUserDataProvider()
    {
        return [
            'customer' => [\Magento\Authorization\Model\UserContextInterface::USER_TYPE_CUSTOMER],
            'guest' => [\Magento\Authorization\Model\UserContextInterface::USER_TYPE_GUEST]
        ];
    }

    /**
     * @param int $userType
     *
     * @dataProvider frontendUserDataProvider
     */
    public function testIsAllowedWithFrontendUser($userType)
    {
        $this->userContextMock->expects($this->once())->method('getUserType')->willReturn($userType);
        $this->backendMock->expects($this->never())->method('isAllowed');
        $this->frontendMock->expects($this->once())->method('isAllowed')->willReturn(true);
        $this->assertTrue($this->_model->isAllowed());
    }
}
