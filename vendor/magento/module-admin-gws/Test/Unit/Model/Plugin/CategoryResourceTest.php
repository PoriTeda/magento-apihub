<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\AdminGws\Test\Unit\Model\Plugin;

class CategoryResourceTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \Magento\AdminGws\Model\Plugin\CategoryResource
     */
    protected $model;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $roleMock;

    protected function setUp()
    {
        $this->roleMock = $this->createMock(\Magento\AdminGws\Model\Role::class);
        $this->model = new \Magento\AdminGws\Model\Plugin\CategoryResource($this->roleMock);
    }

    public function testBeforeChangeParentDoesNotCheckCategoryAccessWhenRoleIsNotRestricted()
    {
        $subjectMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category::class);
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $this->roleMock->expects($this->once())->method('getIsAll')->will($this->returnValue(true));
        $this->roleMock->expects($this->never())->method('hasExclusiveCategoryAccess');
        $this->model->beforeChangeParent($subjectMock, $currentCategory, $parentCategory);
    }

    /**
     * @param boolean $hasParentPathAccess
     * @param boolean $hasCurrentPathAccess
     * @expectedException \Magento\Framework\Exception\LocalizedException
     * @expectedExceptionMessage More permissions are needed to save this item.
     * @dataProvider beforeChangeParentThrowsExceptionWhenAccessIsRestrictedDataProvider
     */
    public function testBeforeChangeParentThrowsExceptionWhenAccessIsRestricted(
        $hasParentPathAccess,
        $hasCurrentPathAccess
    ) {
        $this->roleMock->expects($this->once())->method('getIsAll')->will($this->returnValue(false));

        $subjectMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category::class);
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $currentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->will(
            $this->returnValue('current/path')
        );
        $parentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->will(
            $this->returnValue('parent/path')
        );

        $this->roleMock->expects(
            $this->any()
        )->method(
            'hasExclusiveCategoryAccess'
        )->will(
            $this->returnValueMap(
                [['parent/path', $hasParentPathAccess], ['current/path', $hasCurrentPathAccess]]
            )
        );
        $this->model->beforeChangeParent($subjectMock, $currentCategory, $parentCategory, null);
    }

    public function beforeChangeParentThrowsExceptionWhenAccessIsRestrictedDataProvider()
    {
        return [[true, false], [false, true], [false, false]];
    }

    public function testBeforeChangeParentDoesNotThrowExceptionWhenUserHasAccessToGivenCategories()
    {
        $this->roleMock->expects($this->once())->method('getIsAll')->will($this->returnValue(false));

        $subjectMock = $this->createMock(\Magento\Catalog\Model\ResourceModel\Category::class);
        $parentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $parentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->will(
            $this->returnValue('parent/path')
        );
        $currentCategory = $this->createMock(\Magento\Catalog\Model\Category::class);
        $currentCategory->expects(
            $this->any()
        )->method(
            'getData'
        )->with(
            'path',
            null
        )->will(
            $this->returnValue('current/path')
        );

        $this->roleMock->expects(
            $this->exactly(2)
        )->method(
            'hasExclusiveCategoryAccess'
        )->will(
            $this->returnValueMap([['parent/path', true], ['current/path', true]])
        );
        $this->model->beforeChangeParent($subjectMock, $currentCategory, $parentCategory, null);
    }
}
