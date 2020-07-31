<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Test\Unit\Ui\DataProvider\Catalog\Product\Form\Modifier;

use Magento\CatalogStaging\Ui\DataProvider\Catalog\Product\Form\Modifier\Eav;
use Magento\Catalog\Api\ProductAttributeGroupRepositoryInterface;
use Magento\Catalog\Api\ProductAttributeRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory as EavAttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\ResourceModel\Entity\Attribute\Group\CollectionFactory as GroupCollectionFactory;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SortOrderBuilder;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Filter\Translit;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\Component\Form\Fieldset;
use Magento\Ui\DataProvider\Mapper\FormElement as FormElementMapper;
use Magento\Ui\DataProvider\Mapper\MetaProperties as MetaPropertiesMapper;
use Magento\Catalog\Model\Attribute\ScopeOverriddenValue;
use Magento\Catalog\Ui\DataProvider\CatalogEavValidationRules;

/**
 * @SuppressWarnings(PHPMD.TooManyFields)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class EavTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Eav
     */
    protected $modifier;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $locatorMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $validationRulesMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavConfigMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requestMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $groupFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $storeManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $formMapperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $metaPropertiesMapperMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeGroupRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeRepositoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $searchCriteriaBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $sortOrderBuilderMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $eavAttributeFactoryMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $translitFilterMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $arrayManagerMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $scopeOverriddenValueMock;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dataPersistorMock;

    protected function setUp()
    {
        $this->locatorMock = $this->getMockBuilder(\Magento\Catalog\Model\Locator\LocatorInterface::class)
            ->getMock();
        $this->validationRulesMock = $this->getMockBuilder(CatalogEavValidationRules::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavConfigMock = $this->getMockBuilder(Config::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->requestMock = $this->getMockBuilder(RequestInterface::class)
            ->getMock();
        $this->groupFactoryMock = $this->getMockBuilder(GroupCollectionFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->storeManagerMock = $this->getMockBuilder(StoreManagerInterface::class)
            ->getMock();
        $this->formMapperMock = $this->getMockBuilder(FormElementMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->metaPropertiesMapperMock = $this->getMockBuilder(MetaPropertiesMapper::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeGroupRepositoryMock = $this->getMockBuilder(ProductAttributeGroupRepositoryInterface::class)
            ->getMock();
        $this->attributeRepositoryMock = $this->getMockBuilder(ProductAttributeRepositoryInterface::class)
            ->getMock();
        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->sortOrderBuilderMock = $this->getMockBuilder(SortOrderBuilder::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->eavAttributeFactoryMock = $this->getMockBuilder(EavAttributeFactory::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->translitFilterMock = $this->getMockBuilder(Translit::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->arrayManagerMock = $this->getMockBuilder(ArrayManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->scopeOverriddenValueMock = $this->getMockBuilder(ScopeOverriddenValue::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dataPersistorMock = $this->getMockBuilder(DataPersistorInterface::class)
            ->getMock();

        $this->modifier = new Eav(
            $this->locatorMock,
            $this->validationRulesMock,
            $this->eavConfigMock,
            $this->requestMock,
            $this->groupFactoryMock,
            $this->storeManagerMock,
            $this->formMapperMock,
            $this->metaPropertiesMapperMock,
            $this->attributeGroupRepositoryMock,
            $this->attributeRepositoryMock,
            $this->searchCriteriaBuilderMock,
            $this->sortOrderBuilderMock,
            $this->eavAttributeFactoryMock,
            $this->translitFilterMock,
            $this->arrayManagerMock,
            $this->scopeOverriddenValueMock,
            $this->dataPersistorMock
        );
    }

    /**
     * Checks the configuration array returned by modifyMeta() method
     *
     * Checks that 'product-details' array is exists.
     * Checks that 'prefer' item with 'toggle' value is exists.
     */
    public function testModifyMeta()
    {
        $meta = [1, 2, 3];
        $this->getGroupsMock();
        $productMock = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->locatorMock->expects($this->atLeastOnce())->method('getProduct')->willReturn($productMock);
        $productMock->expects($this->once())->method('getId')->willReturn(1);

        $result = $this->modifier->modifyMeta($meta);
        $this->assertArrayHasKey('product-details', $result);

        $this->assertArrayHasKey(
            'prefer',
            $result['product-details']['children']['container_is_product_new']['children']['is_new']
            ['arguments']['data']['config']
        );
        $this->assertEquals(
            'toggle',
            $result['product-details']['children']['container_is_product_new']['children']['is_new']
            ['arguments']['data']['config']['prefer']
        );
    }

    public function testModifyData()
    {
        $productId = 100;
        $meta = [1, 2, $productId => ['product' => ['news_from_date' => 1]]];
        $productMock = $this->getMockBuilder(\Magento\Catalog\Api\Data\ProductInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->locatorMock->expects($this->atLeastOnce())->method('getProduct')->willReturn($productMock);
        $this->getGroupsMock();
        $productMock->expects($this->atLeastOnce())->method('getId')->willReturn($productId);
        $result = $this->modifier->modifyData($meta);
        $this->assertEquals(1, $result[$productId]['product']['is_new']);
    }

    private function getGroupsMock()
    {
        $this->searchCriteriaBuilderMock->expects($this->atLeastOnce())->method('addFilter')->willReturnSelf();
        $searchCriteriaMock = $this->getMockBuilder(\Magento\Framework\Api\SearchCriteria::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->searchCriteriaBuilderMock->expects($this->atLeastOnce())
            ->method('create')
            ->willReturn($searchCriteriaMock);
        $listMock = $this->getMockBuilder(\Magento\Eav\Api\Data\AttributeGroupSearchResultsInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->attributeGroupRepositoryMock->expects($this->once())
            ->method('getList')
            ->willReturn($listMock);
        $listMock->expects($this->atLeastOnce())->method('getItems')->willReturn([]);
    }
}
