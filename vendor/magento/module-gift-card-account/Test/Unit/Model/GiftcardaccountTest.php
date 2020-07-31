<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftCardAccount\Test\Unit\Model;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

/**
 * Class GiftcardaccountTest
 */
class GiftcardaccountTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Framework\TestFramework\Unit\Helper\ObjectManager
     */
    private $objectManager;

    /**
     * @var \Magento\GiftCardAccount\Model\Giftcardaccount
     */
    private $model;

    /**
     * @var \Magento\GiftCardAccount\Model\EmailManagement|\PHPUnit_Framework_MockObject_MockObject
     */
    private $emailManagement;

    /**
     * Initialize testable object
     */
    protected function setUp()
    {
        $this->objectManager = new ObjectManager($this);
        $this->emailManagement = $this->getMockBuilder(\Magento\GiftCardAccount\Model\EmailManagement::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->model = $this->objectManager->getObject(
            \Magento\GiftCardAccount\Model\Giftcardaccount::class,
            [
                'emailManagement' => $this->emailManagement
            ]
        );
    }

    /**
     * @dataProvider sendEmailDataProvider
     * @param bool $sendEmail
     */
    public function testSendEmail($sendEmail)
    {
        $this->emailManagement->expects($this->atLeastOnce())->method('sendEmail')->with($this->model)
            ->willReturn($sendEmail);
        $this->model->sendEmail();
        $this->assertEquals($sendEmail, $this->model->getEmailSent());
    }

    /**
     * @return array
     */
    public function sendEmailDataProvider()
    {
        return [
            [true],
            [false]
        ];
    }
}
