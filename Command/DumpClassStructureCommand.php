<?php

namespace iLubenets\DIArchitectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use iLubenets\DIArchitectBundle\Classes\JsonDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 *
 * http://fabien.potencier.org/article/16/symfony-service-container-the-need-for-speed
 *
 * @package iLubenets\DIArchitectBundle\Command
 */
class DumpClassStructureCommand extends ContainerAwareCommand
{
    private $save_to = '';

    private $servicePathList = [];

    const JSON_FILE_TYPE = 'json';

    protected function configure()
    {
        $this
        ->setName('di_architect:dump_class_structure')
        ->addOption('bundle', null, InputOption::VALUE_OPTIONAL, 'Bundle name [all|BundleName1,BundleName2,..|system]', null)
        ->setDescription( 'Create the Dependency Injection schema in the *.json format' )
            ->setHelp(
                '<info>Example: </info> il:architect:dump_class_structure --bundle=FrameworkBundle'
            );
    }


    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->servicePathList = $this->getContainer()->getParameter('di_architect.service_path_list');
        $this->save_to = $this->getContainer()->getParameter('di_architect.path_to_save_json');

        $bundleName = $input->getOption('bundle');

        if ($bundleName == 'all') {
            $this->dumpAllCustomDI();
        } elseif ($bundleName == 'system') {
            $this->dumpSystemDI();
        } else {
            $this->dumpBundleDI($bundleName);
        }
    }

    /**
     * Get ContainerBuilder
     * @throws \Exception
     */
    private function dumpAllCustomDI()
    {
        $serviceContainer = new ContainerBuilder();
        foreach ($this->servicePathList as $servicePath) {
            $this->loadServicesByPath($serviceContainer, $servicePath);
        }

        $dumper = new JsonDumper($serviceContainer);
        $this->saveToDotFile('all', $dumper->dump());
    }


    /**
     * Get ContainerBuilder
     * @throws \Exception
     */
    private function dumpSystemDI()
    {
        $serviceContainer = $this->getFullSystemContainerBuilder();

        $dumper = new JsonDumper($serviceContainer);
        $this->saveToDotFile('system', $dumper->dump());
    }


    /**
     * Generate DI scheme for custom Bundle
     * @param null $expectedBundleName
     * @throws \Exception
     */
    private function dumpBundleDI($expectedBundleName = null)
    {
        foreach ($this->servicePathList as $bundleName => $servicePath) {
            if (!is_null($expectedBundleName) && $expectedBundleName != $bundleName) {
                continue;
            }
            $serviceContainer = new ContainerBuilder();
            $this->loadServicesByPath($serviceContainer, $servicePath);

            $dumper = new JsonDumper($serviceContainer);
            $this->saveToDotFile($bundleName, $dumper->dump());
        }
    }


    /**
     * @param $fileName
     * @param $type
     * @return string
     */
    private function getFilePath($fileName, $type) {
        return sprintf("%s/%s.%s", $this->save_to, $fileName, $type);
    }


    /**
     * @param $file_name
     * @param $content
     */
    private function saveToDotFile($file_name, $content)
    {
        if(!is_dir($this->save_to)) {
            mkdir($this->save_to, 0777, true);
        }
        file_put_contents($this->getFilePath($file_name, self::JSON_FILE_TYPE), $content);
    }


    /**
     * @param $serviceContainer
     * @param $servicesFilePath
     * @throws \Exception
     */
    private function loadServicesByPath($serviceContainer, $servicesFilePath) {
        $pathParts = explode('/', $servicesFilePath);
        $fileName = array_pop($pathParts);
        $fileNameParts = explode('.', $fileName);
        $fileType = array_pop($fileNameParts);
        $serviceDir = implode('/', $pathParts);
        $fileLocator = new FileLocator($serviceDir);

        if($fileType == 'yml') {
            $loader = new YamlFileLoader($serviceContainer, $fileLocator);
        } elseif ($fileType == 'xml') {
            $loader = new XmlFileLoader($serviceContainer, $fileLocator);
        } else {
            throw new \Exception("Unknown service config file type [$fileType]");
        }
        $loader->load($fileName);
    }


    /**
     * Loads the ContainerBuilder from the cache.
     *
     * @return ContainerBuilder
     *
     * @throws \LogicException
     */
    protected function getFullSystemContainerBuilder()
    {
        if (!$this->getApplication()->getKernel()->isDebug()) {
            throw new \LogicException(sprintf('Debug information about the container is only available in debug mode.'));
        }
        if (!is_file($cachedFile = $this->getContainer()->getParameter('debug.container.dump'))) {
            throw new \LogicException(sprintf('Debug information about the container could not be found. Please clear the cache and try again.'));
        }

        $container = new ContainerBuilder();
        $loader = new XmlFileLoader($container, new FileLocator());
        $loader->load($cachedFile);

        return $container;
    }
}
