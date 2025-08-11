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

use FilesystemIterator;
use GlobIterator;
use InvalidArgumentException;
use Phar;
use Pkg6\Console\Command;
use RuntimeException;
use Symfony\Component\Finder\Finder;
use UnexpectedValueException;

class PharBuilder
{
    /**
     * @var Package
     */
    public $package;
    /**
     * @var mixed|string
     */
    protected $main;
    /**
     * @var TargetPhar|string
     */
    protected $target;
    /**
     * @var string
     */
    protected $version;
    /**
     * @var mixed
     */
    protected $mount = [];

    public function __construct($path)
    {
        $this->package = new Package($this->loadJson($path), dirname(realpath($path)));
    }

    /**
     * Gets the Phar package name.
     */
    public function getTarget(): string
    {
        if ($this->target === null) {
            $target = $this->package->getShortName();
            if ($this->version !== null) {
                $target .= ':' . $this->version;
            }
            $this->target = $target . '.phar';
        }

        return (string) $this->target;
    }

    /**
     * Set the Phar package name.
     *
     * @param string|TargetPhar $target
     *
     * @return $this
     */
    public function setTarget($target)
    {
        if (is_dir($target)) {
            $this->target = null;
            $target = rtrim($target, '/') . '/' . $this->getTarget();
        }
        $this->target = $target;

        return $this;
    }

    /**
     * @return $this
     */
    public function setVersion(string $version)
    {
        $this->version = $version;

        return $this;
    }

    /**
     * Gets the default run script path.
     */
    public function getMain(): string
    {
        if ($this->main === null) {
            foreach ($this->package->getBins() as $path) {
                if ( ! file_exists($this->package->getDirectory() . $path)) {
                    throw new UnexpectedValueException('Bin file "' . $path . '" does not exist');
                }
                $this->main = $path;
                break;
            }
        }
        if ($this->main === null) {
            throw new UnexpectedValueException('Main file "' . $this->main . '" does not exist');
        }

        return $this->main;
    }

    /**
     * Set the default startup file.
     *
     * @return $this
     */
    public function setMain(string $main)
    {
        $this->main = $main;

        return $this;
    }
    /**
     * @return $this
     */
    public function setMount(array $mount = [])
    {
        foreach ($mount as $item) {
            $items = explode(':', $item);
            $this->mount[$items[0]] = $items[1] ?? $items[0];
        }

        return $this;
    }

    public function getMount(): array
    {
        return $this->mount;
    }

    public function build(Command $command)
    {
        $command->info('Creating phar <info>' . $this->getTarget() . '</info>');

        $time = microtime(true);
        $vendorPath = $this->package->getVendorAbsolutePath();
        if ( ! is_dir($vendorPath)) {
            throw new RuntimeException(sprintf('Directory %s not properly installed, did you run "composer install" ?', $vendorPath));
        }
        $target = $this->getTarget();
        do {
            $tmp = $target . '.' . mt_rand() . '.phar';
        } while (file_exists($tmp));

        $main = $this->getMain();

        if ( ! file_exists($main)) {
            $command->error(sprintf("Main entry file does not exist: %s", $main));

            return;
        }
        $command->info('Adding main package "' . $this->package->getName() . '"');
        $targetPhar = new TargetPhar(new Phar($tmp), $this);
        $projectFinder = Finder::create()
            ->files() // 只找文件
            ->ignoreVCS(true) // 忽略版本控制目录（.git, .svn等）
            ->exclude(rtrim($this->package->getVendorPath(), '/')) // 排除 vendor 目录
            ->exclude($main) // 排除 main 目录
            ->notPath($target);
        foreach ($this->getMount() as $inside) {
            $projectFinder = $projectFinder->exclude($inside);
        }
        $projectFinder = $projectFinder->in($this->package->getDirectory());
        $targetPhar->addBundle($this->package->bundle($projectFinder));
        // Add vendor/bin files.
        if (is_dir($vendorPath . 'bin/')) {
            $command->info('Adding vendor/bin files');
            $binIterator = new GlobIterator($vendorPath . 'bin/*');
            while ($binIterator->valid()) {
                $targetPhar->addFile($binIterator->getPathname());
                $binIterator->next();
            }
        }
        $command->info('Adding composer base files');
        // Add composer autoload file.
        $targetPhar->addFile($vendorPath . 'autoload.php');
        // Add composer autoload files.
        $targetPhar->buildFromIterator(new GlobIterator($vendorPath . 'composer/*.*', FilesystemIterator::KEY_AS_FILENAME));
        // Add composer depenedencies.
        foreach ($this->getPackagesDependencies() as $package) {
            $command->info('Adding dependency "' . $package->getName() . '" from "' . $this->getPathLocalToBase($package->getDirectory()) . '"');
            // support package symlink
            if (is_link(rtrim($package->getDirectory(), '/'))) {
                $bundle = $package->bundle();
                foreach ($bundle as $resource) {
                    foreach ($resource as $iterator) {
                        $targetPhar->addFile($iterator->getPathname());
                    }
                }
            } else {
                $targetPhar->addBundle($package->bundle());
            }
        }
        //Adding main file
        $command->info('Adding main file "' . $main . '"');
        $stubContents = file_get_contents($main);
        $targetPhar->addFromString($main, $stubContents);
        $targetPhar->setStub($targetPhar->createDefaultStub($main));

        $targetPhar->stopBuffering();
        if (file_exists($target)) {
            $command->info('Overwriting existing file <info>' . $target . '</info> (' . $this->getSize($target) . ')');
        }
        if (rename($tmp, $target) === false) {
            throw new UnexpectedValueException(sprintf('Unable to rename temporary phar archive to %s', $target));
        }
        $time = max(microtime(true) - $time, 0);
        $command->info('    <info>OK</info> - Creating <info>' . $this->getTarget() . '</info> (' . $this->getSize($this->getTarget()) . ') completed after ' . round($time, 1) . 's');
    }

