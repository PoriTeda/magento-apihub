<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogRuleStaging\Test\Unit\Block\Adminhtml\Update;

use Magento\CatalogRuleStaging\Block\Adminhtml\Update\Provider;
use Magento\Framework\Exception\NoSuchEntityException;

class ProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $ruleRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var Provider
     */
    protected $button;

    protected function setUp()
    {
        $this->requestMock = $this->createMock(\Magento\Framework\App\RequestInterface::class);
        $this->ruleRepositoryMock = $this->createMock(
            \Magento\CatalogRule\Api\CatalogRuleRepositoryInterface::class
        );
        $this->button = new Provider($this->requestMock, $this->ruleRepositoryMock);
    }

    public function testGetRuleIdNoRule()
    {
        $this->ruleRepositoryMock->expects($this->once())
            ->method('get')
            ->willThrowException(new NoSuchEntityException(__('Smth went to exception')));
        $this->assertNull($this->button->getId());
    }

    public function testGetRuleId()
    {
        $id = 203040;
        $catalogRuleMock = $this->createMock(\Magento\CatalogRule\Api\Data\RuleInterface::class);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('id')
            ->willReturn($id);
        $this->ruleRepositoryMock->expects($this->once())
            ->method('get')
            ->with($id)
            ->willReturn($catalogRuleMock);
        $catalogRuleMock->expects($this->once())->method('getRuleId')->willReturn($id);

        $this->assertEquals($id, $this->button->getId());
    }
}
