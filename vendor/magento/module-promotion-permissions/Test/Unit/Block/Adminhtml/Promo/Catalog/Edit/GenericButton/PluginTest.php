<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\PromotionPermissions\Test\Unit\Block\Adminhtml\Promo\Catalog\Edit\GenericButton;

class PluginTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\PromotionPermissions\Block\Adminhtml\Promo\Catalog\Edit\GenericButton\Plugin
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
        $permissionsDataMock->expects($this->once())->method('getCanAdminEditCatalogRules')->willReturn($canEdit);
        $buttonMock = $this->createMock(\Magento\CatalogRule\Block\Adminhtml\Edit\GenericButton::class);

        $model = new \Magento\PromotionPermissions\Block\Adminhtml\Promo\Catalog\Edit\GenericButton\Plugin(
            $permissionsDataMock
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
            [false, 'save_and_continue_edit', false]
        ];
    }
}
