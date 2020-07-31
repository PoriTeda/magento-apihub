<?php

namespace Riki\Catalog\Plugin;

class ModifyImageOptions
{
    protected $_imageHelper;

    public function __construct(
        \Magento\Catalog\Helper\Image $imageHelper
    )
    {
        $this->_imageHelper = $imageHelper;
    }

    public function aroundGetGalleryImages(\Magento\Catalog\Block\Product\View\Gallery $subject, \Closure $proceed)
    {
        $product = $subject->getProduct();
        $images = $proceed();

        if ($images instanceof \Magento\Framework\Data\Collection) {
            foreach ($images as $image) {
                /* @var \Magento\Framework\DataObject $image */
                $image->setData(
                    'medium_image_url',
                    $this->_imageHelper->init($product, 'product_page_image_medium')
                        ->constrainOnly(true)->keepAspectRatio(true)->keepFrame(true) // change keepFrame to true
                        ->setImageFile($image->getFile())
                        ->getUrl()
                );
            }
        }

        return $images;
    }
}
