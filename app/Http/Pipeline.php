<?php
// 文件路径: app/Core/Http/Pipeline.php

namespace App\Http;

use Closure;
use RuntimeException;

class Pipeline
{
    /**
     * @var mixed 需要通过管道传递的对象
     */
    private mixed $passable;

    /**
     * @var array 管道中的所有“管子”（中间件）
     */
    private array $pipes = [];

    /**
     * 设置要发送通过管道的对象
     */
    public static function init():self
    {
        return new self();
    }
    public function send(mixed $passable): self
    {
        $this->passable = $passable;
        return $this;
    }

    /**
     * 设置管道中的所有中间件
     */
    public function through(array $pipes): self
    {
        $this->pipes = $pipes;
        return $this;
    }

    /**
     * 运行管道，并提供最终的回调（即“洋葱”的核心）
     */
    public function then(Closure $destination)
    {
        // 将所有中间件通过 array_reduce 从里到外地包装成一个巨大的、层层嵌套的闭包
        $pipeline = array_reduce(
            array_reverse($this->pipes), // 反转数组，确保从第一个中间件开始包装
            $this->carry(),
            function () use ($destination) {
                // 这是最里层，是最终要执行的目标
                return $destination();
            }
        );

        // 运行这个最终构建好的“洋葱”闭包
        return $pipeline($this->passable);
    }

    /**
     * 生成一个用于 array_reduce 的闭包，它负责将下一层包裹在当前层之内
     */
    private function carry(): Closure
    {
        /**
         * @params mixed|$stack 是初始值，第一次循环为初始值，第二次就为上次执行的值，这是累加器
         * @params mixed|$pipe  是当前值
         */
        return function ($stack, $pipe) {
            return function ($passable) use ($stack, $pipe) {
                // 实例化中间件
                if (!class_exists($pipe)) {
                    throw new RuntimeException("中间件类不存在: {$pipe}");
                }

                $middleware = new $pipe();

                if (!($middleware instanceof MiddlewareInterface)) {
                    throw new RuntimeException("中间件 {$pipe} 必须实现 MiddlewareInterface 接口");
                }

                // 调用中间件的 handle 方法
                // 关键点：将下一层（$stack）本身作为一个闭包（$next）传递给当前中间件
                return $middleware->handle($passable, $stack);
            };
        };
    }
}