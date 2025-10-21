<?php

namespace Kernel\Validation;

use Illuminate\Validation\Factory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Validator;
use Kernel\Database\DB;

// 不再需要 'use Capsule'，我们通过 DB 类来获取

class ValidatorFactory
{
    protected Factory $factory;
    public function __construct(Translator $translator)
    {
        // 1. 设置翻译器

        $this->factory = new Factory($translator);

        // 2. 【核心修正】

        // 2a. 【从 DB 类获取实例】
        //     不再调用 Capsule::instance()，而是从我们的 DB 类获取
        $capsuleInstance = DB::getCapsule();

        if (!$capsuleInstance) {
            throw new \RuntimeException('Database has not been initialized. Call DB::init() first.');
        }

        // 2b. 在【正确的实例】上获取管理器
        $databaseManager = $capsuleInstance->getDatabaseManager();

        // 2c. 创建并注入适配器
        $verifier = new DatabasePresenceVerifier($databaseManager);
        $this->factory->setPresenceVerifier($verifier);
    }

    // ... make 方法保持不变 ...
    public function make(array $data, array $rules): Validator
    {
        return $this->factory->make($data, $rules);
    }
}