<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\ThirdPartyImportExport\Cron\ExportToBi;

class ManageCronBiSubscription
{

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
     * ManageCronBiSubscription constructor.
     * @param \Riki\ThirdPartyImportExport\Logger\ExportToBi\SubscriptionProfile\LoggerCSV $logger
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
        $this->initSetting();

        $maxMessageConsumer  = $this->getMaxMessageConsumer();

        $aCommands = $this->prepareListConsumer();

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
     * @return array
     */
    public function prepareListConsumer(){

        $sPathBinMagento = BP.'/bin/magento';

        $maxMessageConsumer = $this->getMaxMessageConsumer();

        $maxConsumerSubProfile = $this->getMaxConsumerSubProfile();

        $maxConsumerSimulateSubProfile = $this->getMaxConsumerSimulateSubProfile();

        /*--for subscription profile--*/
        $rangeCreateConsumer=range("a","z");

        $aBasicCommand = [
            '%sNextOrderSubscriptionProfileHeader' => $maxConsumerSubProfile,
            '%sNextOrderSubscriptionProfileSimulate' => $maxConsumerSimulateSubProfile
        ];

        $aCommands = [];
        foreach($aBasicCommand as $basicCommand => $maxConsumer){
            $count = 0;
            foreach($rangeCreateConsumer as $character){

                $nameConsumer = sprintf($basicCommand,$character);

                $aCommands[$nameConsumer] = $sPathBinMagento.' queue:consumers:start --max-messages='.$maxMessageConsumer.' '.$nameConsumer;
                $count++;

                if($count >= $maxConsumer){
                    break;
                }
            }
        }

        return $aCommands;
    }


    /**
     * @return mixed
     */
    public function getMaxMessageConsumer(){

        return $this->scopeConfig->getValue('di_data_export_setup/data_cron_subscription_next_delivery/number_message_per_consumer',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getMaxConsumerSubProfile(){
        return $this->scopeConfig->getValue('di_data_export_setup/data_cron_subscription_next_delivery/number_consumer_exported_sub_profile',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getMaxConsumerSimulateSubProfile(){
        return $this->scopeConfig->getValue('di_data_export_setup/data_cron_subscription_next_delivery/number_consumer_exported_simulate_sub_profile',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
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
}
