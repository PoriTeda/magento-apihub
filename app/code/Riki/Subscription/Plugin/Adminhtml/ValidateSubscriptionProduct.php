<?php

namespace Riki\Subscription\Plugin\Adminhtml;
use Riki\SubscriptionCourse\Model\CourseFactory;
use Riki\CreateProductAttributes\Model\Product\CaseDisplay;

class ValidateSubscriptionProduct
{
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $_messageManager;
    /**
     * @var \Magento\Backend\Model\Session\Quote
     */
    protected $_sessionQuote;
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;
    /**
     * @var \Riki\Subscription\Helper\Hanpukai\Data
     */
    protected $_helperHanpukai;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $_helperSubPage;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var CourseFactory
     */
    protected $courseFactory;
    /**
     * @var \Magento\Catalog\Api\CategoryRepositoryInterface
     */
    protected $categoryRepository;

    protected  $subCourseHelper;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;

    protected $productErrors = null;

    protected $maximumOrderQty = null;

    protected $hanpukaiQty = null;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * ValidateSubscriptionProduct constructor.
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai
     * @param \Riki\SubscriptionPage\Helper\Data $helperSubPage
     * @param CourseFactory $courseFactory
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository
     * @param \Riki\SubscriptionCourse\Helper\Data $subCourseHelper
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai,
        \Riki\SubscriptionPage\Helper\Data $helperSubPage,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Magento\Catalog\Api\CategoryRepositoryInterface $categoryRepository,
        \Riki\SubscriptionCourse\Helper\Data $subCourseHelper,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->helperDelayPayment = $helperDelayPayment;
        $this->_objectManager = $objectManager;
        $this->_messageManager = $messageManager;
        $this->_sessionQuote = $sessionQuote;
        $this->_helperHanpukai = $helperHanpukai;
        $this->_helperSubPage = $helperSubPage;
        $this->dateTime = $datetime;
        $this->courseFactory = $courseFactory;
        $this->categoryRepository = $categoryRepository;
        $this->subCourseHelper = $subCourseHelper;
        $this->subscriptionValidator = $subscriptionValidator;
    }

    public function beforeExecute(\Magento\Sales\Controller\Adminhtml\Order\Create\LoadBlock $subject)
    {
        $today = $this->dateTime->gmtDate('Y/m');
        $quote = $this->_sessionQuote->getQuote();
        $quoteItems = $quote->getAllItems();
        if ($subject->getRequest()->getPost('hanpukai_qty')) {
            $this->hanpukaiQty = $subject->getRequest()->getPost('hanpukai_qty');
        }
        if ($subject->getRequest()->has('item') && !$subject->getRequest()->getPost('update_items')) {
            $arrProductId =[];
            $arrProductIdQty = [];
            $totalQtyShow = 0; // total qty product case and piece like show in ui
            if ($subject->getRequest()->getPost('course_id') and $subject->getRequest()->getPost('frequency_id')) {
                $items = $subject->getRequest()->getPost('item');
                $isAddition = false;
                $isMain = false;
                $courseId = $subject->getRequest()->getPost('course_id');
                $courseModel = $this->courseFactory->create()->load($courseId);
                $courseName = '';
                if ($courseModel->getId()) {
                    $courseName = $courseModel->getData('course_name');
                }
                $subscriptionType = $this->_helperSubPage->getSubscriptionType($courseId);
                if ($subscriptionType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                    $validMachines = $this->validateMachineItems($items, $quoteItems);
                    $subject->getRequest()->setPostValue('item', $validMachines);
                    $items = $validMachines;
                }
                $listMachinesOfTypes = [];
                foreach ($items as $id => $item) {
                    if ($subscriptionType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                        if (!array_key_exists('is_machine_type', $item)) {
                            continue;
                        }
                        $ids = explode('_', $id);
                        $machineTypeId = $ids[0];
                        $productMachineId = $ids[1];
                        if (!array_key_exists($machineTypeId, $listMachinesOfTypes)) {
                            $listMachinesOfTypes[$machineTypeId] = $productMachineId;
                        } else {
                            $subject->getRequest()->setPostValue('item', []);
                            $this->_messageManager->addError(
                                __('Each machine type, you are able to choose one product only.')
                            );
                        }
                    }
                    if (isset($item['is_machine']) and $item['is_machine'] ==1) {
                        continue;
                    }
                    if (isset($item['is_additional'])) {
                        $isAddition = true;
                    } else {
                        $isMain = true;
                    }
                }
                foreach ($quoteItems as $quoteItem) {
                    if ($quoteItem->getData('is_addition') == 1) {
                        $isAddition = true;
                    } else {
                        $isMain = true;
                    }
                }

                /*Validate when add product to cart*/
                $courseExistInQuote = $quote->getRikiCourseId();
                if ($subscriptionType == 'hanpukai') {
                    $hanpukaiType = $this->_helperSubPage->getHanpukaiType($courseId);
                    $productHanpukai = $this->_helperHanpukai->getHanpukaiProductData($hanpukaiType, $courseId,1, $today);
                    $convertItems =[];
                    if ($courseId && $courseExistInQuote && $courseExistInQuote != $courseId) {
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__('Can not add 2 difference course id'));
                        return [];
                    }
                    foreach ($items as $id => $item) {
                        $convertItems[$id] = $item['qty'];
                    }
                    $productHanpukai = array_map('intval', $productHanpukai);
                    if (!empty(array_diff_assoc($convertItems, $productHanpukai))) {
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__('Please select all product for subscription Hanpukai'));
                        return [];
                    }
                }

                foreach ($items as $id => $item) {
                    if (isset($item['is_machine']) and $item['is_machine'] == 1) {
                        continue;
                    }
                    if (isset($item['case_display']) && strtoupper($item['case_display']) == CaseDisplay::PROFILE_UNIT_PIECE) {
                        $totalQtyShow = $totalQtyShow + $item['qty'];
                        $arrProductIdQty[$id] = $item['qty'];
                    }

                    if (isset($item['case_display']) && strtoupper($item['case_display']) == CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQtyShow = $totalQtyShow + ($item['qty']/$item['unit_qty']);
                        $arrProductIdQty[$id] = ($item['qty']/$item['unit_qty']);
                    }
                    $arrProductId[] = $id;
                }
                foreach ($quoteItems as $quoteItem) {
                    if ($this->subscriptionValidator->isItemSkipped($quoteItem)) {
                        continue;
                    }

                    if (strtoupper($quoteItem->getData('unit_case')) == CaseDisplay::PROFILE_UNIT_PIECE) {
                        $totalQtyShow = $totalQtyShow + $quoteItem->getData('qty');
                        $arrProductIdQty[$quoteItem->getData('product_id')] = $quoteItem->getData('qty');
                    }

                    if (strtoupper($quoteItem->getData('unit_case')) == CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQtyShow = $totalQtyShow + ($quoteItem->getData('qty')/$quoteItem->getData('unit_qty'));
                        $key =$quoteItem->getData('product_id');
                        $arrProductIdQty[$key] = ($quoteItem->getData('qty')/$quoteItem->getData('unit_qty'));
                    }
                    $arrProductId[] = $quoteItem->getData('product_id');
                }
                $objCourseHelper = $this->subCourseHelper;
                $arrCategoryProductId = $objCourseHelper->arrCategoryIdQty($courseId);
                if (count($arrCategoryProductId) > 1) {
                    $categoryId = $arrCategoryProductId[0];
                    if ($categoryObj = $this->categoryRepository->get($categoryId)) {
                        $categoryName = $categoryObj->getName();
                    } else {
                        $categoryName = '';
                    }
                    $qtyOfCategory = $arrCategoryProductId[1];
                } else {
                    $categoryId = '';
                    $qtyOfCategory = 0;
                    $categoryName = '';
                }
                $miniShoppingCartQty = $objCourseHelper->getMinimumQtyShoppingCart($courseId);

                $errorCode = $this->isNotValid(
                    $courseId,
                    $arrProductId,
                    $items,
                    $totalQtyShow,
                    $arrProductIdQty,
                    $quoteItems
                );
                switch ($errorCode) {
                    case 1:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__('shopping cart cannot contain SPOT and subscription at the same time'));
                        break;
                    case 3:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__("You must select at least %1 quantity product(s) belong to \"%2\" category in %3",$qtyOfCategory, $categoryName,$courseName));
                        break;
                    case 4:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(
                            sprintf(__("The total number of items in the shopping cart have at least %s quantity")
                                , $miniShoppingCartQty));
                        break;
                    case 5:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__("You must select at least %1 quantity product(s) belong to \"%2\" category in %3",$qtyOfCategory, $categoryName,$courseName));
                        break;
                    case 6:
                        $subject->getRequest()->setPostValue('item', []);
                        $message = $this->subscriptionValidator->getMessageMaximumError(
                            $this->productErrors,
                            $this->maximumOrderQty
                        );
                        $this->_messageManager->addError($message);
                        break;
                    default:
                        // Do nothing
                }
            }
            else {
                $courseId = $quote->getRikiCourseId();
                $subscriptionType = $this->_helperSubPage->getSubscriptionType($courseId);
                $items = $subject->getRequest()->getPost('item');
                if ($subscriptionType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                    $validMachines = $this->validateMachineItems($items, $quoteItems);
                    $subject->getRequest()->setPostValue('item', $validMachines);
                    $items = $validMachines;
                }
                if ($courseId != null) {
                    $listMachinesOfTypes = [];
                    foreach ($items as $id => $item) {
                        if ($subscriptionType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                            if (!array_key_exists('is_machine_type', $item)) {
                                continue;
                            }
                            $ids = explode('_', $id);
                            $machineTypeId = $ids[0];
                            $productMachineId = $ids[1];
                            if (!array_key_exists($machineTypeId, $listMachinesOfTypes)) {
                                $listMachinesOfTypes[$machineTypeId] = $productMachineId;
                            } else {
                                $subject->getRequest()->setPostValue('item', []);
                                $this->_messageManager->addError(
                                    __('Each machine type, you are able to choose one product only.')
                                );
                            }
                        }
                        if (isset($item['is_machine']) and $item['is_machine'] == 1) {
                            continue;
                        }
                        $arrProductId[] = $id;
                    }
                    $errorCode = $this->isNotValid($courseId, $arrProductId, $items);
                    switch ($errorCode) {
                        case 1:
                            $subject->getRequest()->setPostValue('item', []);
                            $this->_messageManager->addError(__('shopping cart cannot contain SPOT and subscription at the same time'));
                            break;
                        default:
                            // Do nothing
                    }
                }
            }
        }
        if($subject->getRequest()->has('update_items')){
            $quote = $this->_sessionQuote->getQuote();
            $courseId = $quote->getRikiCourseId();
            $subscriptionType = $this->_helperSubPage->getSubscriptionType($courseId);
            $courseModel = $this->courseFactory->create()->load($courseId);
            $courseName = '';
            if ($courseModel->getId()) {
                $courseName = $courseModel->getData('course_name');
            }
            $arrProductId = [];
            $arrProductIdQty = [];
            $totalQtyShow = 0; // total qty product case and piece like show in ui
            if($subscriptionType == 'hanpukai') {
                $items = $subject->getRequest()->getPost('item');
                $hanpukaiType = $this->_helperSubPage->getHanpukaiType($courseId);
                $productHanpukai = $this->_helperHanpukai->getHanpukaiProductData($hanpukaiType, $courseId,1,$today);
                $convertItems =[];
                $itemRemoved = 0;
                foreach ($items as $id => $item) {
                    if($item['action'] == 'remove'){
                        $itemRemoved +=1;
                    }
                    $quoteItem = $quote->getItemById($id);
                    $productId = $quoteItem->getProduct()->getId();
                    $convertItems[$productId] = (int)$item['qty'];
                }
                if($itemRemoved >=1){
                    $quote->removeAllItems();
                }
                else{
                    $productHanpukai = array_map('intval', $productHanpukai);
                    if(count(array_diff_assoc($convertItems, $productHanpukai)) > 0){
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__('Cannot update product qty or remove product of subscription Hanpukai'));
                        return [];
                    }
                }


            }

            $items = $subject->getRequest()->getPost('item');
            // remove all cart if has only one free machine
            $itemRemoved = 0;
            /*get size of main items. without free gift, free machine*/
            $sizeOfFreeItems = 0;
            foreach ($items as $id => $item) {
                if(isset($item['action']) && $item['action'] == 'remove'){
                    $itemRemoved +=1;
                }
                $quoteItem = $quote->getItemById($id);
                if($quoteItem == null) {
                    unset($items[$id]);
                }else{
                    if ($quoteItem->getIsRikiMachine()) {
                        $sizeOfFreeItems ++;
                    }
                    $buyRequest = $quoteItem->getBuyRequest();
                    if (isset($buyRequest['options']['ampromo_rule_id'])) {
                        $sizeOfFreeItems ++;
                    }

                }
            }
            if ($itemRemoved == (sizeof($items)-$sizeOfFreeItems)) {
                $quote->removeAllItems();
            }
            else{
                /*Validate at least one main product*/
                $removed = 0;
                $isMain =  false;
                foreach ($items as $quoteItemId => $item) {
                    if ($item['action'] == 'remove') {
                        $removed++;
                    } else {
                        $quoteItem = $quote->getItemById($quoteItemId);
                        if ($quoteItem->getData('is_riki_machine') == 1) {
                            continue;
                        }
                        $buyRequest = $quoteItem->getBuyRequest();
                        if (isset($buyRequest['options']['ampromo_rule_id'])) {
                            continue;
                        }
                        if (!$quoteItem->getData('is_addition')) {
                            $isMain = true;
                        }
                    }

                }
                /*validate minimum qty order and must selected SKU*/
                $itemsData = [];
                foreach ($items as $id => $item) {
                    if (isset($item['action']) and $item['action'] == 'remove') {
                        continue;
                    }
                    $quoteItem = $quote->getItemById($id);
                    if ($quoteItem->getData('is_riki_machine') == 1) {
                        continue;
                    }
                    $buyRequest = $quoteItem->getBuyRequest();
                    if (isset($buyRequest['options']['ampromo_rule_id'])) {
                        continue;
                    }
                    if (strtoupper($quoteItem->getData('unit_case')) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                        $totalQtyShow = $totalQtyShow + $item['qty'];
                        $arrProductIdQty[$quoteItem->getData('product_id')] = $item['qty'];
                    }
                    if (strtoupper($quoteItem->getData('unit_case')) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                        $totalQtyShow = $totalQtyShow + ($item['qty'] / $quoteItem->getData('unit_qty'));
                        $arrProductIdQty[$quoteItem->getData('product_id')] = ($item['qty'] / $quoteItem->getData('unit_qty'));
                    }

                    $arrProductId[] = $quoteItem->getData('product_id');

                    $itemsData[$quoteItem->getData('product_id')] = [
                        'qty' => $item['qty'],
                        'product' => $quoteItem->getProduct(),
                        'case_display' => $quoteItem->getData('unit_case'),
                        'unit_qty' => $quoteItem->getData('unit_qty')
                    ];
                }
                $objCourseHelper = $this->subCourseHelper;
                $arrCategoryProductId = $objCourseHelper->arrCategoryIdQty($courseId);
                if (count($arrCategoryProductId) > 1) {
                    $categoryId = $arrCategoryProductId[0];
                    if ($categoryObj = $this->categoryRepository->get($categoryId)) {
                        $categoryName = $categoryObj->getName();
                    } else {
                        $categoryName = '';
                    }
                    $qtyOfCategory = $arrCategoryProductId[1];
                } else {
                    $qtyOfCategory = 0;
                    $categoryName = '';
                }
                $miniShoppingCartQty = $objCourseHelper->getMinimumQtyShoppingCart($courseId);

                /** Not set quoteItem for case update item */
                $errorCode = $this->isNotValid(
                    $courseId,
                    $arrProductId,
                    $itemsData,
                    $totalQtyShow,
                    $arrProductIdQty
                );
                switch ($errorCode) {
                    case 1:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__('shopping cart cannot contain SPOT and subscription at the same time'));
                        break;
                    case 3:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__("You must select at least %1 quantity product(s) belong to \"%2\" category in %3",$qtyOfCategory, $categoryName,$courseName));
                        break;
                    case 4:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(
                            sprintf(__("The total number of items in the shopping cart have at least %s quantity")
                                , $miniShoppingCartQty));
                        break;
                    case 5:
                        $subject->getRequest()->setPostValue('item', []);
                        $this->_messageManager->addError(__("You must select at least %1 quantity product(s) belong to \"%2\" category in %3",$qtyOfCategory, $categoryName,$courseName));
                        break;
                    case 6:
                        $subject->getRequest()->setPostValue('item', []);
                        $message = $this->subscriptionValidator->getMessageMaximumError(
                            $this->productErrors,
                            $this->maximumOrderQty
                        );
                        $this->_messageManager->addError($message);
                        break;
                    default:
                        // Do nothing
                }
            }
        }
    }

    protected function validateMachineItems($postItems, $quoteItems)
    {
        $machines = [];
        foreach ($postItems as $key => $postItem) {
            if (!array_key_exists('is_machine_type', $postItem)) {
                continue;
            }
            $ids = explode('_', $key);
            $machineTypeId = $ids[0];
            $postItem['machine_type_id'] = $machineTypeId;
            $machines[$key] = $postItem;
        }
        /** @var \Magento\Quote\Model\Quote\Item $quoteItem */
        foreach ($quoteItems as $quoteItem) {
            $product = $quoteItem->getProduct();
            $machineTypeOption = $product->getCustomOption('machine_type_id');
            if ($machineTypeOption) {
                $quoteItem->delete();
            }
        }
        return $postItems;
    }

    protected function isNotValid(
        $courseId,
        $arrProductId,
        $items,
        $qty = null,
        $arrProductIdQty = null,
        $quoteItems = null
    ) {
        $objCourse = $this->_objectManager->create('Riki\SubscriptionCourse\Model\Course');
        $objCourse->load($courseId);

        if(empty($objCourse->getId())) { // not yet set course_id in quote
            return 0;
        }
        // Check product must belong course
        $objCourseHelper = $this->subCourseHelper;
        $errorCode = $objCourseHelper->checkCartIsValidForCourse($arrProductId, $courseId);

        if($errorCode === 1) return 1; // have spot
        // Check at least on must have sku
        $mustHaveCatId = $objCourse->getData("must_select_sku");
        $isValid = $objCourseHelper->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if(!$isValid) {
            return 3;
        }

        $minimumOrderQty = $objCourse->getData("minimum_order_qty");
        if( $qty < $minimumOrderQty ) {
            return 4; // Maximum limit
        }

        if (!$objCourseHelper->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId)) {
            return 5; // Minimum product qty in category
        }

        if ($hanpukaiQty = $this->hanpukaiQty) {
            /** Process qty for subscription hanpukai */
            foreach ($items as $key => $item) {
                $items[$key]['qty'] = $item['qty'] * $hanpukaiQty;
            }
        }

        if (!empty($quoteItems)) {
            foreach ($quoteItems as $quoteItem) {
                if ($this->subscriptionValidator->isItemSkipped($quoteItem)) {
                    continue;
                }

                if (isset($items[$quoteItem->getProductId()])) {
                    $items[$quoteItem->getProductId()]['qty'] = $items[$quoteItem->getProductId()]['qty'] + $quoteItem->getQty();
                } else {
                    $items[$quoteItem->getProductId()]['qty'] = $quoteItem->getQty();
                    $items[$quoteItem->getProductId()]['case_display'] = $quoteItem->getUnitCase();
                    $items[$quoteItem->getProductId()]['unit_qty'] = $quoteItem->getUnitQty();
                }
            }
        }
        /** Validate maximum qty restriction */
        $prepareData = $this->subscriptionValidator->prepareProductData($items);
        $validateMaximumQty = $this->subscriptionValidator
            ->setCourseId($courseId)
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();

        if ($validateMaximumQty['error']) {
            $this->productErrors = $validateMaximumQty['product_errors'];
            $this->maximumOrderQty = $validateMaximumQty['maxQty'];
           return 6;
        }

        return 0;
    }

}