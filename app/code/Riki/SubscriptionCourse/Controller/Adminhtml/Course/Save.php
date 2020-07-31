<?php
/**
 *
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\SubscriptionCourse\Controller\Adminhtml\Course;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Driver\File;
use \Riki\SubscriptionCourse\Model\Course\Type;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Model\AbstractModel;

class Save extends \Magento\Backend\App\Action
{
    CONST UPLOAD_TARGET = 'subscription-course';

    /**
     * @var \Riki\SubscriptionCourse\Model\CourseFactory
     */
    protected $_courseFactory;

    /**
     * @var \Magento\Backend\Helper\Js
     */
    protected $_jsHelper;

    private $isActionEdit = false;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezoneInterface;

    /**
     * @var DirectoryList
     */
    protected $_directoryList;

    /**
     * @var File
     */
    protected $_fileDriver;

    /**
     * @var UploaderFactory
     */
    protected $_uploaderFactory;

    public function __construct
    (
        \Magento\Backend\App\Action\Context $context,
        \Riki\SubscriptionCourse\Model\CourseFactory $courseFactory,
        \Magento\Backend\Helper\Js $jsHelper,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezoneInterface,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        DirectoryList $directoryList,
        File $fileDriver

    )
    {
        $this->_timezoneInterface = $timezoneInterface;
        $this->_jsHelper = $jsHelper;
        $this->_courseFactory = $courseFactory;
        $this->_directoryList = $directoryList;
        $this->_uploaderFactory = $uploaderFactory;
        $this->_fileDriver = $fileDriver;
        parent::__construct($context);
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::save');
    }

    /**
     * Save action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();
        /** @var \Riki\SubscriptionCourse\Helper\Data $helper */

        $data = $this->getRequest()->getPostValue();
        $courseCode = $data['course_code'];
        if (isset($data['course_id'])) {
            $id = $data['course_id'];
            $this->isActionEdit = true;
        } else {
            $id = null;
        }
        /*validate unique course_code*/
        $courseModel = $this->_courseFactory->create()->getCollection();
        $courseModel->addFieldToFilter('course_code', $courseCode);
        if ($id) {
            $courseModel->addFieldToFilter('course_id', ['neq' => $id]);
        }
        if ($courseModel->getSize() > 0) {
            $this->messageManager->addError(__('This course code already exists.'));
            if ($id) {
                return $resultRedirect->setPath('*/*/edit', ['course_id' => $id, '_current' => true]);
            }
            $type = isset($data['subscription_type']) ? $data['subscription_type'] : Type::TYPE_SUBSCRIPTION;
            if ($type == Type::TYPE_HANPUKAI) {
                $type = isset($data['hanpukai_type']) ? $data['hanpukai_type'] : Type::TYPE_SUBSCRIPTION;
            }
            return $resultRedirect->setPath('*/*/new', ['type' => $type, '_current' => true]);
        }

        if ($this->isActionEdit) {
            if (isset($data['is_delay_payment']) ||
                isset($data['is_shopping_point_deduction']) ||
                isset($data['payment_delay_time'])
            ) {
                $this->messageManager->addError(__('Not allowed to changes the config of the delay payment'));
                if ($id) {
                    return $resultRedirect->setPath('*/*/edit', ['course_id' => $id, '_current' => true]);
                }
                $type = isset($data['subscription_type']) ? $data['subscription_type'] : Type::TYPE_SUBSCRIPTION;
                if ($type == Type::TYPE_HANPUKAI) {
                    $type = isset($data['hanpukai_type']) ? $data['hanpukai_type'] : Type::TYPE_SUBSCRIPTION;
                }
                return $resultRedirect->setPath('*/*/new', ['type' => $type, '_current' => true]);
            }
        }
        $returnToEdit = false;

        if ($data) {
            $model = $this->_courseFactory->create()->load($id);
            if (!$model->getId() && $id) {
                $this->messageManager->addError(__('This course no longer exists.'));
                return $resultRedirect->setPath('*/*/');
            }
            // check add set default value for hanpukai setting
            $isHanpukaiType = false;
            if ($model->getId()) {
                if ($model->getData('subscription_type') == Type::TYPE_HANPUKAI) {
                    $isHanpukaiType = true;
                }
            } else {
                if (isset($data['subscription_type']) &&
                    $data['subscription_type'] == Type::TYPE_HANPUKAI) {
                    $isHanpukaiType = true;
                }
            }
            if (!array_key_exists('membership_ids', $data)) {
                $model->setMembershipIds(null);
            }
            $data = $this->setProductData($data);

            $data = $this->setMachineData($data);

            if (!isset($data['category_ids'])) {
                $model->setCategoryIds(null);
            }

            if (!isset($data['additional_category_ids'])) {
                $model->setAdditionalCategoryIds(null);
            }

            if (!array_key_exists('merge_profile_to', $data)) {
                $model->setMergeProfileTo(null);
            }

            if (!isset($data['profile_category_ids'])) {
                $model->setProfileCategoryIds(null);
            }

            $data = $this->processUploadFile($data);
            $data = $this->validateData($data);
            $model->addData($data);

            if (!$model->getData('last_order_time_is_delay_payment')) {
                $model->setLastOrderTimeIsDelayPayment(null);
            }

            $errors = $model->validate();
            if (!empty($errors)) {
                foreach ($errors as $errorMessage) {
                    $this->messageManager->addError($errorMessage);
                }
                $returnToEdit = true;
                $this->_getSession()->setFormData($data);
            } else {
                try {
                    if ($isHanpukaiType == true) {
                        $model->setData('allow_skip_next_delivery', 0);
                        $model->setData('allow_change_product', 0);
                        $model->setData('allow_change_qty', 0);
                    }

                    $model->save();
                    $id = $model->getId();

                    $this->messageManager->addSuccess(__('You saved the course.'));
                    // clear previously saved data from session
                    $this->_getSession()->setFormData(false);

                    $returnToEdit = (bool)$this->getRequest()->getParam('back', false);
                } catch (\Exception $e) {
                    // display error message
                    $this->messageManager->addError($e->getMessage());
                    // save data in session
                    $this->_getSession()->setFormData($data);

                    $returnToEdit = true;
                }
            }
        }

        $resultRedirect = $this->resultRedirectFactory->create();
        if ($returnToEdit) {
            if ($id) {
                $resultRedirect->setPath(
                    '*/*/edit',
                    ['course_id' => $id, '_current' => true]
                );
            } else {
                $type = isset($data['subscription_type']) ? $data['subscription_type'] : \Riki\SubscriptionCourse\Model\Course\Type::TYPE_SUBSCRIPTION;
                if ($type == \Riki\SubscriptionCourse\Model\Course\Type::TYPE_HANPUKAI) {
                    $type = isset($data['hanpukai_type']) ? $data['hanpukai_type'] : \Riki\SubscriptionCourse\Model\Course\Type::TYPE_SUBSCRIPTION;
                }
                $resultRedirect->setPath(
                    '*/*/new',
                    ['type' => $type, '_current' => true]
                );
            }
        } else {
            $resultRedirect->setPath('*/*');
        }
        return $resultRedirect;
    }

    /**
     * Validate Data
     *
     * @param array $data
     *
     * @return array $data
     */
    public function validateData($data)
    {
        if (!$this->isActionEdit) {
            if (isset($data['is_delay_payment']) && $data['is_delay_payment'] == 0) {
                $data['is_shopping_point_deduction'] = 0;
                $data['payment_delay_time'] = null;
            }
        }
        if (isset($data['order_total_amount_option'])) {
            if ((int)$data['order_total_amount_option'] == 2 && isset($data['minimum_amount'])) {
                $multiAmount = [];
                if (is_array($data['minimum_amount'])) {
                    foreach ($data['minimum_amount'] as $minAmount) {
                        $multiAmount[] = [
                            'from_order_time' => $minAmount['order_from'],
                            'to_order_time' => $minAmount['order_to'],
                            'amount' => $minAmount['minimum_amount']
                        ];
                    }
                }
                $dataRestriction = [
                    'minimum' => [
                        'option' => $data['order_total_amount_option'],
                        'amounts' => $multiAmount
                    ],
                    'maximum' => [
                        'amount' => $data['oar_maximum_amount_threshold']
                    ]
                ];
            } else {
                $dataRestriction = [
                    'minimum' => [
                        'option' => $data['order_total_amount_option'],
                        'amount' => $data['oar_minimum_amount_threshold']
                    ],
                    'maximum' => [
                        'amount' => $data['oar_maximum_amount_threshold']
                    ]
                ];
            }
            $data['oar_condition_serialized'] = json_encode($dataRestriction);
        }

        // Set value to null
        $fieldToCheck = [
            'minimum_order_times',
            'sales_count',
            'sales_value_count'
        ];

        foreach ($fieldToCheck as $field) {
            if (empty($data[$field])) {
                $data[$field] = null;
            }
        }


        // Validate data for maximum qty
        if (isset($data['maximum_qty_restriction_option'])) {
            if ((int)$data['maximum_qty_restriction_option'] == 3 && isset($data['maximum_qty'])) {
                $multiQty = [];
                if (is_array($data['maximum_qty'])) {
                    foreach ($data['maximum_qty'] as $maxQty) {
                        $multiQty[] = [
                            'from_order_time' => $maxQty['order_from'],
                            'to_order_time' => $maxQty['order_to'],
                            'qty' => $maxQty['maximum_qty']
                        ];
                    }
                }
                $dataRestriction = [
                    'maximum' => [
                        'option' => $data['maximum_qty_restriction_option'],
                        'qtys' => $multiQty
                    ]
                ];
            } else {
                $dataRestriction = [
                    'maximum' => [
                        'option' => $data['maximum_qty_restriction_option'],
                        'qty' => $data['oqr_maximum_qty_restriction']
                    ]
                ];
            }
            $data['maximum_qty_restriction'] = json_encode($dataRestriction);
        }
        return $data;
    }

    /**
     * Set Product Data
     *
     * @param array $data
     *
     * @return array $data
     */
    public function setProductData($data)
    {
        if (isset($data['products'])) {
            $data['products'] = $this->_jsHelper->decodeGridSerializedInput($data['products']);
            $data['post_products'] = $data['products'];
        }
        if (isset($data['post_products'])) {
            foreach ($data['post_products'] as $itemId => $itemUnitCase) {
                $data['post_products'][$itemId]['qty'] = ((int)$data['post_products'][$itemId]['qty'] > 0) ? (int)$data['post_products'][$itemId]['qty'] : 1;
                if (isset($itemUnitCase['unit_case']) && isset($itemUnitCase['unit_qty'])) {
                    $data['post_products'][$itemId]['unit_case'] = strtoupper($itemUnitCase['unit_case']);
                    $data['post_products'][$itemId]['unit_qty'] = (int)$itemUnitCase['unit_qty'];

                    if ('CS' == $data['post_products'][$itemId]['unit_case']) {
                        $data['post_products'][$itemId]['qty'] = (int)$data['post_products'][$itemId]['unit_qty'] * $data['post_products'][$itemId]['qty'];
                    }
                }
            }
            $data['products'] = $data['post_products'];
        }
        return $data;
    }

    /**
     * Set Machine Data
     *
     * @param array $data
     *
     * @return array $data
     */
    public function setMachineData($data)
    {
        if (isset($data['machines'])) {
            $data['machines'] = $this->_jsHelper->decodeGridSerializedInput($data['machines']);
            // mapping grid view data
            foreach ($data['machines'] as $itemId => $machine) {
                if (isset($data['product_machine'][$itemId])) {
                    $data['machines'][$itemId]['is_free'] = $data['product_machine'][$itemId];
                }
                if (isset($data['wbs'][$itemId])) {
                    $data['machines'][$itemId]['wbs'] = $data['wbs'][$itemId];
                }
            }
        }
        return $data;
    }

    /**
     * Set Machine Types Data
     *
     * @param array $data
     *
     * @return array $data
     */
    public function setMachineTypeData($data)
    {
        if (isset($data['multi_machine'])) {
            $data['multi_machine'] = $this->_jsHelper->decodeGridSerializedInput($data['multi_machine']);
        }
        return $data;
    }

    public function processUploadFile($data)
    {
        $destinationPath = $this->getDestinationPath();
        $deleteFile = $this->getRequest()->getParam('terms_of_use_delete');
        if ($deleteFile) {
            $file = $destinationPath . DIRECTORY_SEPARATOR . $data['terms_of_use'];
            if ($this->_fileDriver->isExists($file)) {
                unlink($destinationPath . DIRECTORY_SEPARATOR . $data['terms_of_use']);
                $data['terms_of_use'] = null;

                return $data;
            } else {
                $this->messageManager->addError(__('No such file exists in directory'));
            }
        }
        if ($this->getRequest()->getFiles('terms_of_use')['name']) {
            try {
                $uploader = $this->_uploaderFactory->create(['fileId' => 'terms_of_use'])
                    ->setAllowCreateFolders(true)
                    ->setAllowRenameFiles(true)
                    ->addValidateCallback('validate', $this, 'validateFile');
                //success
                if (!$uploader->save($destinationPath)) {
                    throw new \Magento\Framework\Exception\LocalizedException(
                        __('File cannot be saved to path: $1', $destinationPath)
                    );
                }
                $data['terms_of_use'] = $uploader->getUploadedFileName();
            } catch (\Exception $e) {
                $this->messageManager->addError('Error: ' . $e);
            }
        }

        return $data;
    }

    public function getDestinationPath()
    {
        $mediaDirectory = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
        $path = $mediaDirectory . '/' . self::UPLOAD_TARGET;
        $fileObject = new File();

        if (!$fileObject->isDirectory($path)) {
            $fileObject->createDirectory($path, 0777);
        }
        return $path;
    }
}