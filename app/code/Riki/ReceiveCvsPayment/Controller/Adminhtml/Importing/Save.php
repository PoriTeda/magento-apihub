<?php
/**
 * Receive CVS Payment
 * PHP version 7
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
namespace Riki\ReceiveCvsPayment\Controller\Adminhtml\Importing;
use Magento\Backend\App\Action;
use Magento\MediaStorage\Model\File\UploaderFactory;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Class Save
 *
 * @category  RIKI
 * @package   Riki\ReceiveCvsPayment
 * @author    Nestle.co.jp <support@nestle.co.jp>
 * @copyright 2016 Riki
 * @license   https://opensource.org/licenses/MIT MIT License
 * @link      https://github.com/rikibusiness/riki-ecommerce
 */
class Save extends \Magento\Backend\App\Action
{
    /**
     * @var \Magento\Framework\Filesystem
     */
    protected $_fileSystem;
    /**
     * @var UploaderFactory
     */
    protected $_uploaderFactory;
    /**
     * @var array
     */
    protected $_allowedExtensions = ['csv'];
    /**
     * @var string
     */
    protected $_fileId = 'csv_file';
    /**
     * @var TimezoneInterface
     */
    protected $_dateTime;
    /**
     * @var DirectoryList
     */
    protected $_directoryList;
    /**
     * @var
     */
    protected $_readerCSV;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\ImportingFactory
     */
    protected $_importingFactory;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\CsvorderFactory
     */
    protected $_cvsOrderFactory;
    /**
     * @var \Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder\CollectionFactory
     */
    protected $_cvsOrderCollectionFactory;
    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $_backendSession;
    /**
     * @var \Riki\ReceiveCvsPayment\Helper\Data
     */
    protected $_helper;
    /**
     * @param Action\Context $context
     */
    public function __construct(
        Action\Context $context,
        \Magento\MediaStorage\Model\File\UploaderFactory $uploaderFactory,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\File\Csv $reader,
        \Riki\ReceiveCvsPayment\Model\ImportingFactory $importingFactory,
        \Riki\ReceiveCvsPayment\Model\ResourceModel\Csvorder\CollectionFactory $collectionFactory,
        \Riki\ReceiveCvsPayment\Model\CsvorderFactory $csvorderFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Riki\ReceiveCvsPayment\Helper\Data $dataHelper

    )
    {
        $this->_uploaderFactory = $uploaderFactory;
        $this->_dateTime = $dateTime;
        $this->_directoryList = $directoryList;
        $this->_readerCSV = $reader;
        $this->_importingFactory = $importingFactory;
        $this->_backendSession = $context->getSession();
        $this->_cvsOrderCollectionFactory = $collectionFactory;
        $this->_cvsOrderFactory = $csvorderFactory;
        $this->_fileSystem = $filesystem;
        $this->_helper = $dataHelper;
        parent::__construct($context);
    }

    /**
     * @return $this
     * @throws LocalizedException
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $uploader = $this->_uploaderFactory->create(['fileId' => $this->_fileId]);

        if ($uploader) {
            //upload file
            $destinationPath = $this->getDestinationPath();
            try {
                $uploader = $this->_uploaderFactory->create(['fileId' => $this->_fileId])
                    ->setAllowCreateFolders(true)
                    ->setAllowedExtensions($this->_allowedExtensions)
                    ->setAllowRenameFiles(true)
                    ->addValidateCallback('validate', $this, 'validateFile');
                if (!$uploader->save($destinationPath)) {
                    throw new LocalizedException(
                        __('File cannot be saved to path: $1', $destinationPath)
                    );
                }
                //success
                $data['csv_file'] = $uploader->getUploadedFileName();

            } catch (\Exception $e) {
                $this->messageManager->addError(
                    __($e->getMessage())
                );
                return $resultRedirect->setPath('*/*/edit', ['upload_id' => $this->getRequest()->getParam('upload_id')]);
            }

            $model = $this->_importingFactory->create();
            $id = $this->getRequest()->getParam('upload_id');
            if ($id) {
                $model->load($id);
            }

            $data['status'] = 0;
            $data['created']  =  $this->_dateTime->date()->format('Y-m-d H:i:s');
            $data['updated']  =  $this->_dateTime->date()->format('Y-m-d H:i:s');
            $model->setData($data);
            try {
                $model->save();
                $csvId = $model->getUploadId();
                $this->messageManager->addSuccess(__('The importing has been saved.'));
                $this->_backendSession->setFormData(false);

                //delete old ones
                $csvOrderCollection = $this->_cvsOrderCollectionFactory->create();
                $csvOrderModel = $this->_cvsOrderFactory->create();
                $csvOrderCollection->addFieldtoFilter('csv_id',$csvId)->load();
                if ($csvOrderCollection->getSize()) {
                    foreach ($csvOrderCollection as $_csvorder)
                        $_csvorder->delete();
                }
                $csvData = $this->csvToArray($destinationPath .'/'. $uploader->getUploadedFileName());
                //import new ones
                if ($csvData)
                {
                    foreach($csvData as $_data)
                    {
                        if($_data) {
                            $newarray = array();
                            $newarray['csv_id'] = $csvId;
                            $newarray['order_increment'] = $_data[0];
                            $newarray['payment_date'] = $_data[1];
                            $newarray['status'] = 0;
                            $csvOrderModel->setData($newarray);
                            try{
                                $csvOrderModel->save();

                            }
                            catch(\Magento\Framework\Exception\LocalizedException $e){
                                $this->messageManager->addError($e->getMessage());

                            }

                        }
                    }
                }
                if ($this->getRequest()->getParam('back')) {
                    return $resultRedirect->setPath('*/*/edit', ['upload_id' => $model->getId(), '_current' => true]);
                }
                return $resultRedirect->setPath('*/*/');
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\RuntimeException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Exception $e) {
                $this->messageManager->addException($e, __('Something went wrong while saving the importing.'));
            }

            $this->_backendSession->setFormData($data);
            return $resultRedirect->setPath('*/*/edit', ['upload_id' => $this->getRequest()->getParam('upload_id')]);
        }
        return $resultRedirect->setPath('*/*/');
    }

    /**
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getDestinationPath()
    {
        $varDirectory = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::VAR_DIR);
        $path = $varDirectory.'/'.'cvspayment';
        $fileObject = new File();

        if(!$fileObject->isDirectory($path))
        {
            $fileObject->createDirectory($path,0777);
        }
        return $path;

    }

    /**
     * @param $csvfile
     * @return array
     */
    public function csvToArray($csvfile)
    {
        $columnIndex  = 3;
        $datas = $this->_readerCSV->getData($csvfile);
        $totalRows = count($datas);
        for($i=0;$i < $totalRows;$i++)
        {
            for($j=0;$j<10;$j++)
            {
                if(!array_key_exists($j,$datas[$i]))
                {
                    $datas[$i][$j] = '';
                }
            }
        }
        $data = array();
        if($datas)
        {
            foreach($datas as $_data)
            {
                $data[] = [$_data[$columnIndex], $_data[7]];
            }
        }
        return $data;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_ReceiveCvsPayment::importing');
    }
}
