<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Riki\AdvancedInventory\Cron\OutOfStock;

class OosConsumerTrigger
{

    const CONSUMER_NAME = 'startGenerateOrderOos';
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
     * @var \Riki\Framework\Helper\Logger\Monolog $log
     */
    protected $log;

    /**
     * Generate order for out of stock product constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Shell $shell
     * @param \Riki\AdvancedInventory\Helper\Logger $log
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Shell $shell,
        \Riki\AdvancedInventory\Helper\Logger $log
    ){
        $this->log = $log->getOosTriggerLogger();
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * Init Setting
     */
    public function initSetting()
    {

        $this->pathPhp  = $this->getPathPhp();

        $this->pathBinMagento = BP.'/bin/magento';

    }
    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        $this->initSetting();

        $maxMessageConsumer  = $this->scopeConfig->getValue('advancedinventory_outofstock/generate_order/max_message_queue');

        $aCommands[self::CONSUMER_NAME] = $this->pathBinMagento.' queue:consumers:start --max-messages='.$maxMessageConsumer.' '. self::CONSUMER_NAME;

        $this->executeConsumer($aCommands,$maxMessageConsumer);
    }

    /**
     * @param $aListCommand
     * @param $maxMessageConsumer
     */
    public function executeConsumer($aListCommand,$maxMessageConsumer)
    {

        foreach($aListCommand as $keyCommand => $command){
            try {
                $aResultCommand  = $this->shell->execute("ps aux | grep -i " . $keyCommand);

                $this->log->info('Run: ' . $aResultCommand);

                if (strpos($aResultCommand, $command) === false) {
                    $this->shell->execute($this->pathPhp.' '.$this->pathBinMagento.' queue:consumers:start --max-messages='.$maxMessageConsumer.' '.$keyCommand.' >> /dev/null &');
                }
            }
            catch(\Exception $e){
                $this->log->critical($e->getMessage());
                $this->log->critical($e->getTraceAsString());
            }
        }
    }

    /**
     * GetPathPhp
     *
     *
     * @return string
     */
    public function getPathPhp()
    {

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
            $this->log->critical($e->getMessage());
        }

    }
}