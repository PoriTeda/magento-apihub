<?php

namespace Riki\CsvOrderMultiple\Controller\Adminhtml\Csv;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;

class Download extends Action
{

    const CSV_FOLDER = 'importexport/multiple_order_csv';
    /**
     * @var \Magento\Framework\Controller\Result\RawFactory
     */
    protected $resultFactory;
    /**
     * @var \Magento\Framework\App\Response\Http\FileFactory
     */
    protected $fileFactory;

    /**
     * @var \Magento\Framework\Filesystem\Directory\WriteInterface
     */
    protected $varDirectory;
    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resourceConnection;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * Download constructor.
     * @param Context $context
     * @param \Magento\Framework\App\Response\Http\FileFactory $fileFactory
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function __construct(
        Context $context,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Filesystem $filesystem,
        \Magento\Framework\App\ResourceConnection $resource
    ) {
        $this->fileFactory = $fileFactory;
        $this->varDirectory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->resourceConnection = $resource;
        $this->messageManager = $this->getMessageManager();
        $this->resultFactory = $context->getResultFactory();
        parent::__construct($context);
    }

    /**
     * Download execution
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function execute()
    {
        $id = $this->getRequest()->getParam('entity_id');

        if ($id) {
            $fileName = $this->getCsvFileName($id);
            if ($fileName) {
                try {
                    return $this->fileFactory->create(
                        $fileName,
                        [
                            'type' => 'filename',
                            'value' => self::CSV_FOLDER . DIRECTORY_SEPARATOR . $fileName
                        ],
                        DirectoryList::VAR_DIR,
                        'text/csv'
                    );
                } catch (\Exception $exception) {
                    $this->messageManager->addErrorMessage($exception->getMessage());
                }
            } else {
                $this->messageManager->addErrorMessage(__('File name is not found'));
            }
        } else {
            $this->messageManager->addErrorMessage(__('Id is not found'));
        }

        /**
         * @var $result \Magento\Framework\Controller\Result\Redirect
         */
        $result = $this->resultFactory->create(\Magento\Framework\Controller\ResultFactory::TYPE_REDIRECT);
        $result->setUrl($this->getUrl('csvOrderMultiple/csv/index'));
        return $result;
    }

    /**
     * @return bool
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_CsvOrderMultiple::import_order_csv_download');
    }

    /**
     * Get csv file name by id
     * @param $id
     * @return bool|string
     */
    protected function getCsvFileName($id)
    {
        $connection = $this->resourceConnection->getConnection('sales');
        $tableName = $connection->getTableName('riki_csv_order_import_history_download');
        try {
            $sql = $connection->select()
                ->from($tableName, ['file_name'])
                ->where('entity_id = ?', $id)
                ->limit(1, 0);
            $result = $connection->fetchRow($sql);

            return $result['file_name'] ?: false;
        } catch (\Exception $exception) {
            return false;
        }
    }
}
