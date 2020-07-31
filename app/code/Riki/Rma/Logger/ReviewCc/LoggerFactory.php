<?php

namespace Riki\Rma\Logger\ReviewCc;

class LoggerFactory
{
    protected $name = 'Rma_ReviewCc';

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(\Magento\Framework\ObjectManagerInterface $objectManager)
    {
        $this->_objectManager = $objectManager;
    }

    /**
     * @param \Riki\Rma\Model\ReviewCc $reviewCc
     * @param array $handlers
     * @param array $processors
     * @return mixed
     */
    public function create(\Riki\Rma\Model\ReviewCc $reviewCc, array $handlers = array(), array $processors = array())
    {

        /** @var \Riki\Rma\Logger\ReviewCc\Handler\Info $infoHandle */
        $infoHandle = $this->_objectManager->create(\Riki\Rma\Logger\ReviewCc\Handler\Info::class, ['filePath' =>  $reviewCc->getLogFile()->getFilePath()]);

        /** @var \Riki\Rma\Logger\ReviewCc\Handler\Error $criticalHandle */
        $criticalHandle = $this->_objectManager->create(\Riki\Rma\Logger\ReviewCc\Handler\Error::class, ['filePath' =>  $reviewCc->getLogFile()->getErrorFilePath()]);

        $formatter = $this->_objectManager->create(\Riki\Rma\Logger\ReviewCc\Formatter::class, ['allowInlineLineBreaks'    =>  true, 'format'    =>  "[%datetime%] %channel%." . $reviewCc->getId() . ".%level_name%: %message% %context% %extra%\n"]);

        $handlers['error'] = $criticalHandle->setFormatter($formatter);
        $handlers['info'] = $infoHandle->setFormatter($formatter);

        return $this->_objectManager->create(\Riki\Rma\Logger\ReviewCc\Logger::class, [
            'name' => $this->name,
            'handlers' => $handlers,
            'processors' => $processors
        ]);
    }
}