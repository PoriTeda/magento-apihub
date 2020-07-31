<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Test\Unit\Model\SalesRule;

use Magento\Framework\EntityManager\EntityMetadataInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Reward\Helper\Data;
use Magento\Reward\Model\ResourceModel\Reward;
use Magento\Reward\Model\SalesRule\ReadHandler;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Model\Rule;

class ReadHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ReadHandler */
    private $model;

    /** @var Data|\PHPUnit_Framework_MockObject_MockObject */
    private $rewardHelperMock;

    /** @var MetadataPool|\PHPUnit_Framework_MockObject_MockObject */
    private $metadataPoolMock;

    /** @var Reward|\PHPUnit_Framework_MockObject_MockObject */
    private $rewardMock;

    protected function setUp()
    {
        $this->rewardHelperMock = $this->getMockBuilder(Data::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metadataPoolMock = $this->getMockBuilder(MetadataPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->rewardMock = $this->getMockBuilder(Reward::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->model = new ReadHandler(
            $this->rewardHelperMock,
            $this->metadataPoolMock,
            $this->rewardMock
        );
    }

    public function testExecute()
    {
        $attributes = [
            'some_attribute' => 'some_value',
        ];
        $linkField = 'link_field';
        $linkFieldValue = 'link_field_value';
        $data = [
            'points_delta' => '123',
        ];
        $attributesResult = array_merge_recursive(
            $attributes,
            [
                'reward_points_delta' => $data['points_delta'],
            ]
        );

        /** @var Rule|\PHPUnit_Framework_MockObject_MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData', 'setRewardPointsDelta', 'setExtensionAttributes'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn($attributes);

        /** @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn($linkFieldValue);

        $this->rewardMock->expects(self::any())
            ->method('getRewardSalesrule')
            ->with($linkFieldValue)
            ->willReturn($data);

        $ruleMock->expects(self::once())
            ->method('setRewardPointsDelta')
            ->with($data['points_delta']);

        $ruleMock->expects(self::once())
            ->method('setExtensionAttributes')
            ->with($attributesResult);

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    public function testExecuteWithoutAttributesAndPoints()
    {
        $linkField = 'link_field';
        $linkFieldValue = 'link_field_value';
        $attributesResult = array_merge_recursive(
            [
                'reward_points_delta' => 0,
            ]
        );

        /** @var Rule|\PHPUnit_Framework_MockObject_MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData', 'setRewardPointsDelta', 'setExtensionAttributes'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        /** @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn($linkFieldValue);

        $this->rewardMock->expects(self::any())
            ->method('getRewardSalesrule')
            ->with($linkFieldValue)
            ->willReturn([]);

        $ruleMock->expects(self::once())
            ->method('setRewardPointsDelta')
            ->with(0);

        $ruleMock->expects(self::once())
            ->method('setExtensionAttributes')
            ->with($attributesResult);

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    public function testExecuteWithoutLinkField()
    {
        $linkField = 'link_field';

        /** @var Rule|\PHPUnit_Framework_MockObject_MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['getExtensionAttributes', 'getData', 'setRewardPointsDelta', 'setExtensionAttributes'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(true);

        $ruleMock->expects($this->any())
            ->method('getExtensionAttributes')
            ->willReturn(null);

        /** @var EntityMetadataInterface|\PHPUnit_Framework_MockObject_MockObject $metadataMock */
        $metadataMock = $this->getMockBuilder(EntityMetadataInterface::class)
            ->getMock();

        $this->metadataPoolMock->expects(self::any())
            ->method('getMetadata')
            ->with(RuleInterface::class)
            ->willReturn($metadataMock);

        $metadataMock->expects(self::any())
            ->method('getLinkField')
            ->willReturn($linkField);

        $ruleMock->expects(self::any())
            ->method('getData')
            ->with($linkField)
            ->willReturn('');

        $ruleMock->expects(self::never())
            ->method('setRewardPointsDelta');

        $ruleMock->expects(self::once())
            ->method('setExtensionAttributes')
            ->with([]);

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }

    public function testExecuteWithDisabledRewards()
    {
        /** @var Rule|\PHPUnit_Framework_MockObject_MockObject $ruleMock */
        $ruleMock = $this->getMockBuilder(Rule::class)
            ->disableOriginalConstructor()
            ->setMethods(['setRewardPointsDelta', 'setExtensionAttributes'])
            ->getMock();

        $this->rewardHelperMock->expects(self::any())
            ->method('isEnabled')
            ->willReturn(false);

        $ruleMock->expects(self::never())
            ->method('setRewardPointsDelta');

        $ruleMock->expects(self::never())
            ->method('setExtensionAttributes');

        self::assertEquals($ruleMock, $this->model->execute($ruleMock));
    }
}
