<?php
namespace Riki\AdvancedInventory\Controller\Adminhtml;

abstract class Outofstock extends AbstractAction
{
    /**
     * @var \Riki\Framework\Helper\Search
     */
    protected $searchHelper;

    /**
     * @var \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface
     */
    protected $outOfStockRepository;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @var \Magento\Framework\Url\DecoderInterface
     */
    protected $urlDecoder;

    /**
     * Outofstock constructor.
     *
     * @param \Magento\Framework\Url\DecoderInterface $urlDecoder
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository
     * @param \Riki\Framework\Helper\Search $searchHelper
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Backend\App\Action\Context $context
     */
    public function __construct(
        \Magento\Framework\Url\DecoderInterface $urlDecoder,
        \Psr\Log\LoggerInterface $logger,
        \Riki\AdvancedInventory\Api\OutOfStockRepositoryInterface $outOfStockRepository,
        \Riki\Framework\Helper\Search $searchHelper,
        \Magento\Framework\Registry $registry,
        \Magento\Backend\App\Action\Context $context
    ) {
        $this->urlDecoder = $urlDecoder;
        $this->searchHelper = $searchHelper;
        $this->logger = $logger;
        $this->outOfStockRepository = $outOfStockRepository;
        parent::__construct($registry, $context);
    }
}