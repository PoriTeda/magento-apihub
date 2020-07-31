<?php
/**
 * Log plugin. Logs user actions
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Logging\App\Action\Plugin;

use Magento\Logging\Model\Processor;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\RequestInterface;

class Log
{
    /**
     * @var Processor
     */
    protected $_processor;

    /**
     * @param Processor $processor
     */
    public function __construct(Processor $processor)
    {
        $this->_processor = $processor;
    }

    /**
     * Mark actions for logging, if required
     *
     * @param ActionInterface $subject
     * @param RequestInterface $request
     *
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeDispatch(ActionInterface $subject, RequestInterface $request)
    {
        $beforeForwardInfo = $request->getBeforeForwardInfo();

        // Always use current action name bc basing on
        // it we make decision about access granted or denied
        $actionName = $request->getActionName();

        if (empty($beforeForwardInfo)) {
            $fullActionName = $request->getFullActionName();
        } else {
            $fullActionName = [$request->getRouteName()];

            if (isset($beforeForwardInfo['controller_name'])) {
                $fullActionName[] = $beforeForwardInfo['controller_name'];
            } else {
                $fullActionName[] = $request->getControllerName();
            }

            if (isset($beforeForwardInfo['action_name'])) {
                $fullActionName[] = $beforeForwardInfo['action_name'];
            } else {
                $fullActionName[] = $actionName;
            }

            $fullActionName = \implode('_', $fullActionName);
        }

        $this->_processor->initAction($fullActionName, $actionName);
    }
}
