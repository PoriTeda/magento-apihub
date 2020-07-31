<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Test\Unit\Model\Update;

use Magento\Framework\Intl\DateTimeFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;

class ValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\Staging\Model\Update\Validator
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|\Magento\Staging\Model\Update
     */
    protected $entityMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DateTimeFactory
     */
    private $dateTimeFactory;

    /**
     * Set Up function.
     */
    protected function setUp()
    {
        $this->entityMock = $this->createMock(\Magento\Staging\Model\Update::class);
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $this->dateTimeFactory = $this->createMock(DateTimeFactory::class);
        $this->model = $this->model = $objectManager
            ->getObject(
                \Magento\Staging\Model\Update\Validator::class,
                ['dateTimeFactory' => $this->dateTimeFactory]
            );
    }

    /**
     * @expectedException \Magento\Framework\Exception\ValidatorException
     * @expectedExceptionMessage The Name for Future Update needs to be selected. Select and try again.
     */
    public function testValidateWithEmptyName()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('');
        $this->model->validateCreate($this->entityMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\ValidatorException
     * @expectedExceptionMessage The Start Time for Future Update needs to be selected. Select and try again.
     */
    public function testValidateWithEmptyStartTime()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $this->model->validateCreate($this->entityMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\ValidatorException
     * @expectedExceptionMessage The Future Update Start Time is invalid. It can't be earlier than the current time.
     */
    public function testValidateWithWrongStartDateTime()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime('-10 minutes');
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));
        $this->model->validateCreate($this->entityMock);
    }

    /**
     * @expectedException \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateWithWrongEndDateTime()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime('tomorrow');
        $endDateTime = $startDateTime->sub(new \DateInterval('PT10M'));
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));
        $this->entityMock->expects($this->any())
            ->method('getEndTime')
            ->willReturn($endDateTime->format("m/d/Y H:i:s"));
        $this->model->validateCreate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update End Time is invalid. It can't be the same time or earlier than the current time."
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateWrongStartTime()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = (new \DateTime())->modify('+ 35 years');
        $this->entityMock->expects($this->exactly(4))
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $maxDate = new \DateTime();
        $dateTimeMock = $this->createPartialMock(DateTime::class, ['modify']);
        $this->dateTimeFactory->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);
        $dateTimeMock->expects($this->once())
            ->method('modify')
            ->with('+ 30 years')
            ->willReturn($maxDate->modify('+ 30 years'));
        $this->model->validateCreate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update Start Time is invalid. It can't be later than current time + 30 years."
        );
    }

    /**
     * @expectedException \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateWithInvalidEndTime()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $endTime = (new \DateTime())->modify('+ 35 years');
        $this->entityMock->expects($this->atLeastOnce())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $this->entityMock->expects($this->atLeastOnce())
            ->method('getEndTime')
            ->willReturn($endTime->format("m/d/Y H:i:s"));

        $maxDate = new \DateTime();
        $dateTimeMock = $this->createPartialMock(DateTime::class, ['modify']);
        $this->dateTimeFactory->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);
        $dateTimeMock->expects($this->once())
            ->method('modify')
            ->with('+ 30 years')
            ->willReturn($maxDate->modify('+ 30 years'));
        $this->model->validateCreate($this->entityMock);

        $this->expectExceptionMessage(
            "The Future Update End Time is invalid. It can't be later than current time + 30 years."
        );
    }

    /**
     * Test validate create.
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function testValidate()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $startDateTime->add(new \DateInterval('PT60S'));
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $maxDate = new \DateTime();
        $dateTimeMock = $this->createPartialMock(DateTime::class, ['modify']);
        $this->dateTimeFactory->expects($this->once())
            ->method('create')
            ->willReturn($dateTimeMock);
        $dateTimeMock->expects($this->once())
            ->method('modify')
            ->with('+ 30 years')
            ->willReturn($maxDate->modify('+ 30 years'));
        $this->model->validateCreate($this->entityMock);
    }

    /**
     * Test validate update
     *
     * @throws \Exception
     * @throws \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateUpdate()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');
        $startDateTime = new \DateTime();
        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $startDateTime->add(new \DateInterval('PT60S'));

        $this->entityMock->expects($this->any())
            ->method('getEndTime')
            ->willReturn($startDateTime->format('m/d/Y H:i:s'));
        $this->model->validateUpdate($this->entityMock);
    }

    /**
     * Scenario: End Time is less than current time. Exception expected
     *
     * @expectedExceptionMessage The Future Update End Time is invalid. It can't be earlier than the current time.
     * @expectedException \Magento\Framework\Exception\ValidatorException
     */
    public function testValidateUpdate2()
    {
        $this->entityMock->expects($this->once())->method('getName')->willReturn('Test Update');

        $startDateTime = new \DateTime(date('m/d/Y H:i:s'));
        $startDateTime->sub(new \DateInterval('P5D'));

        $this->entityMock->expects($this->any())
            ->method('getStartTime')
            ->willReturn($startDateTime->format("m/d/Y H:i:s"));

        $startDateTime->add(new \DateInterval('P2D'));

        $this->entityMock->expects($this->any())
            ->method('getEndTime')
            ->willReturn($startDateTime->format('m/d/Y H:i:s'));
        $this->model->validateUpdate($this->entityMock);
    }
}
