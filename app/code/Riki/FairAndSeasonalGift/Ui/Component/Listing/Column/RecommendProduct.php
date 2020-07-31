<?php
namespace Riki\FairAndSeasonalGift\Ui\Component\Listing\Column;

class RecommendProduct extends \Magento\Ui\Component\Listing\Columns
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;


    /**
     * @var \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory
     */
    protected $_fairDetailCollection;

    /**
     * RecommendProduct constructor.
     * @param \Magento\Framework\View\Element\UiComponent\ContextInterface $context
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param array $components
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\UiComponent\ContextInterface $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\FairAndSeasonalGift\Model\ResourceModel\FairDetail\CollectionFactory $fairDetailCollection,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $components, $data);
        $this->_productRepository = $productRepository;
        $this->_fairDetailCollection = $fairDetailCollection;
    }

    public function prepareDataSource(array $dataSource)
    {
        if (isset($dataSource['data']['items']) && is_array($dataSource['data']['items'])) {
            foreach ($dataSource['data']['items'] as &$item) {
                if( !empty($item['fair_id']) ){
                    $reProduct = $this->getRecommendProduct($item['fair_id']);
                    if($reProduct){
                        $item['recommend_product'] = $reProduct;
                    }
                }
            }
        }
        return $dataSource;
    }

    public function getRecommendProduct($fairId){
        $collection = $this->_fairDetailCollection->create();
        $collection->addFieldToFilter('fair_id', $fairId)->addFieldToFilter('is_recommend', 1);
        if($collection->getSize()){
            try {
                $product = $this->_productRepository->getById($collection->getFirstItem()->getProductId());
                if($product){
                    return $product->getSku().'<br>'.$product->getName();
                }

            } catch (\Exception $e){
                return false;
            }
        } else {
            return false;
        }
    }
}
