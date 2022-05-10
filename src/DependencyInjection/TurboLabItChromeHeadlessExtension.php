<?php
namespace TurboLabIt\ChromeHeadless\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;


class TurboLabItChromeHeadlessExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new YamlFileLoader($container, new FileLocator(dirname(__DIR__).'/Resources/config'));
        $loader->load('services.yaml');

        $serviceDefinition = $container->getDefinition('TurboLabIt\ChromeHeadless\ChromeHeadless');
        $arrFinalConfig = $serviceDefinition->getArgument('$arrConfig');

        foreach($configs as $oneConfig) {
            $arrFinalConfig = array_replace_recursive($arrFinalConfig, $oneConfig['$arrConfig']);
        }

        $serviceDefinition->replaceArgument('$arrConfig', $arrFinalConfig);
    }
}
