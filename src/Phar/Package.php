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

use Symfony\Component\Finder\Finder;

class Package
{
    /**
     * @var array
     */
    protected $package;

    /**
     * @var string
     */
    protected $directory;

    public function __construct(array $package, string $directory)
    {
        $this->package = $package;
        $this->directory = rtrim($directory, '/') . '/';
    }

    /**
     * Get full package name.
     */
    public function getName()
    {
        return $this->package['name'] ?? null;
    }

    /**
     * Gets the short package name
     * If not, the pathname is used as the package name.
     */
    public function getShortName()
    {
        $name = $this->getName();
        if ($name === null) {
            $name = realpath($this->getDirectory());
            if ($name === false) {
                $name = $this->getDirectory();
            }
        }

        return basename($name);
    }

    /**
     * Gets the relative address of the vendor directory, which supports custom addresses in composer.json.
     */
    public function getVendorPath()
    {
        $vendor = 'vendor';
        if (isset($this->package['config']['vendor-dir'])) {
            $vendor = $this->package['config']['vendor-dir'];
        }

        return $vendor . '/';
    }

    /**
     * Gets the absolute address of the vendor directory.
     */
    public function getVendorAbsolutePath()
    {
        return $this->getDirectory() . $this->getVendorPath();
    }

    /**
     * Get package directory.
     */
    public function getDirectory()
    {
        return $this->directory;
    }

    /**
     * Get resource bundle object.
     */
    public function bundle(Finder $finder = null)
    {
        $bundle = new Bundle();
        $dir = $this->getDirectory();
        $vendorPath = $this->getVendorPath();
        if (empty($this->package['autoload']) && ! is_dir($dir . $vendorPath)) {
            return $bundle;
        }
        if ($finder == null) {
            $finder = Finder::create()->files()->ignoreVCS(true)->exclude(rtrim($vendorPath, '/'))->in($dir);
        }

        return $bundle->addFinder($finder);
    }

    /**
     * Gets the executable file path, and the directory address where the Phar package will run.
     */
    public function getBins()
    {
        return $this->package['bin'] ?? [];
    }
}
