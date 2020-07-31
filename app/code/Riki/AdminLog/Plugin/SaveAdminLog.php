<?php
namespace Riki\AdminLog\Plugin;

class SaveAdminLog
{
    /**
     * @var \Riki\AdminLog\Model\LogFactory
     */
    protected $_collectionFactory;

    /**
     * @var \Riki\AdminLog\Logger\LogLoginAdminLog
     */
    protected $_logger;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\User\Model\User
     */
    protected $_userModel;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_localeDate;
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;

    /**
     * SaveAdminLog constructor.
     * @param \Riki\AdminLog\Model\LogFactory $collectionFactory
     * @param \Riki\AdminLog\Logger\LogLoginAdminLog $log
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\User\Model\User $userModel
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        \Riki\AdminLog\Model\LogFactory $collectionFactory,
        \Riki\AdminLog\Logger\LogLoginAdminLog $log,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\User\Model\User $userModel,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Framework\Stdlib\DateTime $dateTime
    ){
        $this->_collectionFactory = $collectionFactory;
        $this->_logger = $log;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_userModel = $userModel;
        $this->_localeDate = $localeDate;

    }
    /*put a function to add log*/
    public function afterSave(
        \Magento\Logging\Model\Event $subject
    ){
        $data = $subject->getData();
        /**
         *  if table log save data
         */
        if(isset($data['log_id']) && isset($data['event_code'])){
            if($data['event_code']=='admin_login'  && $data['is_success'] == true){
                unset($data['log_id']);

                $datetime = new \DateTime($data['time']);
                $dateCompare = new \Zend_Date();
                /*get config timezone*/
                $configTimezone = new \DateTimeZone($this->_timezone->getConfigTimezone());
                if($data['is_success'] == false){
                    if($data['user_id'] == null){
                        $data['error_message'] = 'User id doesn\'t exist' ;
                    }
                    else{
                        $userData = $this->_userModel->load($data['user_id']);
                        if($expirationDate = $userData->getData('lock_expires')){
                            $expirationDate = $this->_dateTime->formatDate($expirationDate, true);
                            if ($dateCompare->isEarlier($expirationDate, \Zend_Date::ISO_8601)) {
                                $data['error_message'] = ' User locked' ;
                            }
                            else{
                                $data['error_message'] = 'Wrong password' ;
                            }
                        }
                        else{
                            $data['error_message'] = 'Wrong password' ;
                        }

                    }
                }
                /*set timezone for logger*/
                $this->_logger->setTimezone($configTimezone);
                $data['time'] =$datetime->setTimezone($configTimezone);
                $adminLog = $this->_collectionFactory->create();
                $adminLog->setData($data);
                if($adminLog->save()){
                    $result = $data;
                    $result['ip'] = long2ip($result['ip']);
                    $dataJson= \Zend_Json::encode($result);
                    $this->_logger->info( "Admin login ".$data['status']."\n"."data:".$dataJson ."\n");
                }
            }
        }
        return true;
    }
}