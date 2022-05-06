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

        $serviceDefinition  = $container->getDefinition('TurboLabIt\ChromeHeadless\ChromeHeadless');

        $arrDefaultConfig   = $serviceDefinition->getArgument('$arrConfig');
        $arrCustomConfig    = $configs[0]['$arrConfig'];
        $arrFinalConfig     = array_replace_recursive($arrDefaultConfig, $arrCustomConfig);

        //$serviceDefinition->replaceArgument('$arrConfig', $arrFinalConfig);
    }
}
