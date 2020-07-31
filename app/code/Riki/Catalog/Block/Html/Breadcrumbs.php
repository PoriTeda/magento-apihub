<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Catalog\Block\Html;

class Breadcrumbs extends \Magento\Theme\Block\Html\Breadcrumbs
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_productRepository;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        array $data = []
    ) {
        $this->_productRepository = $productRepository;
        parent::__construct($context, $data);
    }

    /**
     * get product by id
     */
    public function getProduct(){
         $productId = $this->getRequest()->getParam('id');
         if($productId!=null){
             try{
                 $product = $this->_productRepository->getById($productId);

                 $this->addCrumb(
                     'navigation_path',
                     [
                         'dataHtml'=>$product->getData('navigation_path'),
                     ]
                 );

                 $this->addCrumb(
                     'product',
                     [
                         'label' => __($product->getName()),
                         'title' => __($product->getName()),
                     ]
                 );
             }catch (\Exception $e){
                 $this->_logger->info($e);
             }
         }
    }

    protected function _prepareLayout()
    {
        $this->addCrumb(
            'home',
            [
                'label' => __('HomePage'),
                'title' => __('Go to Home Page'),
                'link' => $this->_storeManager->getStore()->getBaseUrl()
            ]
        );

        //add breadcrumbs product
        $this->getProduct();

        return parent::_prepareLayout();
    }

}