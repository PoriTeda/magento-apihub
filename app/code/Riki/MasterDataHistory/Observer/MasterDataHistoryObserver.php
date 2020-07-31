<?php
namespace Riki\MasterDataHistory\Observer;

class MasterDataHistoryObserver
{
    /**
     * @var \Magento\Framework\App\Filesystem\DirectoryList
     */
    protected $_directoryList;
    /**
     * @var \Magento\Framework\Filesystem\Driver\File
     */
    protected $_file;
    /**
     * @var \Magento\Framework\File\Csv
     */
    protected $_csv;
    /**
     * @var DateTime
     */
    protected $_datetime;
    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $authSession;
    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Framework\App\Request\Http
     */
    protected $_request;

    /**
     * MasterDataHistoryObserver constructor.
     * @param \Magento\Framework\App\Filesystem\DirectoryList $directoryList
     * @param \Magento\Framework\Filesystem\Driver\File $file
     * @param \Magento\Framework\File\Csv $csv
     * @param \Magento\Framework\Stdlib\DateTime\Timezone $timezone
     * @param \Magento\Backend\Model\Auth\Session $authSession
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\App\Request\Http $request
     */
    public function __construct(
        \Magento\Framework\App\Filesystem\DirectoryList $directoryList,
        \Magento\Framework\Filesystem\Driver\File $file,
        \Magento\Framework\File\Csv $csv,
        \Magento\Framework\Stdlib\DateTime\Timezone $timezone,
        \Magento\Backend\Model\Auth\Session $authSession,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http $request
    )
    {
        if (defined('DS') === false) define('DS', DIRECTORY_SEPARATOR);
        $this->_directoryList = $directoryList;
        $this->_file = $file;
        $this->_csv = $csv;
        $this->_datetime = $timezone;
        $this->authSession = $authSession;
        $this->scopeConfig = $scopeConfig;
        $this->_request = $request;
    }
    /**
     * @param $dirFolder
     * @return bool|string
     */
    public function createFileLocal($dirFolder){
        $baseDir = $this->_directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        if(trim($dirFolder,-1) == DS){
            $dirFolder = str_replace(DS,'',$dirFolder);
        }
        $createFileLocal = $baseDir . DS . $dirFolder;
        if(!$this->_file->isDirectory($createFileLocal)){
            if(!$this->_file->createDirectory($createFileLocal)){
                return false;
            }
        }
        //
        if(!$this->_file->isWritable($createFileLocal)){
            return false;
        }
        return $createFileLocal;
    }
    /**
     * @return mixed|string
     */
    public function getCurrentUserAdmin(){
        /**
         * @var \Magento\User\Model\User
         */
        $user = $this->authSession->getUser();
        if($user instanceof \Magento\User\Model\User ){
            return $user->getUserName();
        }else{
            return '';
        }
    }
    /**
     * @param $key
     * @return mixed
     */
    public function getConfig($key)
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        return $this->scopeConfig->getValue($key,$storeScope);
    }
    /**
     * @return string
     */
    public function getActionName(){
        if($this->_request->getParam('id')){
            return 'Update';
        }else{
            return 'Add';
        }
    }
}