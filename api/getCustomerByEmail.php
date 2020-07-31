<?php
use Magento\Framework\App\Bootstrap;

return function (array $event) {
    $params = $_SERVER;
    $bootstrap = Bootstrap::create(BP, $params);
    $obj = $bootstrap->getObjectManager();
    $state = $obj->get(\Magento\Framework\App\State::class);
    $state->setAreaCode('frontend');
    /*Graphql generator*/
    $queryProcessor = $obj->get(\Magento\Framework\GraphQl\Query\QueryProcessor::class);
    $schemaInterface = $obj->get(\Magento\Framework\GraphQl\Schema\SchemaGeneratorInterface::class);
    $resolverContext = $obj->get(\Magento\Framework\GraphQl\Query\Resolver\ContextInterface::class);
    $schema = $schemaInterface->generate();
    //$query = 'query{testcustomer(email:"riki02345@mailinator.com"){entity_id firstname lastname email}}';
    $result = $queryProcessor->process(
        $schema,
        $event['query'],
        $resolverContext,
        []
    );
    return json_encode($result);
};
