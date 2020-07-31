<?php
namespace Riki\Subscription\Plugin\Adminhtml\Order\Create;

use Magento\Framework\Controller\ResultFactory;
use Riki\MachineApi\Helper\Machine;

class ValidateBeforeSaveOrder
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Catalog\Model\CategoryFactory
     */
    protected $categoryFactory;

    /**
     * @var ResultFactory
     */
    protected $resultFactory;


    protected $_sessionQuote;
    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    protected $redirect;
    /**
     * @var \Magento\Framework\Controller\Result\RedirectFactory
     */
    protected $resultRedirectFactory;
    /**
     * @var \Riki\SubscriptionPage\Helper\Data
     */
    protected $helperSubPage;
    /**
     * @var \Riki\SubscriptionCourse\Model\ResourceModel\Course
     */
    protected $helperHanpukai;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $dateTime;
    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $courseFactory;

    /**
     * @var \Riki\SubscriptionCourse\Helper\ValidateDelayPayment
     */
    protected $helperDelayPayment;
    protected $productErrors = null;
    protected $maximumOrderQty = null;

    /**
     * @var |Riki\MachineApi\Helper\Machine
     */
    protected $machineTypeHelper;

    /**
     * @var \Riki\Subscription\Api\Data\ValidatorInterface
     */
    protected $subscriptionValidator;

    /**
     * ValidateBeforeSaveOrder constructor.
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param \Magento\Backend\Model\Session\Quote $sessionQuote
     * @param \Riki\SubscriptionPage\Helper\Data $helperSubPage
     * @param \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai
     * @param \Magento\Framework\Stdlib\DateTime\DateTime $datetime
     * @param Machine $machineTypeHelper
     * @param \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory
     * @param \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment
     * @param \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        \Magento\Backend\Model\Session\Quote $sessionQuote,
        \Riki\SubscriptionPage\Helper\Data $helperSubPage,
        \Riki\Subscription\Helper\Hanpukai\Data $helperHanpukai,
        \Magento\Framework\Stdlib\DateTime\DateTime $datetime,
        \Riki\MachineApi\Helper\Machine $machineTypeHelper,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Riki\SubscriptionCourse\Helper\ValidateDelayPayment $helperDelayPayment,
        \Riki\Subscription\Api\Data\ValidatorInterface $subscriptionValidator
    ) {
        $this->helperDelayPayment = $helperDelayPayment;
        $this->_objectManager = $context->getObjectManager();
        $this->messageManager = $context->getMessageManager();
        $this->resultFactory = $context->getResultFactory();
        $this->redirect = $context->getRedirect();
        $this->categoryFactory = $categoryFactory;
        $this->_sessionQuote = $sessionQuote;
        $this->resultRedirectFactory = $context->getResultRedirectFactory();
        $this->helperSubPage =  $helperSubPage;
        $this->machineTypeHelper = $machineTypeHelper;
        $this->helperHanpukai = $helperHanpukai;
        $this->dateTime = $datetime;
        $this->courseFactory = $courseFactory;
        $this->subscriptionValidator = $subscriptionValidator;
    }

    public function aroundExecute(\Magento\Sales\Controller\Adminhtml\Order\Create\Save $subject, \Closure $proceed)
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $quote = $this->_sessionQuote->getQuote();
        $courseId = $quote->getData("riki_course_id");

        if (!empty($courseId)) {
            $courseModel = $this->courseFactory->create()->load($courseId);
            $courseName = '';
            $courseType = '';
            if ($courseModel->getId()) {
                $courseName = $courseModel->getData('course_name');
                $courseType = $courseModel->getSubscriptionType();
            }
            $items =[];
            $hasMachine = false;
            /** @var \Magento\Quote\Model\Quote\Item $item */
            foreach ($quote->getAllItems() as $item) {
                if ($courseType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                    if ($productMachineOption = $item->getProduct()->getCustomOption('machine_type_id')) {
                        $hasMachine = true;
                    }
                }
                if ($item->getIsRikiMachine()) {
                    continue;
                }
                if (isset($items[$item->getProductId()])) {
                    $items[$item->getProductId()] += $item->getQty();
                } else {
                    $items[$item->getProductId()] = $item->getQty();
                }
            }
            if (!$hasMachine && $courseType == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
                $this->messageManager->addError(
                    __("In this subscription you need choose machine at least one machine.")
                );
                $resultRedirect->setPath('sales/*/');
                return $resultRedirect;
            }
            $arrProductId = [];
            $arrProductIdQty = [];
            $totalQtyShow = 0; // total qty product case and piece like show in ui
            $hasMainProduct = false;
            foreach ($quote->getAllItems() as $item) {
                if ($item->getData('is_addition') == 0 && $hasMainProduct == false) {
                    $hasMainProduct = true;
                }
                if ($item->getIsRikiMachine()) {
                    continue;
                }
                $buyRequest = $item->getBuyRequest();
                if (isset($buyRequest['options']['ampromo_rule_id'])) {
                    continue;
                }

                if ($item->getData('parent_item_id') != null) {
                    continue;
                }

                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_PIECE) {
                    $totalQtyShow = $totalQtyShow + $item->getQty();
                    $arrProductIdQty[$item->getProductId()] = $item->getQty();
                }

                if (strtoupper($item->getUnitCase()) == \Riki\CreateProductAttributes\Model\Product\CaseDisplay::PROFILE_UNIT_CASE) {
                    $totalQtyShow = $totalQtyShow + ($item->getQty()/$item->getUnitQty());
                    $arrProductIdQty[$item->getProductId()] = ($item->getQty()/$item->getUnitQty());
                }

                $arrProductId[] = $item->getProductId();
            }
            $objCourseHelper = $this->_objectManager->get('Riki\SubscriptionCourse\Helper\Data');
            $machinesIsValid = $this->validateMachines($courseModel, $quote);
            if (!$machinesIsValid) {
                $resultRedirect->setPath('sales/*/');
                return $resultRedirect;
            }
            $arrCategoryProductId = $objCourseHelper->arrCategoryIdQty($courseId);
            if (count($arrCategoryProductId) > 1) {
                $categoryId = $arrCategoryProductId[0];
                if ($categoryObj = $this->categoryFactory->create()->load($categoryId)) {
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

            $errorCode = $this->isNotValid($courseId, $arrProductId, $totalQtyShow, $arrProductIdQty, $quote);

            switch ($errorCode) {
                case 1:
                    $this->messageManager->addError(
                        __("shopping cart cannot contain SPOT and subscription at the same time")
                    );
                    break;
                case 3:
                    $this->messageManager->addError(
                        __(
                            "You must select at least %1 quantity product(s) belong to \"%2\" category in %3",
                            $qtyOfCategory,
                            $categoryName,
                            $courseName
                        )
                    );
                    break;
                case 4:
                    $this->messageManager->addError(
                        __(
                            "The total number of items in the shopping cart have at least %s quantity",
                            $miniShoppingCartQty
                        )
                    );
                    break;
                case 5:
                    $this->messageManager->addError(
                        __(
                            "You must select at least %1 quantity product(s) belong to \"%2\" category in %3",
                            $qtyOfCategory,
                            $categoryName,
                            $courseName
                        )
                    );
                    break;
                case 6:
                    $message = $this->subscriptionValidator->getMessageMaximumError(
                        $this->productErrors,
                        $this->maximumOrderQty
                    );
                    $this->messageManager->addError($message);
                    break;
                default:
                    // Do nothing
            }

            $arrResultApplicationLimit = $this->helperSubPage->checkApplicationLimit($quote->getCustomerId(), $courseId);
            if ($arrResultApplicationLimit['has_error'] == 1) {
                $errorCode = 6;
                $message = $this->helperSubPage->getApplicationLimitErrorMessage($arrResultApplicationLimit);
                $this->messageManager->addError($message);
            }

            if ($errorCode != 0) {
                $resultRedirect->setPath('sales/*/');
                return $resultRedirect;
            }
        }
        return $proceed();
    }

    protected function validateMachines(
        \Riki\SubscriptionCourse\Model\Course $course,
        \Magento\Quote\Model\Quote $quote
    ) {
        if ($course->getSubscriptionType() != \Riki\SubscriptionCourse\Model\Course\Type::TYPE_MULTI_MACHINES) {
            return true;
        }

        $typesOfCourse = $this->machineTypeHelper->getTypesApplicable($quote);
        if (!$typesOfCourse) {
            $this->messageManager->addError(
                __("This course does not contain machine type can be applicable")
            );
            return false;
        }
        return true;
    }

    protected function isNotValid($courseId, $arrProductId, $qty, $arrProductIdQty = null, $quote)
    {
        $objCourse = $this->_objectManager->create('Riki\SubscriptionCourse\Model\Course');
        $objCourse->load($courseId);

        if (empty($objCourse->getId())) { // not yet set course_id in quote
            return 0;
        }
        $nDelivery = 0;
        if ($this->_sessionQuote->getOrderId()) {
            if ($this->_sessionQuote->getOrderId()) {
                $order = $this->_sessionQuote->getOrder();
                if ($order instanceof \Magento\Sales\Model\Order) {
                    if ($order->getData('subscription_profile_id')) {
                        $nDelivery = $order->getData('subscription_order_time') - 1;
                    }
                }
            }
        }

        // Check product must belong course
        /** @var $objCourseHelper \Riki\SubscriptionCourse\Helper\Data */
        $objCourseHelper = $this->_objectManager->get('Riki\SubscriptionCourse\Helper\Data');
        $errorCode = $objCourseHelper->checkCartIsValidForCourse($arrProductId, $courseId, $nDelivery);

        if ($errorCode === 1) {
            return 1;
        } // have spot

        // Check at least on must have sku
        $mustHaveCatId = $objCourse->getData("must_select_sku");
        $isValid = $objCourseHelper->isValidMakeHaveInCart($arrProductId, $mustHaveCatId);
        if (!$isValid) {
            return 3;
        }

        $minimumOrderQty = $objCourse->getData("minimum_order_qty");
        if ($qty < $minimumOrderQty) {
            return 4; // Maximum limit
        }

        if (!$objCourseHelper->isValidMustHaveQtyInCategory($arrProductIdQty, $mustHaveCatId)) {
            return 5; // Minimum product qty in category
        }

        /** Validate maximum qty restriction */
        $prepareData = $this->subscriptionValidator->prepareProductDataByQuote($quote);
        $validateMaximumQty = $this->subscriptionValidator
            ->setCourseId($quote->getRikiCourseId())
            ->setProductCarts($prepareData)
            ->validateMaximumQtyRestriction();

        if ($validateMaximumQty['error'] == 1) {
            $this->productErrors = $validateMaximumQty['product_errors'];
            $this->maximumOrderQty = $validateMaximumQty['maxQty'];
            return 6;
        }

        return 0;
    }
}
