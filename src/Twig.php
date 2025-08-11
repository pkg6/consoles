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

use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class Twig
{
    /**
     * 缓存不同模板目录对应的 Views 环境.
     *
     * @var Environment[]
     */
    protected static $instances = [];

    /**
     * 获取指定目录的 Views 环境实例（单例）.
     */
    protected static function getEnvironment(string $templateDir)
    {
        if ( ! isset(self::$instances[$templateDir])) {
            $loader = new FilesystemLoader($templateDir);
            self::$instances[$templateDir] = new Environment($loader);
        }

        return self::$instances[$templateDir];
    }

    /**
     * 渲染模板，$filename 为完整路径.
     */
    public static function fetch(string $name, array $context = [])
    {
        $templateFile = basename($name);
        $twig = self::getEnvironment(__DIR__ . DIRECTORY_SEPARATOR . 'Views');
        try {
            return $twig->render($templateFile, $context);
        } catch (\Exception $exception) {
            return "";
        }
    }

    /**
     * 渲染指定模板并写入到目标文件中。
     *
     * - 自动创建目标目录（如果不存在）。
     * - 渲染模板内容并写入目标路径。
     *
     * @param string $name 模板名称或模板文件标识（传给 fetch 方法处理）
     * @param string $dst 生成目标文件完整路径（如 /path/to/output.php）
     * @param array $context 传递给模板的上下文数据（变量数组）
     *
     * @return bool 成功写入返回 true
     *
     * @throws \RuntimeException 当目录创建失败、模板渲染失败或文件写入失败时抛出异常
     */
    public static function fetchWrite(string $name, string $dst, array $context = [])
    {
        // 目标目录路径
        $dir = dirname($dst);
        // 自动创建目标目录
        if ( ! is_dir($dir)) {
            if ( ! mkdir($dir, 0777, true) && ! is_dir($dir)) {
                throw new \RuntimeException("Unable to create directory: {$dir}");
            }
        }
        // 渲染模板内容
        $codeString = self::fetch($name, $context);
        if (trim($codeString) === '') {
            throw new \RuntimeException("Template rendering result is empty for template: {$name}");
        }
        // 写入文件内容
        if (file_put_contents($dst, $codeString) === false) {
            throw new \RuntimeException("Failed to write rendered template to file: {$dst}");
        }

        return true;
    }
}
