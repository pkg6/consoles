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

namespace Pkg6\Consoles\Phar;

use InvalidArgumentException;
use Pkg6\Console\Command;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;

class PharBuildCommand extends Command
{
    protected $name = 'phar:build';
    protected $description = 'Pack your project into a Phar package.';

    public function __construct()
    {
        parent::__construct();
        $this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, 'Project root path.', ".")
            ->addOption('bin', 'b', InputOption::VALUE_OPTIONAL, 'The script path to execute by default.', 'bin/consoles')
            ->addOption('name', '', InputOption::VALUE_OPTIONAL, 'This is the name of the Phar package, and if it is not passed in, the project name is used by default')
            ->addOption('phar-version', '', InputOption::VALUE_OPTIONAL, 'The version of the project that will be compiled.')
            ->addOption('mount', 'M', InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'The mount path or dir.');
    }

    protected function handle()
    {
        if (ini_get('phar.readonly') === '1') {
            $this->error("'Your configuration disabled writing phar files (phar.readonly = On), please update your configuration'");

            return self::FAILURE;
        }
        $path = $this->input->getOption('path');
        $bin = $this->input->getOption('bin');
        $name = $this->input->getOption('name');
        $version = $this->input->getOption('phar-version');
        $mount = $this->input->getOption('mount');

        try {
            $builder = $this->getPharBuilder($path);
            if ( ! empty($bin)) {
                $builder->setMain($bin);
            }
            if ( ! empty($name)) {
                $builder->setTarget($name);
            }
            if ( ! empty($version)) {
                $builder->setVersion($version);
            }
            if (count($mount) > 0) {
                $builder->setMount($mount);
            }
            $builder->build($this);
            $this->info("phar successfully");
        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }

        return self::SUCCESS;
    }

    public function getPharBuilder(string $path): PharBuilder
    {
        if (is_dir($path)) {
            $path = rtrim($path, '/') . '/composer.json';
        }
        if ( ! is_file($path)) {
            throw new InvalidArgumentException(sprintf('The given path %s is not a readable file', $path));
        }
        $pharBuilder = new PharBuilder($path);
        $vendorPath = $pharBuilder->getPackage()->getVendorAbsolutePath();
        if ( ! is_dir($vendorPath)) {
            throw new RuntimeException('The project has not been initialized, please manually execute the command `composer install` to install the dependencies');
        }

        return $pharBuilder;
    }
}
