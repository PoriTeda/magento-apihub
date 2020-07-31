<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Catalog\Block\Html;

class ProductMultipleBreadcrumbs extends \Magento\Theme\Block\Html\Breadcrumbs
{
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $_categoryRepository;
    /**
     * @var \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory
     */
    protected $_categoryCollectionFactory;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Magento\Catalog\Model\ResourceModel\Category\CollectionFactory $categoryCollectionFactory,
        array $data = []
    ) {
        $this->_categoryRepository = $categoryRepository;
        $this->_categoryCollectionFactory = $categoryCollectionFactory;
        parent::__construct($context, $data);
    }

    /**
     * get category by id
     */
    public function getCategory(){
         $categoryID = $this->getRequest()->getParam('id');
         if($categoryID!=null){
             $collectionCategory = $this->_categoryCollectionFactory->create();
             $collectionCategory->addAttributeToSelect('*')
                 ->addAttributeToFilter('url_key',$categoryID);

             if($collectionCategory->getSize()){
                 $category = $collectionCategory->getFirstItem();
             }else{
                 $category = false;
             }


             if($category){
                 $this->addCrumb(
                     'navigation_path',
                     [
                        'dataHtml'=>$category->getData('navigation_path'),
                     ]
                 );

                 $this->addCrumb(
                     'product',
                     [
                         'label' => __($category->getName()),
                         'title' => __($category->getName()),
                     ]
                 );
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
        $this->getCategory();

        return parent::_prepareLayout();
    }

}