<?php
namespace Riki\FairAndSeasonalGift\Block\Adminhtml\Items;

class ListItem extends \Magento\Framework\View\Element\Template
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    /**
     * @var \Magento\Framework\Api\SearchCriteriaInterface
     */
    protected $_searchCriteria;

    /**
     * ListItem constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteria,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_coreRegistry = $registry;
        $this->_productRepository = $productRepository;
        $this->_searchCriteria = $searchCriteria;
    }

    /**
     * @return Widget
     */
    protected function _prepareLayout()
    {
        $this->setTemplate('Riki_FairAndSeasonalGift::fair/items/new_items.phtml');

        return parent::_prepareLayout();
    }

    public function getProduct()
    {
        $criteria = $this->_searchCriteria->addFilter('entity_id', [$this->_coreRegistry->registry('new_item')], 'in' )
            ->create();

        $productCollection = $this->_productRepository->getList($criteria);

        if($productCollection->getTotalCount())
        {
            return $productCollection->getItems();
        }
        else
        {
            return false;
        }
    }
    
}