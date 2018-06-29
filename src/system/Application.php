<?php
namespace swiftphp\core\system;

/**
 * 应用管理
 * @author Administrator
 *
 */
class Application
{
    /**
     * 执行一个可执行对象
     * @param IRunnable $target
     */
    public static function run(IRunnable $target)
    {
        $target->run();
    }
}

