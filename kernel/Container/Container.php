<?php

namespace Kernel\Container;

use Illuminate\Container\Container as IlluminateContainer;

class Container
{
    private static ?IlluminateContainer $instance = null;

    /**
     * 初始化容器并将其设置为单例实例。
     */
    public static function init(): IlluminateContainer
    {
        if (self::$instance === null) {
            self::$instance = new IlluminateContainer();
            // 将容器实例自身也绑定到容器中，方便后续解析
            self::$instance->instance(IlluminateContainer::class, self::$instance);
        }
        return self::$instance;
    }

    /**
     * 获取容器的单例实例。
     *
     * @return IlluminateContainer
     */
    public static function getInstance(): IlluminateContainer
    {
        if (self::$instance === null) {
            // 确保即使在未显式调用的情况下也能初始化
            return self::init();
        }
        return self::$instance;
    }
}