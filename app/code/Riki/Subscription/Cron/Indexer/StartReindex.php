<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Riki\Subscription\Cron\Indexer;

class StartReindex
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
    protected $log;

    /**
     * GenerateOrder constructor.
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Framework\Shell $shell
     * @param \Psr\Log\LoggerInterface $logger
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\Shell $shell,
        \Psr\Log\LoggerInterface $logger
    ) {
        $this->log = $logger;
        $this->shell = $shell;
        $this->scopeConfig = $scopeConfig;

    }

    /**
     * Init Setting
     */
    public function initSetting()
    {

        $this->pathPhp = $this->getPathPhp();

        $this->pathBinMagento = BP . '/bin/magento';

    }

    /**
     * {@inheritdoc}
     */
    public function execute()
    {

        $this->initSetting();

        /*for create order subscription*/
        $maxMessageConsumerCreateOrder = $this->getMaxMessageReindex();

        $aCommandCreateOrder = $this->prepareListReindexSubscription();

        $this->executeConsumer($aCommandCreateOrder, $maxMessageConsumerCreateOrder);
    }

    /**
     * @param $aListCommand
     * @param $maxMessageConsumer
     */
    public function executeConsumer($aListCommand, $maxMessageConsumer)
    {

        foreach ($aListCommand as $keyCommand => $command) {
            try {
                $aResultCommand = $this->shell->execute("ps aux | grep -i " . $keyCommand);

                if (strpos($aResultCommand, $command) === false) {
                    $this->shell->execute($this->pathPhp . ' ' . $this->pathBinMagento . ' queue:consumers:start --max-messages=' . $maxMessageConsumer . ' ' . $keyCommand . ' >> /dev/null &');
                }
            } catch (\Exception $e) {
                $this->log->critical($e->getMessage());
            }
        }
    }

    /**
     * @return array
     */
    public function prepareListReindexSubscription()
    {

        $sPathBinMagento = BP . '/bin/magento';

        $maxMessageConsumer = $this->getMaxMessageReindex();

        $maxConsumer = $this->getMaxConsumerReindex();

        /*--for subscription profile--*/
        $rangeCreateConsumer = range("a", "z");

        $aBasicCommand = [
//            '%sReindexProfileSubscription'
        ];

        $aCommands = [];
//        foreach ($aBasicCommand as $basicCommand) {
//            $count = 0;
//            foreach ($rangeCreateConsumer as $character) {
//
//                $nameConsumer = sprintf($basicCommand, $character);
//
//                $aCommands[$nameConsumer] = $sPathBinMagento . ' queue:consumers:start --max-messages=' . $maxMessageConsumer . ' ' . $nameConsumer;
//                $count++;
//
//                if ($count >= $maxConsumer) {
//                    break;
//                }
//            }
//        }

        return $aCommands;
    }

    /**
     * @return mixed
     */
    public function getMaxMessageReindex()
    {

        return $this->scopeConfig->getValue('subscriptioncourse/indexer/number_message_per_consumer',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * @return mixed
     */
    public function getMaxConsumerReindex()
    {
        return $this->scopeConfig->getValue('subscriptioncourse/indexer/number_consumer_indexer',
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * GetPathPhp
     *
     *
     * @return string
     */
    public function getPathPhp()
    {

        $aCommands = [
            'whereis php',
            'which php'
        ];
        try {
            foreach ($aCommands as $sCommand) {
                $sPhpPath = $this->shell->execute($sCommand);

                $aPhpPath = explode(" ", $sPhpPath);
                foreach ($aPhpPath as $sPhpPathLine) {
                    if (strpos($sPhpPathLine, "/bin/php") !== false) {
                        $sPhpPath = $sPhpPathLine;
                        break;
                    }
                }

                return $sPhpPath;
            }
        } catch (\Exception $e) {
            $this->log->critical($e->getMessage());
        }

    }
}