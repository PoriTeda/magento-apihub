<?php

namespace Riki\PointOfSale\Controller\Adminhtml\Manage;

use Wyomind\PointOfSale\Model\PointOfSaleFactory;

class Save extends \Wyomind\PointOfSale\Controller\Adminhtml\Manage\Save
{
    /**
     * @var \Wyomind\PointOfSale\Model\PointOfSale
     */
    protected $modelpos;
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $directoryList;

    protected $posFactory;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Directory\Model\ResourceModel\Region\Collection $regionCollection,
        \Magento\Framework\Registry $coreRegistery,
        \Wyomind\PointOfSale\Model\ResourceModel\PointOfSale\Collection $posCollection,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $posModelFactory,
        \Magento\Backend\Model\View\Result\ForwardFactory $resultForwardFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        \Wyomind\Core\Helper\Data $coreHelper,
        \Wyomind\PointOfSale\Model\PointOfSale $pointOfSale,
        \Wyomind\PointOfSale\Model\PointOfSaleFactory $pointOfSaleFactory,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList
    ) {
        $this->modelpos = $pointOfSale;
        $this->directoryList = $directoryList;
        $this->posFactory = $pointOfSaleFactory;
        parent::__construct(
            $context,
            $resultPageFactory,
            $regionCollection,
            $coreRegistery,
            $posCollection,
            $posModelFactory,
            $resultForwardFactory,
            $resultRawFactory,
            $coreHelper
        );
    }
    public function execute()
    {
        // check if data sent
        $data = $this->getRequest()->getPost();
        if ($data) {
            $id = $this->getRequest()->getParam('place_id');
            if ($id) {
                $model = $this->modelpos->load($id);
            } else {
                $model = $this->posFactory->create();
            }
            if (isset($data["image"]["delete"]) && $data["image"]["delete"] == 1) {
                $data["image"] = "";
            } else {
                try {
                    /* Starting upload */
                    $uploader = new \Magento\Framework\File\Uploader("image");
                    // Any extention would work
                    $uploader->setAllowedExtensions(["jpg", "jpeg", "gif", "png"]);
                    $uploader->setAllowRenameFiles(true);
                    // Set the file upload mode
                    // false -> get the file directly in the specified folder
                    // true -> get the file in the product like folders
                    //	(file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);
                    $uploader->setAllowCreateFolders(true);
                    // We set media as the upload dir
                    $path = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::MEDIA);
                    $path.=DIRECTORY_SEPARATOR;
                    $uploader->save($path . "stores", null);
                    $imageName = $uploader->getUploadedFileName();
                    //this way the name is saved in DB
                    $data["image"] = "stores/" . preg_replace('/[^a-z0-9_\\-\\.]+/i', '_', $imageName);
                } catch (\Exception $e) {
                    if (isset($data["image"])) {
                        unset($data["image"]);
                    }
                }
            }
            if (in_array('-1', $data["customer_group"])) {
                $data["customer_group"] = ["-1"];
            }
            $data["customer_group"] = implode(',', $data["customer_group"]);

            if (in_array('0', $data["store_id"])) {
                $data["store_id"] = ["0"];
            }
            $data["store_id"] = implode(',', $data["store_id"]);

            // Add Delivery Type to warehouse
            if ($data["deliverytype_enable_list"] == null) {
                $data["deliverytype_enable_list"] = ["0"];
            }
            $data["deliverytype_enable_list"] = implode(',', $data["deliverytype_enable_list"]);
            foreach ($data as $index => $value) {
                $model->setData($index, $value);
            }
            if (!$this->_validatePostData($data)) {
                return $this->_resultRedirectFactory->create()->setPath(
                    'pointofsale/manage/edit',
                    ['id' => $model->getId(),'_current' => true]
                );
            }

            try {
                $model->save();
                $this->messageManager->addSuccess(__('The POS has been saved.'));
                $this->_session->setFormData(false);

                if ($this->getRequest()->getParam('back_i') == "1") {
                    return $this->_resultRedirectFactory->create()->setPath(
                        'pointofsale/manage/edit',
                        ['id' => $model->getId(), '_current' => true]
                    );
                }

                $this->_getSession()->setFormData($data);
                return $this->_resultRedirectFactory->create()->setPath('pointofsale/manage/index');
            } catch (\Exception $e) {
                $this->messageManager->addError(__('Unable to save the POS.') . '<br/><br/>' . $e->getMessage());
                return $this->_resultRedirectFactory->create()->setPath(
                    'pointofsale/manage/edit',
                    ['id' => $model->getPlaceId(), '_current' => true]
                );
            }
        }
        return $this->_resultRedirectFactory->create()->setPath('pointofsale/manage/index');
    }
}
