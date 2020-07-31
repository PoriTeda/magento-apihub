<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Order\Create\Additional\Grid\Column\Renderer;

/**
 * Renderer for Qty field in sales create new order search grid
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Category extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
{
    /**
     * @var \Magento\Catalog\Model\ProductRepository
     */
    protected $_productRepository;

    /**
     * Type config
     *
     * @var \Magento\Catalog\Model\ProductTypes\ConfigInterface
     */
    protected $typeConfig;
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $courseHelper;
    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Riki\SubscriptionCourse\Model\ResourceModel\Course $courseHelper,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->typeConfig = $typeConfig;
        $this->courseHelper = $courseHelper;
        $this->_productRepository = $productRepository;
        $this->categoryFactory = $categoryFactory;
    }

    /**
     * Returns whether this qty field must be inactive
     *
     * @param \Magento\Framework\DataObject $row
     * @return bool
     */
    protected function _isInactive($row)
    {
        return $this->typeConfig->isProductSet($row->getTypeId());
    }

    /**
     * Render product qty field
     *
     * @param \Magento\Framework\DataObject $row
     * @return string
     */
    public function render(\Magento\Framework\DataObject $row)
    {
        $categoryIds = $row->getCategoryIds();
        $courseId = $row->getData('course_id');
        $categoryIdsInCourse = $this->courseHelper->getAdditionalCategoryIds($courseId);
        $categoryNameArr = [];
        $categoryNames = null;
        if(is_array($categoryIds) and sizeof($categoryCommon = array_intersect($categoryIdsInCourse,$categoryIds)) > 0){
            $categoryModel = $this->categoryFactory->create()->getCollection()
                            ->addFieldToSelect('name')
                            ->addFieldToFilter('entity_id',['in',$categoryCommon]);
            if(sizeof($categoryModel->getItems()) > 0){
                foreach ($categoryModel->getItems() as $item){
                    $categoryNameArr[] =  $item->getName();
                }
                $categoryNames = implode(', ',$categoryNameArr);
            }
            return $categoryNames;
        }
        return null;
    }
}
