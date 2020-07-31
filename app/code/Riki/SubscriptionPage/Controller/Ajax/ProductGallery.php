<?php

namespace Riki\SubscriptionPage\Controller\Ajax;

class ProductGallery extends \Magento\Framework\App\Action\Action
{
    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $resultPageFactory;

    /**
     * @var \Magento\Framework\Controller\Result\JsonFactory
     */
    protected $resultJsonFactory;

    /**
     * @var \Riki\Subscription\Helper\Profile\Data
     */
    protected $profileData;

    /**
     * @var \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper
     */
    protected $deliveryDateGenerateHelper;

    protected $customerProfiles;

    protected $profileCacheRepository;

    /**
     * AjaxUpdateNextDeliveryDateMessage constructor.
     *
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param \Riki\Subscription\Helper\Profile\Data $profileData
     * @param \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Riki\Subscription\Helper\Profile\Data $profileData,
        \Riki\Subscription\Helper\Profile\DeliveryDateGenerateHelper $deliveryDateGenerateHelper,
        \Riki\Subscription\CustomerData\CustomerProfiles $customerProfiles,
        \Riki\Subscription\Model\ProfileCacheRepository $profileCacheRepository
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->profileData = $profileData;
        $this->deliveryDateGenerateHelper = $deliveryDateGenerateHelper;
        $this->customerProfiles = $customerProfiles;
        $this->profileCacheRepository = $profileCacheRepository;
        parent::__construct($context);
    }

    /**
     * Update next delivery date message when customer change delivery date
     *
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $productId = $this->getRequest()->getParam('product_id');
        $response = null;
        if (isset($productId)) {
            try {
                $galleryBlock = $this->resultPageFactory->create()
                    ->getLayout()->createBlock('\Riki\SubscriptionPage\Block\Catalog\Product\View\ProductGallery');
                $galleryBlock->setProductId($productId);
                $response['data'] = $galleryBlock->getGalleryImagesJson();
                $response['options'] = [
                    "nav"=> false,
                    "loop"=> $galleryBlock->getVar("gallery/loop", "Magento_Catalog"),
                    "keyboard"=> $galleryBlock->getVar("gallery/keyboard", "Magento_Catalog"),
                    "arrows"=> $galleryBlock->getVar("gallery/arrows", "Magento_Catalog"),
                    "allowfullscreen"=> false,
                    "showCaption"=> $galleryBlock->getVar("gallery/caption", "Magento_Catalog"),
                    "thumbwidth"=> $galleryBlock->getImageAttribute('product_page_image_small', 'width'), 
                    "thumbheight"=> $galleryBlock->getImageAttribute('product_page_image_small', 'height')
                        ?: $galleryBlock->getImageAttribute('product_page_image_small', 'width'),
                    "maxwidth" => 300,
                    "maxheight" => 300,
                    "minwidth" => 300,
                    "minheight" => 300,
                    "transitionduration"=> $galleryBlock->getVar("gallery/transition/duration", "Magento_Catalog"),
                    "transition"=>  $galleryBlock->getVar("gallery/transition/effect", "Magento_Catalog"),
                    "navarrows"=> $galleryBlock->getVar("gallery/navarrows", "Magento_Catalog"),
                    "navtype"=>  $galleryBlock->getVar("gallery/navtype", "Magento_Catalog"),
                    "navdir"=>  $galleryBlock->getVar("gallery/navdir", "Magento_Catalog")
                ];
                $response['fullscreen'] = [
                    "nav"=> $galleryBlock->getVar("gallery/fullscreen/nav", "Magento_Catalog"),
                    "loop"=> $galleryBlock->getVar("gallery/fullscreen/loop", "Magento_Catalog"),
                    "navdir"=> $galleryBlock->getVar("gallery/fullscreen/navdir", "Magento_Catalog"),
                    "arrows"=> json_encode($galleryBlock->getVar("gallery/fullscreen/arrows", "Magento_Catalog")),
                    "showCaption"=> json_encode($galleryBlock->getVar("gallery/fullscreen/caption", "Magento_Catalog")),
                    "transitionduration"=> $galleryBlock->getVar("gallery/fullscreen/transition/duration", "Magento_Catalog"),
                    "transition"=> $galleryBlock->getVar("gallery/fullscreen/transition/effect", "Magento_Catalog")
                ];
                $response['breakpoints'] = $galleryBlock->getBreakpoints();
                $response['magnifierOpts'] = $galleryBlock->getMagnifier();
            } catch (\Exception $e) {
                $response = null;
            }
        }
        return $this->resultJsonFactory->create()->setData($response);
    }
}
