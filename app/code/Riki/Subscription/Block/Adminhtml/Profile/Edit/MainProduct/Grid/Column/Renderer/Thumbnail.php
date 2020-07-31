<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Subscription\Block\Adminhtml\Profile\Edit\MainProduct\Grid\Column\Renderer;

/**
 * Renderer for Qty field in sales create new order search grid
 *
 * @author     Magento Core Team <core@magentocommerce.com>
 */
class Thumbnail extends \Magento\Backend\Block\Widget\Grid\Column\Renderer\Input
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
     * @var \Magento\Catalog\Helper\Image
     */
    protected $helperImage;

    /**
     * Thumbnail constructor.
     * @param \Magento\Backend\Block\Context $context
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig
     * @param \Magento\Catalog\Model\ProductRepository $productRepository
     * @param \Magento\Catalog\Helper\Image $helperImage
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Context $context,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $typeConfig,
        \Magento\Catalog\Model\ProductRepository $productRepository,
        \Magento\Catalog\Helper\Image $helperImage,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->typeConfig = $typeConfig;
        $this->_productRepository = $productRepository;
        $this->helperImage =  $helperImage;
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
        $origImageHelper = $this->helperImage->init($row, 'product_listing_thumbnail_preview');
        $urlThumbnail = $origImageHelper->getUrl();
        // Compose html
        $html = '<img class="img-thumbnail" src="'.$urlThumbnail.'" alt="Product Image" />';

        return $html;
    }
}
