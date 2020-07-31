<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Test\Unit\Block\Adminhtml\Promo\Quote\Edit\GenericButton;

use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\PromotionPermissions\Block\Adminhtml\Promo\Quote\Edit\GenericButton\Plugin
     */
    protected $model;

    /**
     * @param bool $canEdit
     * @param string $name
     * @param bool $expectedResult
     * @dataProvider afterCanRenderDataProvider
     */
    public function testAfterCanRender($canEdit, $name, $expectedResult)
    {
        $permissionsDataMock = $this->createMock(\Magento\PromotionPermissions\Helper\Data::class);
        $permissionsDataMock->expects($this->once())->method('getCanAdminEditSalesRules')->willReturn($canEdit);
        $buttonMock = $this->createMock(\Magento\SalesRule\Block\Adminhtml\Promo\Quote\Edit\GenericButton::class);

        $model = (new ObjectManager($this))->getObject(
            \Magento\PromotionPermissions\Block\Adminhtml\Promo\Quote\Edit\GenericButton\Plugin::class,
            ['promoPermData' => $permissionsDataMock]
        );
        $this->assertEquals($expectedResult, $model->afterCanRender($buttonMock, $name));
    }

    /**
     * @return array
     */
    public function afterCanRenderDataProvider()
    {
        return [
            [true, 'any', true],
            [false, 'delete', false],
            [false, 'turbo', true],
            [false, 'save_and_continue_edit', false],
            [false, 'save', false],
            [false, 'reset', false]
        ];
    }
}
