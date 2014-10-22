<?php

namespace iLubenets\DIArchitectBundle\Classes;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\Dumper;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

/**
 * Dump a list of dependencies in a json
 * @package iLubenets\DIArchitectBundle\Classes
 */
class JsonDumper extends Dumper
{
    /**
     * Group list
     * @var IndexBag
     */
    private $groupBag;
    /**
     * Node list
     * @var IndexBag
     */
    private $nodesBag;
    /**
     * Links list
     * @var IndexBag
     */
    private $linksBag;
    /**
     * Index of th group
     * @var integer
     */
    private $metaGroupIndex;
    /**
     * External reference
     * @var bool
     */
    private $includeExternalReferences = false;


    function __construct(ContainerBuilder $container)
    {
        parent::__construct($container);
        $this->groupBag = new IndexBag();
        $this->nodesBag = new IndexBag();
        $this->linksBag = new IndexBag();
        $this->metaGroupIndex = $this->groupBag->addItem('meta');
    }


    /**
     * Get definition
     *
     * @param $id
     * @return bool|Definition
     */
    private function getDefinition($id)
    {
        if (!$this->container->hasDefinition($id)) {
            return false;
        }

        $definition = $this->container->getDefinition($id);

        if ($definition instanceof Alias) {
            return false;
        }

        return $definition;
    }


    /**
     * Generate json array
     *
     * @param array $options
     * @return string
     */
    public function dump(array $options = array())
    {

        // Nodes for all services
        foreach ($this->container->getServiceIds() as $id) {
            $definition = $this->getDefinition($id);
            if ($definition === false) {
                continue;
            }

            $classInfo = $this->getDefinitionClassInfo($definition);
            $this->nodesBag->addItem(
                $id,
                [
                    'name' => $classInfo['class_name'],
                    'group' => $this->groupBag->addItem($classInfo['group_name'])
                ]
            );
        }

        // Reference between services
        foreach ($this->container->getServiceIds() as $id) {
            $definition = $this->getDefinition($id);
            if ($definition === false) {
                continue;
            }

            // Factory services
            $factoryServiceId = $definition->getFactoryService();
            if (isset($factoryServiceId)) {
                $this->linksBag->addItem(
                    $factoryServiceId . $id . 'f',
                    [
                        'source' => $this->nodesBag->getItemIndex($factoryServiceId),
                        'target' => $this->nodesBag->getItemIndex($id),
                        'value' => 1,
                        'kind' => 'factory'
                    ]
                );
            }

            // Setter injection
            foreach ($definition->getMethodCalls() as $methodId => $method) {
                $methodReferences = $method[1];
                foreach ($methodReferences as $methodReference) {
                    $this->processReference($id, $methodReference, 'setter');
                }
            }

            // Constructor injection
            foreach ($definition->getArguments() as $argumentId => $argument) {
                $this->processReference($id, $argument, 'constructor');
            }

        }

        return json_encode(
            [
                'nodes' => $this->nodesBag->toArray(),
                'links' => $this->linksBag->toArray()
            ]
        );
    }


    /**
     * Возвращает информацию о классе определения
     *
     * @param $definition
     * @return array
     */
    private function getDefinitionClassInfo($definition)
    {
        $class = $definition->getClass();
        $classPath = $this->container->getParameterBag()->resolveValue($class);
        $classPathParts = explode('\\', $classPath);
        $className = $classPathParts[count($classPathParts) - 1];
        if ($classPathParts[0] == 'Prosto') {
            $groupName = $classPathParts[0] . $classPathParts[1];
        } else {
            $groupName = $classPathParts[0];
        }

        return [
            'class_name' => $className,
            'group_name' => $groupName
        ];
    }


    /**
     * Обрабатывает зависимости
     *
     * @param $id
     * @param $reference
     * @param $kind
     */
    private function processReference($id, $reference, $kind)
    {
        if (!($reference instanceof Reference)) {
            return;
        }

        $referenceId = (string)$reference;
        try {
            $referenceDefinition = $this->container->getDefinition($referenceId);
            if ($referenceDefinition instanceof Definition) {

                $this->linksBag->addItem(
                    $id . $referenceId,
                    [
                        'source' => $this->nodesBag->getItemIndex($id),
                        'target' => $this->nodesBag->getItemIndex($referenceId),
                        'value' => 1,
                        'kind' => $kind
                    ]
                );

            }
        } catch (InvalidArgumentException $e) {
            if (!$this->includeExternalReferences) {
                return;
            }

            $argumentName = (string)$reference;
            $this->nodesBag->addItem(
                $argumentName,
                [
                    'name' => $argumentName,
                    'group' => $this->metaGroupIndex
                ]
            );

            $this->linksBag->addItem(
                $id . $referenceId . 'a',
                [
                    'source' => $this->nodesBag->getItemIndex($id),
                    'target' => $this->nodesBag->getItemIndex($argumentName),
                    'value' => 1
                ]
            );
        }
    }
}