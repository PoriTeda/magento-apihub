<?php
/**
 * @author Amasty Team
 * @copyright Copyright (c) 2019 Amasty (https://www.amasty.com)
 * @package Amasty_Smtp
 */


namespace Amasty\Smtp\Model\Provider\Config;

use Magento\Framework\Config\ConverterInterface;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\SchemaLocatorInterface;
use Magento\Framework\Config\ValidationStateInterface;

class Reader extends Filesystem
{
    /**
     * Constructor
     *
     * @param FileResolverInterface                $fileResolver
     * @param Converter|ConverterInterface         $converter
     * @param SchemaLocator|SchemaLocatorInterface $schemaLocator
     * @param ValidationStateInterface             $validationState
     * @param string                               $fileName
     * @param array                                $idAttributes
     * @param string                               $domDocumentClass
     * @param string                               $defaultScope
     */
    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        $fileName = 'providers.xml',
        $idAttributes = [],
        $domDocumentClass = 'Magento\Framework\Config\Dom',
        $defaultScope = 'global'
    ) {
        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
