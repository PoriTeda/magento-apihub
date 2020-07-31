<?php

namespace Riki\GiftWrapping\Controller\Adminhtml\Import;

class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\MediaStorage\Model\File\UploaderFactory
     */
    protected $_uploaderFactory ;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csvReader;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\DateTime
     */
    protected $_datetime;
    /**
     * @var \Riki\GiftWrapping\Logger\Logger
     */
    protected $loggerImport;
    /**
     * @var \Magento\GiftWrapping\Api\WrappingRepositoryInterface
     */
    protected $wrappingRepository;
    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    protected $productRepository;
    /**
     * @var \Magento\Framework\Api\SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;
    
    protected $_coreRegistry = null;

    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\File\Csv $csv,
        \Magento\GiftWrapping\Api\WrappingRepositoryInterface $wrappingRepository,
        \Magento\Framework\Stdlib\DateTime\DateTime $dateTime,
        \Riki\GiftWrapping\Logger\Logger $logger,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Registry $registry,
        \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
        parent::__construct($context);
        $this->_uploaderFactory = $uploaderFactory;
        $this->_csvReader = $csv;
        $this->wrappingRepository = $wrappingRepository;
        $this->_datetime = $dateTime;
        $this->loggerImport = $logger;
        $this->loggerImport->setTimezone(new \DateTimeZone($timezone->getConfigTimezone()));
        $this->productRepository = $productRepository;
        $this->_coreRegistry = $registry;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    /**
     * {@inheritdoc}
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_GiftWrapping::import_giftwrapping');
    }

    public function execute()
    {

        $uploadFile = $this->_uploaderFactory->create(['fileId' => 'csv_import_giftwrapping']);
        $uploadFile->setAllowedExtensions(['csv']);
        $fileImport = $uploadFile->validateFile();

        // read file and import data
        $this->importMapProduct($fileImport);

        //$resultMessage = $this->_resultMessage;
        //$this->messageManager->addSuccess($resultMessage);
        //$this->_coreRegistry->register('result',$resultMessage);

        $resultRedirect = $this->resultRedirectFactory->create();
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * Load product by specified sku
     *
     * @param string $sku
     * @return bool|Product
     */
    protected function loadProductBySku($sku)
    {
        try {
            $product = $this->productRepository->get($sku, false, 0, false);
        } catch (\Exception $e) {
            return false;
        }
        return $product;
    }

    public function loadWrappingId($giftCode)
    {
        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('gift_code', $giftCode, 'eq')
            ->setPageSize(1)
            ->create();

        $searchResults = $this->wrappingRepository->getList($searchCriteria);
        if (!$searchResults->getTotalCount()) {
            return false;
        }
        $giftData = $searchResults->getItems();

        return current($giftData)->getWrappingId();
    }

    protected $_resultMessage = '';


    public function importMapProduct($file)
    {
        $errors = 0;
        $tmpName = $file['tmp_name'];
        $dataFile = $this->_csvReader->getData($tmpName);
        $row = 0;
        $productUpdate = [];

        $this->loggerImport->info(__('=================== START ====================='));
        //$this->_resultMessage .= "\r\n=================== START =====================\r\n";

        foreach ($dataFile as $val) {
            if ($row == 0) {
                $row++;
                continue;
            }

            if (!isset($val[1])) {
                $this->loggerImport->info(sprintf('GIFT_CODE null, Import unsuccessful row %s', $row));
                //$this->_resultMessage .= "\r\n".sprintf('GIFT_CODE null, Import unsuccessful row %s', $row)."\r\n";
                $this->messageManager->addError(sprintf('GIFT_CODE null, Import unsuccessful row %s', $row));
                $row++;$errors++;
                continue;
            }
            if (!isset($val[2])) {
                $this->loggerImport->info(sprintf('COMMODITY_CODE null, Import unsuccessful row %s', $row));
                //$this->_resultMessage .= "\r\n".sprintf('COMMODITY_CODE null, Import unsuccessful row %s', $row)."\r\n";
                $this->messageManager->addError(sprintf('COMMODITY_CODE null, Import unsuccessful row %s', $row));
                $row++;$errors++;
                continue;
            }
            $giftCode = $val[1]; // GiftWrapping Code
            $commodityCode = $val[2]; //SKU Product

            // get gift wrapping id
            $giftId = $this->loadWrappingId($giftCode);
            if (!$giftId) {
                $this->loggerImport->info(sprintf('Can not load GIFT WRAPPING from GIFT_CODE = %s, Import unsuccessful row %s',$giftCode, $row));
                //$this->_resultMessage .= "\r\n".sprintf('Can not load GIFT WRAPPING from GIFT_CODE = %s, Import unsuccessful row %s',$giftCode, $row)."\r\n";
                $this->messageManager->addError(sprintf('Can not load GIFT WRAPPING from GIFT_CODE = %s, Import unsuccessful row %s',$giftCode, $row));
                $row++;
                $errors++;
                continue;
            }
            //load product from sku (commondity code)
            $product = $this->loadProductBySku($commodityCode);
            if (!$product) {
                $this->loggerImport->info(sprintf('Can not load PRODUCT from COMMODITY_CODE = %s, Import unsuccessful row %s', $commodityCode, $row));
                //$this->_resultMessage .= "\r\n".sprintf('Can not load PRODUCT from COMMODITY_CODE = %s, Import unsuccessful row %s', $commodityCode, $row)."\r\n";
                $this->messageManager->addError(sprintf('Can not load PRODUCT from COMMODITY_CODE = %s, Import unsuccessful row %s', $commodityCode, $row));
                $row++;
                $errors++;
                continue;
            }

            //group data giftwrapping if for product
            if(array_key_exists($commodityCode, $productUpdate)) {
                $productUpdate[$commodityCode] = $productUpdate[$commodityCode].','.$giftId;
            } else {
                $productUpdate[$commodityCode] = $giftId;
            }
            $row++;
        }

        if (count($productUpdate)) {
            $this->_updateGiftWrappingToProduct($productUpdate);
        }

        if($errors > 0){
            $this->loggerImport->info(sprintf('TOTAL ERROR : %s', $errors));
            //$this->_resultMessage .= "\r\n".sprintf('TOTAL ERROR : %s', $errors)."\r\n";
            $this->messageManager->addError(sprintf('TOTAL ERROR : %s', $errors));
        }
        $this->loggerImport->info(sprintf('TOTAL ROWS READED: %s', $row-1));
        $this->loggerImport->info(__('==================== END ===================='));

        //$this->_resultMessage .= "\r\n".sprintf('TOTAL ROWS READED: %s', $row-1)."\r\n";
        //$this->_resultMessage .= "\r\n==================== END ====================\r\n";
        $this->messageManager->addSuccess(sprintf('TOTAL ROWS READED: %s', $row-1));
        $this->messageManager->addSuccess(__('VIEW MORE DETAIL RESULT FOR IMPORT IN THE LOG FILE : var/importGiftWrapping.log'));
    }

    private function _updateGiftWrappingToProduct($productUpdate)
    {
        $update = 0;
        foreach ($productUpdate as $sku => $giftId) {
            $product = $this->loadProductBySku(trim($sku));
            try {
                $product->setData('gift_wrapping', $giftId);
                $product->save();
                $update++;
                $this->loggerImport->info(sprintf('PRODUCT SKU = %s IMPORTED GIFTWRAPPING SUCCESSFUL', $product->getSku()));
            } catch (\Exception $e) {
                $this->messageManager->addError(sprintf('Can not save GIFT WRAPPING for PRODUCT SKU = %s', $product->getSku()));
            }
        }
        $this->loggerImport->info(sprintf('TOTAL PRODUCTS IMPORTED : %s', $update));
        //$this->_resultMessage .= "\r\n".sprintf('TOTAL PRODUCTS IMPORTED : %s', $update)."\r\n";
        $this->messageManager->addSuccess(sprintf('TOTAL PRODUCTS IMPORTED : %s', $update));
        return $this;
    }
}