    /**
     * Get package object.
     */
    public function getPackage(): Package
    {
        return $this->package;
    }

    /**
     * Load the configuration.
     */
    private function loadJson(string $path): array
    {
        $result = json_decode(file_get_contents($path), true);
        if ($result === null) {
            throw new InvalidArgumentException(sprintf('Unable to parse given path %s', $path), json_last_error());
        }

        return $result;
    }

    /**
     * Gets a list of all dependent packages.
     *
     * @return Package[]
     */
    public function getPackagesDependencies(): array
    {
        $packages = [];

        $vendorPath = $this->package->getVendorAbsolutePath();

        // Gets all installed dependency packages
        if (is_file($vendorPath . 'composer/installed.json')) {
            $installed = $this->loadJson($vendorPath . 'composer/installed.json');
            $installedPackages = $installed;
            // Adapte Composer 2.0
            if (isset($installed['packages'])) {
                $installedPackages = $installed['packages'];
            }
            // Package all of these dependent components into the packages
            foreach ($installedPackages as $package) {
                // support custom install path
                $dir = 'composer/' . ($package['install-path'] ?? '../' . $package['name']) . '/';

                if (isset($package['target-dir'])) {
                    $dir .= trim($package['target-dir'], '/') . '/';
                }

                $dir = $vendorPath . $dir;
                $packages[] = new Package($package, $this->canonicalize($dir));
            }
        }

        return $packages;
    }

    /**
     * Gets the canonicalize path, like realpath.
     *
     * @param mixed $address
     */
    public function canonicalize($address)
    {
        $address = explode('/', $address);
        $keys = array_keys($address, '..');
        foreach ($keys as $pos => $key) {
            array_splice($address, $key - ($pos * 2 + 1), 2);
        }
        $address = implode('/', $address);

        return str_replace('./', '', $address);
    }

    /**
     * Gets the relative path relative to the resource bundle.
     */
    public function getPathLocalToBase(string $path): ?string
    {
        $root = $this->package->getDirectory();
        if (strpos($path, $root) !== 0) {
            throw new UnexpectedValueException('Path "' . $path . '" is not within base project path "' . $root . '"');
        }
        $basePath = substr($path, strlen($root));

        return empty($basePath) ? null : $this->canonicalize($basePath);
    }

    /**
     * Get file size.
     *
     * @param PharBuilder|string $path
     */
    protected function getSize($path): string
    {
        return round(filesize((string) $path) / 1024, 1) . ' KiB';
    }

}
