<?php
namespace Riki\Framework\Helper\Logger;

class LoggerBuilder extends \Magento\Framework\App\Helper\AbstractHelper
{
    const ALIAS_DATE_HANDLER  = 'date';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $fileName;

    /**
     * @var array
     */
    protected $handlers;

    /**
     * @var MonologFactory
     */
    protected $monoLogFactory;

    /**
     * @var Handler\DateHandlerFactory
     */
    protected $dateHandlerFactory;

    /**
     * LoggerBuilder constructor.
     *
     * @param Handler\DateHandlerFactory $dateHandlerFactory
     * @param MonologFactory $monoLogFactory
     * @param \Magento\Framework\App\Helper\Context $context
     */
    public function __construct(
        \Riki\Framework\Helper\Logger\Handler\DateHandlerFactory $dateHandlerFactory,
        \Riki\Framework\Helper\Logger\MonologFactory $monoLogFactory,
        \Magento\Framework\App\Helper\Context $context
    ){
        $this->dateHandlerFactory = $dateHandlerFactory;
        $this->monoLogFactory = $monoLogFactory;
        parent::__construct($context);
    }

    /**
     * Push handler by alias
     *
     * @param $alias
     * @return $this
     *
     * @throws \Exception
     */
    public function pushHandlerByAlias($alias)
    {
        if (!$this->name) {
            throw new \Exception('Logger need a name to work correctly.');
        }
        switch ($alias) {
            case self::ALIAS_DATE_HANDLER:

                $handler = $this->dateHandlerFactory->create([
                    'identifier' => $this->name,
                    'fileName' => $this->fileName
                ]);
                $this->pushHandler($handler);
                break;
        }

        return $this;
    }

    /**
     * Set a name to logger/handler
     *
     * @param $name
     *
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Set file name to logger/handler
     *
     * @param $fileName
     *
     * @return $this
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
        return $this;
    }

    /**
     *  Push a handler manually
     *
     * @param \Monolog\Handler\HandlerInterface $handler
     *
     * @return $this
     */
    public function pushHandler(\Monolog\Handler\HandlerInterface $handler)
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * Set handlers
     *
     * @param $handlers
     *
     * @return $this
     */
    public function setHandlers($handlers)
    {
        $this->handlers = $handlers;
        return $this;
    }

    /**
     * Create a logger
     *
     * @return \Riki\Framework\Helper\Logger\Monolog
     */
    public function create()
    {
        $logger = $this->monoLogFactory->create([
            'name' => $this->name,
            'handlers' => $this->handlers
        ]);

        return $logger;
    }
}