<?php

namespace iLubenets\DIArchitectBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Dumper\GraphvizDumper;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;

/**
 * Class GraphvizGenerateCommand
 * Idea from the http://fabien.potencier.org/article/16/symfony-service-container-the-need-for-speed
 * You need to create an image http://www.graphviz.org/
 * @package iLubenets\DIArchitectBundle\Command
 */
class GraphvizGenerateCommand extends ContainerAwareCommand
{
    const GRAPHVIZ_CREATE_IMG_CMD = 'dot -Tpng -o %s %s';

    const GRAPHVIZ_FILE_TYPE = 'dot';

    const IMG_FILE_TYPE = 'png';

    /**
     * @var string
     */
    private $save_to = '';

    /**
     * @var array
     */
    private $servicePathList = [];

    protected function configure()
    {
        $this
            ->setName('di_architect:graphviz_generate')
            ->addOption('bundle', null, InputOption::VALUE_OPTIONAL, 'Bundle name [all|BundleName1,BundleName2,..|system]', null)
            ->addOption('img', null, InputOption::VALUE_NONE, 'Is need to create an image?')
            ->setDescription('Create the Dependency Injection schema in the *.dot format')
            ->setHelp(
                '<info>Example: </info> il:architect:graphviz_generate --bundle=FrameworkBundle --img'
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
        $this->save_to = $this->getContainer()->getParameter('di_architect.path_to_save_graphviz');

        $bundleName = $input->getOption('bundle');
        $convertIntoImg = $input->getOption('img');

        if ($bundleName == 'all') {
            $this->dumpAllCustomDI($convertIntoImg);
        } elseif ($bundleName == 'system') {
            $this->dumpSystemDI($convertIntoImg);
        } else {
            $this->dumpBundleDI($convertIntoImg, $bundleName);
        }
    }


    /**
     * Get ContainerBuilder
     * @param bool $convertIntoImg
     * @throws \Exception
     */
    private function dumpAllCustomDI($convertIntoImg = false)
    {
        $serviceContainer = new ContainerBuilder();
        foreach ($this->servicePathList as $servicePath) {
            $this->loadServicesByPath($serviceContainer, $servicePath);
        }

        $dumper = new GraphvizDumper($serviceContainer);
        $this->saveToDotFile('all', $dumper->dump());
        if ($convertIntoImg) {
            $this->convertGraphvizIntoImage('all');
        }
    }


    /**
     * Get ContainerBuilder
     * @param bool $convertIntoImg
     * @throws \Exception
     */
    private function dumpSystemDI($convertIntoImg = false)
    {
        $serviceContainer = $this->getFullSystemContainerBuilder();

        $dumper = new GraphvizDumper($serviceContainer);
        $this->saveToDotFile('system', $dumper->dump());
        if ($convertIntoImg) {
            $this->convertGraphvizIntoImage('system');
        }
    }


    /**
     * Generate DI scheme for custom Bundle
     * @param bool $convertIntoImg
     * @param null $expectedBundleName
     * @throws \Exception
     */
    private function dumpBundleDI($convertIntoImg = false, $expectedBundleName = null)
    {
        foreach ($this->servicePathList as $bundleName => $servicePath) {
            if (!is_null($expectedBundleName) && $expectedBundleName != $bundleName) {
                continue;
            }
            $serviceContainer = new ContainerBuilder();
            $this->loadServicesByPath($serviceContainer, $servicePath);

            $dumper = new GraphvizDumper($serviceContainer);
            $this->saveToDotFile($bundleName, $dumper->dump());
            if ($convertIntoImg) {
                $this->convertGraphvizIntoImage($bundleName);
            }
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
        file_put_contents($this->getFilePath($file_name, self::GRAPHVIZ_FILE_TYPE), $content);
    }


    /**
     * @param $fileName
     */
    private function convertGraphvizIntoImage($fileName) {
        $dumpFileName = $this->getFilePath($fileName, self::GRAPHVIZ_FILE_TYPE);
        $imgFileName = $this->getFilePath($fileName, self::IMG_FILE_TYPE);
        shell_exec(sprintf(self::GRAPHVIZ_CREATE_IMG_CMD, $imgFileName, $dumpFileName));
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
