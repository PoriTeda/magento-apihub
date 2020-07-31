<?php
namespace Riki\Checkout\Plugin\Cart;

class AdvancedCart{

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var \Riki\Quote\Logger\LoggerPieceCase
     */
    protected $loggerPieceCase;

    /**
     * AdvancedCart constructor.
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Riki\Catalog\Helper\Data $catalogHelper
     * @param \Riki\Quote\Logger\LoggerPieceCase $loggerPieceCase
     */
    public function __construct(
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Riki\Catalog\Helper\Data $catalogHelper,
        \Riki\Quote\Logger\LoggerPieceCase $loggerPieceCase
    )
    {
        $this->productRepository = $productRepository;
        $this->catalogHelper     = $catalogHelper;
        $this->loggerPieceCase   = $loggerPieceCase;
    }

    /**
     * @param \Magento\AdvancedCheckout\Model\Cart $subject
     * @param $sku
     * @param $qty
     * @param array $config
     * @return array
     */
    public function beforePrepareAddProductBySku(\Magento\AdvancedCheckout\Model\Cart $subject,$sku, $qty, $config = []){

        try {
            $product = $this->productRepository->get($sku);
            list($unitQty,$caseDisplay) = $this->catalogHelper->getProductUnitInfo($product,true);
            $qty = $qty * $unitQty;
        }catch (\Exception $e){
            $this->loggerPieceCase->info($e);
        }

        return [$sku,$qty,$config];
    }
}