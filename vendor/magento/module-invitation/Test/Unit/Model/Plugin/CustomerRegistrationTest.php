<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Invitation\Test\Unit\Model\Plugin;

class CustomerRegistrationTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Invitation\Model\Plugin\CustomerRegistration
     */
    protected $_model;

    /**
     * @var \Magento\Invitation\Model\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_invitationConfig;

    /**
     * @var \Magento\Invitation\Helper\Data|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $_invitationHelper;

    /**
     * @var \Magento\Customer\Model\Registration|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subjectMock;

    /**
     * @var \Magento\Invitation\Model\InvitationProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $invitationProviderMock;

    /**
     * @var \Magento\Framework\App\RequestInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestMock;

    protected function setUp()
    {
        $this->_invitationConfig = $this->getMockBuilder(\Magento\Invitation\Model\Config::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->_invitationHelper = $this->getMockBuilder(\Magento\Invitation\Helper\Data::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subjectMock = $this->getMockBuilder(\Magento\Customer\Model\Registration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->invitationProviderMock = $this->getMockBuilder(\Magento\Invitation\Model\InvitationProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestMock = $this->getMockBuilder(\Magento\Framework\App\RequestInterface::class)
            ->getMockForAbstractClass();

        $this->_model = new \Magento\Invitation\Model\Plugin\CustomerRegistration(
            $this->_invitationConfig,
            $this->_invitationHelper,
            $this->invitationProviderMock,
            $this->requestMock
        );
    }

    /**
     * Check basic logic of afterIsAllowed method
     *
     * @dataProvider afterIsAllowedMethodDataProvider
     */
    public function testAfterIsAllowedMethod(
        $invocationResult,
        $isInvitationEnabled,
        $isInvitationRequired,
        $invitationId,
        $expected
    ) {
        $this->_invitationConfig->expects($this->any())
            ->method('isEnabled')
            ->willReturn($isInvitationEnabled);

        $this->_invitationConfig->expects($this->any())
            ->method('getInvitationRequired')
            ->willReturn($isInvitationRequired);

        $invitationMock = $this->getMockBuilder(\Magento\Invitation\Model\Invitation::class)
            ->disableOriginalConstructor()
            ->getMock();
        $invitationMock->expects($this->any())
            ->method('getId')
            ->willReturn($invitationId);

        $this->invitationProviderMock->expects($this->any())
            ->method('get')
            ->with($this->requestMock)
            ->willReturn($invitationMock);

        $this->assertEquals($expected, $this->_model->afterIsAllowed($this->subjectMock, $invocationResult));
    }

    /**
     * Provides test data for testAfterIsAllowedMethod test
     *
     * @return array
     */
    public function afterIsAllowedMethodDataProvider()
    {
        return [
            [
                'invocation_result' => false,
                'invitation_enabled' => false,
                'invitation_required' => false,
                'invitation_id' => null,
                'expected' => false,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => false,
                'invitation_required' => false,
                'invitation_id' => null,
                'expected' => true,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => true,
                'invitation_required' => false,
                'invitation_id' => null,
                'expected' => true,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => true,
                'invitation_required' => true,
                'invitation_id' => null,
                'expected' => false,
            ],
            [
                'invocation_result' => true,
                'invitation_enabled' => true,
                'invitation_required' => true,
                'invitation_id' => 1,
                'expected' => true,
            ],
        ];
    }

    /**
     * Check that if exception occurs then method returns FALSE
     */
    public function testAfterIsAllowedMethodWithException()
    {
        $invocationResult = true;
        $isInvitationEnabled = true;
        $isInvitationRequired = true;

        $this->_invitationConfig->expects($this->once())
            ->method('isEnabled')
            ->willReturn($isInvitationEnabled);

        $this->_invitationConfig->expects($this->once())
            ->method('getInvitationRequired')
            ->willReturn($isInvitationRequired);

        $this->invitationProviderMock->expects($this->once())
            ->method('get')
            ->with($this->requestMock)
            ->willThrowException(new \Exception());

        $this->assertFalse($this->_model->afterIsAllowed($this->subjectMock, $invocationResult));
    }
}
