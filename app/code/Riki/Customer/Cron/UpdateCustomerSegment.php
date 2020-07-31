<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\Customer\Cron;

class UpdateCustomerSegment
{
    const MAX_MESSAGE_QUEUE_CONFIG = 'sso_login_setting/update_segment_queue_customer/max_message_queue';
    const ENABLE_UPDATE_SEGMENT_BY_QUEUE_CONFIG = 'sso_login_setting/update_segment_queue_customer/use_queue_to_update_segment';

    /**
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var string
     */
    protected $pathPhp;

    /**
     * @var string
     */
    protected $pathBinMagento;
    /**
     * @var LoggerCSV
     */
    protected $_log;

    /**
     * ReindexCustomer constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Shell $shell
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Shell $shell,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_log = $logger;
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * Init Setting
     */
    public function initSetting(){

        $this->pathPhp  = $this->getPathPhp();

        $this->pathBinMagento = BP.'/bin/magento';

    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {
        if (!$this->enableUpdateSegmentByQueue()) {
            return;
        }

        $this->initSetting();

        $maxMessageConsumer  = $this->scopeConfig->getValue(self::MAX_MESSAGE_QUEUE_CONFIG);

        $aCommands['startUpdateModelSegment'] = $this->pathBinMagento.' queue:consumers:start --max-messages='.$maxMessageConsumer.' startUpdateModelSegment';

        $this->executeConsumer($aCommands,$maxMessageConsumer);
    }

    /**
     * @param $aListCommand
     * @param $maxMessageConsumer
     */
    public function executeConsumer($aListCommand,$maxMessageConsumer){

        foreach($aListCommand as $keyCommand => $command){
            try {
                $aResultCommand  = $this->shell->execute("ps aux | grep -i " . $keyCommand);

                if (strpos($aResultCommand, $command) === false) {
                    $this->shell->execute($this->pathPhp.' '.$this->pathBinMagento.' queue:consumers:start --max-messages='.$maxMessageConsumer.' '.$keyCommand.' >> /dev/null &');
                }
            }
            catch(\Exception $e){
                $this->_log->critical($e->getMessage());
            }
        }
    }

    /**
     * GetPathPhp
     *
     *
     * @return string
     */
    public function getPathPhp(){

        $sPhpPath = '';

        $aCommands = [
            'whereis php',
            'which php'
        ];
        try{
            foreach($aCommands as $sCommand){
                $sPhpPath = $this->shell->execute($sCommand);

                $aPhpPath = explode(" ",$sPhpPath);
                foreach($aPhpPath as $sPhpPathLine){
                    if(strpos($sPhpPathLine,"/bin/php") !== false){
                        $sPhpPath = $sPhpPathLine;
                        break;
                    }
                }
                return $sPhpPath;
            }
        }catch (\Exception $e){
            $this->_log->critical($e->getMessage());
        }

    }

    /**
     * Update segment by queue is enable or not
     *
     * @return mixed
     */
    public function enableUpdateSegmentByQueue()
    {
        return $this->scopeConfig->getValue(self::ENABLE_UPDATE_SEGMENT_BY_QUEUE_CONFIG);
    }
}