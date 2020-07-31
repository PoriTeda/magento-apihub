<?php

namespace Riki\User\Controller\Adminhtml\Export;

use Magento\Backend\App\Action\Context;
use Magento\User\Model\ResourceModel\User\Collection as UserCollection;
use Riki\AdminLog\Model\ResourceModel\Log\Collection as LoggingCollection;
use Magento\Framework\Controller\Result\RawFactory;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Csv;
use Magento\ImportExport\Helper\Report as ReportHelper;
use Magento\Framework\Filesystem;
class Index extends \Magento\Backend\App\Action
{

    protected $userCollection;
    protected $adminLoggingCollection;
    protected $fileFactory;
    protected $rawFactory;
    protected $csvWritter;
    protected $directoryList;
    protected $reportHelper;
    protected $fileSystem;

    public function __construct(
        Context $context,
        UserCollection $collectionFactory,
        LoggingCollection $loggingCollection,
        RawFactory $rawFactory,
        FileFactory $fileFactory,
        Csv $csvWritter,
        DirectoryList $directoryList,
        ReportHelper $reportHelper,
        FileSystem $filesystem
    )
    {
        $this->userCollection = $collectionFactory;
        $this->adminLoggingCollection = $loggingCollection;
        $this->fileFactory = $fileFactory;
        $this->rawFactory = $rawFactory;
        $this->csvWritter = $csvWritter;
        $this->directoryList = $directoryList;
        $this->reportHelper = $reportHelper;
        $this->fileSystem = $filesystem->getDirectoryWrite(DirectoryList::VAR_DIR);
        parent::__construct($context);
    }

    public function execute()
    {
        $userCollection = $this->userCollection->load();
        $logTime = $this->_getLastestAdminLogin();
        $dataCSV = array();
        //header
        $dataCSV[] = implode(',',[
            'User ID',
            'User Name',
            'Group Name',
            'Account Lock Satus',
            'Last Login',
            'Last Password Change'
        ]);
        $lockStatus = array('Inactive', "Active");
        if($userCollection->getSize()) {
            foreach($userCollection as $_userAdmin) {
                $lastLogin =  '';
                $userId = $_userAdmin->getUserId();
                if(array_key_exists($userId, $logTime)) {
                    $lastLogin = $logTime[$userId];
                }
                $dataCSV[] = implode(',',[
                  $userId,
                  $_userAdmin->getFirstName() . ' ' . $_userAdmin->getLastName(),
                  $_userAdmin->getRole()->getRoleName(),
                  $lockStatus[$_userAdmin->getIsActive()],
                  $lastLogin,
                  $_userAdmin->getModified()

                ]);
            }

        }
        $fileName = 'Riki_Admin_Accounts_'.date('YmdHis'). '.csv';
        $this->fileFactory->create(
            $fileName,
            implode("\r\n",$dataCSV),
            DirectoryList::VAR_DIR,
            'text/csv'
        );
        $resultRaw = $this->rawFactory->create();
        //remove file
        $filePath = $this->directoryList->getPath(DirectoryList::VAR_DIR);
        return $resultRaw;
    }

    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Riki_User::rikiuser');
    }

    /**
     * @return array
     */
    private function _getLastestAdminLogin()
    {
        $loggingCollection = $this->adminLoggingCollection
                            ->addFieldToFilter('status','success')
                            ->addFieldToFilter('event_code', 'admin_login')
                            ->setOrder('time', 'DESC');
        $loggingCollection->getSelect()
            ->reset(\Zend_Db_Select::COLUMNS)
            ->columns(array('user_id', 'status', 'event_code', 'MAX(time) as logged_time'))
            ->group(array('user_id', 'status', 'event_code'));
        $data = array();
        if($loggingCollection->getSize()){
            foreach($loggingCollection as $_log){
                $data[$_log->getUserId()] = $_log->getData('logged_time');
            }
        }
        return $data;
    }

    public function getOutputFile($fileName)
    {
        return $this->fileSystem->readFile(
            $fileName
        );
    }

    public function getReportSize($fileName)
    {
        return $this->fileSystem->stat($fileName)['size'];
    }
}
