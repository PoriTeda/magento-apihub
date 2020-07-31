<?php
namespace Riki\SubscriptionCourse\Controller\Adminhtml\Export;

use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\App\Filesystem\DirectoryList;
use Symfony\Component\Config\Definition\Exception\Exception;
use Magento\Framework\Filesystem\Driver\File;

class GridToCsvSubscriptionCourse extends \Magento\Ui\Controller\Adminhtml\Export\GridToCsv
{
    /**
     * @var WriteInterface
     */
    protected $directory;

    /* @var \Riki\SubscriptionCourse\Controller\Adminhtml\Export\GetDataExport */
    protected $dataExport;

    /* @var \Magento\Framework\App\Filesystem\DirectoryList */
    protected $directoryList;

    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Riki\SubscriptionCourse\Controller\Adminhtml\Export\GetDataExport $getDataExport,
        Filesystem $filesystem,
        \Magento\Backend\App\Action\Context $context,
        \Magento\Ui\Model\Export\ConvertToCsv $converter,
        \Riki\SubscriptionCourse\Controller\Adminhtml\Export\MultiFileFactory $fileFactory
    ){
        parent::__construct($context, $converter, $fileFactory);
        $this->directory = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        $this->dataExport = $getDataExport;
        $this->directoryList = $directoryList;
    }

    public function execute()
    {
        $arrContentFile = array();
        $arrTableName = ['subscription_course', 'subscription_course_category', 'subscription_course_frequency',
            'subscription_course_membership', 'subscription_course_payment', 'subscription_course_product',
            'subscription_course_website','subscription_course_merge_profile'];

        // make folder to storage csv file export
        $folderName = 'subscription-course'. md5(microtime());
        $baseDir = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        $localPath = $baseDir .'/export'. '/'.$folderName;
        $fileObject = new File();
        if (!$fileObject->isExists($localPath)) {
            $fileObject->createDirectory($localPath, 0777);
        }

        foreach ($arrTableName as $tableName) {
            $arrContentFile[] = $this->getCsvFileByTableName($tableName, $folderName);
        }
        $dataZip = $this->fileFactory->createMultiFile($arrContentFile, 'var', $localPath);
        // delete folder after make export folder
        $fileObject->deleteDirectory($localPath);
        return $this->fileFactory->create('subscription-course.zip', $dataZip, 'var');
    }

    /**
     * @param $tableName
     *
     * @return array
     */
    public function getCsvFileByTableName($tableName, $folderName)
    {
        $name = md5(microtime());
        $file = 'export/'.$folderName.'/'. $tableName . $name . '.csv';
        $arrDataExport = $this->dataExport->getDataExportByTableName($tableName);
        $this->directory->create('export');
        $stream = $this->directory->openFile($file, 'w+');
        $stream->lock();
        foreach ($arrDataExport as $data) {
            $stream->writeCsv($data);
        }
        $stream->unlock();
        $stream->close();

        return [
            'type' => 'filename',
            'value' => $file,
            'rm' => true  // can delete file after use
        ];
    }

    /**
     * @return boolean
     */
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_SubscriptionCourse::export_csv');
    }
}