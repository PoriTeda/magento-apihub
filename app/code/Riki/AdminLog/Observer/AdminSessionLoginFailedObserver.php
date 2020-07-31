<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\AdminLog\Observer;

use Magento\Framework\Exception\State\UserLockedException;
use Magento\Framework\Event\ObserverInterface;

class AdminSessionLoginFailedObserver implements ObserverInterface
{
    /**
     * @var \Riki\AdminLog\Model\LogFactory
     */
    protected $_collectionLogAdminFactory;

    /**
     * @var \Riki\AdminLog\Logger\LogLoginAdminLog
     */
    protected $_logger;
    /**
     * @var \Magento\Logging\Model\Config
     */
    protected $_config;

    /**
     * @var \Magento\User\Model\User
     */
    protected $_user;

    /**
     * @var \Magento\Logging\Model\Event
     */
    protected $_event;

    /**
     * Request
     *
     * @var \Magento\Framework\App\RequestInterface
     */
    protected $_request;

    /**
     * @var \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress
     */
    protected $_remoteAddress;
    /**
     * @var AdminLogin
     */
    protected $adminLogin;
    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $_timezone;
    /**
     * @var \Magento\Framework\Stdlib\DateTime
     */
    protected $_dateTime;
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @param AdminLogin $adminLogin
     */
    /**
     * AdminSessionLoginFailedObserver constructor.
     * @param \Magento\Logging\Observer\AdminLogin $adminLogin
     * @param \Magento\Logging\Model\Config $config
     * @param \Magento\User\Model\User $user
     * @param \Magento\Logging\Model\Event $event
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Riki\AdminLog\Logger\LogLoginAdminLog $log
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     * @param \Riki\AdminLog\Model\LogFactory $collectionLogAdminFactory
     * @param \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress
     * @param \Magento\Framework\Registry $registry
     */
    public function __construct(
        \Magento\Logging\Observer\AdminLogin $adminLogin,
        \Magento\Logging\Model\Config $config,
        \Magento\User\Model\User $user,
        \Magento\Logging\Model\Event $event,
        \Magento\Framework\App\RequestInterface $request,
        \Riki\AdminLog\Logger\LogLoginAdminLog $log,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $timezone,
        \Magento\Framework\Stdlib\DateTime $dateTime,
        \Riki\AdminLog\Model\LogFactory $collectionLogAdminFactory,
        \Magento\Framework\HTTP\PhpEnvironment\RemoteAddress $remoteAddress,
        \Magento\Framework\Registry $registry
    ) {
        $this->_collectionLogAdminFactory = $collectionLogAdminFactory;
        $this->_logger = $log;
        $this->adminLogin = $adminLogin;
        $this->_config = $config;
        $this->_user = $user;
        $this->_event = $event;
        $this->_request = $request;
        $this->_remoteAddress = $remoteAddress;
        $this->_dateTime = $dateTime;
        $this->_timezone = $timezone;
        $this->_coreRegistry = $registry;
    }

    /**
     * Log failure of sign in
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        $eventModel = $this->adminLogin->logAdminLogin($observer->getUserName());
        
        if ($eventModel) {
            $eventCode = 'admin_login';
            $data = array();
            $datetime = new \DateTime();
            $configTz = new \DateTimeZone($this->_timezone->getConfigTimezone());
            $data['ip'] = $this->_remoteAddress->getRemoteAddress();
            $data['user'] = $observer->getUserName();
            $data['user_id'] = $userId = $this->_user->loadByUsername($observer->getUserName())->getId();;
            $data['is_success'] = false;
            $data['fullaction'] = "{$this->_request->getRouteName()}_{$this->_request->getControllerName()}" .
                "_{$this->_request->getActionName()}";
            $data['event_code'] = $eventCode;
            $data['action'] = 'login';
            /*set timezone for logger*/
            $this->_logger->setTimezone($configTz);
            $data['time'] =$datetime->setTimezone($configTz);
            $exception = $observer->getException();
            if ($exception instanceof UserLockedException) {
                $data['error_message'] = ' User locked' ;
            }
            else{
                if($data['user_id'] == null){
                    $data['error_message'] = 'User id doesn\'t exist' ;
                }else{
                    $data['error_message'] = 'Wrong password' ;
                }

            }
            $adminLog = $this->_collectionLogAdminFactory->create();
            $adminLog->setData($data);
            if($adminLog->save()){
                $result = $data;
                if (preg_match('/^[1-9][0-9]*$/', $result['ip'])) {
                    $result['ip'] = long2ip($result['ip']);
                }
                $dataJson= \Zend_Json::encode($result);
                $this->_logger->info( "Admin login failure\n"."data:".$dataJson ."\n");
            }
        }
        return $this;
    }
}
