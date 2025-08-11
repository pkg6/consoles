<?php

/*
 * This file is part of the pkg6/consoles
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Pkg6\Consoles;

use Composer\InstalledVersions;
use Pkg6\Console\Command;
use RuntimeException;

class InitCommand extends Command
{
    const consoleCliPkg = "pkg6/consoles";
    const initNamespace = "App";
    const initNamespaceAs = "app";
    const initBin = "consoles";
    const initClassName = "ConsoleCliApp";
    /**
     * @var string
     */
    protected $name = 'init';

    /**
     * @var string
     */
    protected $description = 'project init';

    /**
     * @return int
     */
    public function handle()
    {
        try {
            $composerFile = getcwd() . DIRECTORY_SEPARATOR . 'composer.json';
            if ( ! file_exists($composerFile)) {
                throw new RuntimeException("Can't find composer.json. Please run this command in the project root directory.");
            }
            if ( ! InstalledVersions::isInstalled(self::consoleCliPkg)) {
                throw new RuntimeException("The dependency is not declared. Please run the following command to add the dependency and try again：composer require pkg6/consoles");
            }
            $composerData = json_decode(file_get_contents($composerFile), true);
            if ( ! empty($composerData['name']) && $composerData['name'] == self::consoleCliPkg) {
                throw new RuntimeException("Initialization is not possible in source code");
            }
            $this->composer($composerFile, $composerData);
            $this->app();
            @exec('composer dump-autoload');
            $this->info('init success');
        } catch (\Exception $e) {
            $this->error($e->getMessage());
        }

        return self::SUCCESS;
    }

    protected function composer($composerFile, $composerData)
    {
        $w = false;
        if ( ! isset($composerData['autoload']['psr-4'][self::initNamespace . '\\'])) {
            $composerData['autoload']['psr-4'][self::initNamespace . '\\'] = self::initNamespaceAs . "/";
            $w = true;
        }
        if (isset($composerData['autoload-dev']['psr-4'][self::initNamespace . '\\'])) {
            $w = false;
        }
        if ($w) {
            file_put_contents($composerFile, json_encode($composerData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }
    }

    protected function app()
    {
        // 要复制的文件映射
        $files = [
            'init_app.twig' => getcwd() . DIRECTORY_SEPARATOR . self::initNamespaceAs . DIRECTORY_SEPARATOR . self::initClassName . '.php',
            'int_bin.twig' => getcwd() . DIRECTORY_SEPARATOR . self::initBin,
        ];
        $context = [
            'bin' => self::initBin,
            'namespace' => self::initNamespace,
            'app_class_name' => self::initClassName,
        ];
        foreach ($files as $src => $dst) {
            Twig::fetchWrite($src, $dst, $context);
        }
    }
}
