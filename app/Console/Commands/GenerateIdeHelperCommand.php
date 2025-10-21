<?php

namespace App\Console\Commands;

use Illuminate\Support\Str;
use Kernel\Application; // 假设 Application 类在 Kernel 命名空间
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use ReflectionUnionType;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

class GenerateIdeHelperCommand extends Command
{
    // 命令的名称
    protected static $defaultName = 'ide-helper:models';

    // 命令的描述
    protected function configure(): void
    {
        $this
            ->setName('ide-helper:models') // 确保设置名称
            ->setDescription('Generates a PHPDoc helper file for Eloquent models.')
            ->setHelp('This command scans your app/Models directory and generates PHPDoc annotations for magic methods and properties used by Eloquent and custom traits.');
    }

    // 执行命令的逻辑
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // --- 将原脚本的核心逻辑移到这里 ---

        // --- 脚本配置 ---
        // 注意：APP_ROOT 已经在 jnm 文件中定义，可以直接使用
        $appNamespace = 'App\\';
        $modelBasePath = APP_ROOT . '/app/Models';
        $outputFile = APP_ROOT . '/cache/_ide_helper_models.php';
        $baseModelClass = \Kernel\Database\BaseModel::class;

        // --- 属性注解常量 ---
        $attrTableField = \Kernel\Attribute\Database\TableField::class;
        $attrHasMany = \Kernel\Attribute\Database\HasMany::class;
        $attrHasOne = \Kernel\Attribute\Database\HasOne::class;
        $attrBelongsTo = \Kernel\Attribute\Database\BelongsTo::class;
        $attrBelongsToMany = \Kernel\Attribute\Database\BelongsToMany::class;
        $attrAccessor = \Kernel\Attribute\ModelAccessor\Accessor::class;
        $attrMutator = \Kernel\Attribute\ModelAccessor\Mutator::class;

        $relationAttributes = [ $attrHasMany, $attrHasOne, $attrBelongsTo, $attrBelongsToMany ];

        /**
         * 辅助函数 (保持不变)
         */
        $reflectionTypeToString = function (?ReflectionType $type, bool $addLeadingSlashForClasses = true): string {
            if ($type === null) return '';
            $processName = function ($name) use ($addLeadingSlashForClasses) {
                $builtInTypes = ['int', 'float', 'string', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed', 'void', 'null', 'false', 'true', 'self', 'static', 'parent'];
                if (in_array(ltrim($name, '?'), $builtInTypes)) return $name;
                if ($addLeadingSlashForClasses && strpos($name, '\\') !== false && $name[0] !== '\\') return '\\' . $name;
                return $name;
            };
            if ($type instanceof ReflectionUnionType) {
                $names = array_map(fn($t) => $processName($t->getName()), $type->getTypes());
                return implode('|', $names) . ' ';
            }
            if ($type instanceof ReflectionIntersectionType) {
                $names = array_map(fn($t) => $processName($t->getName()), $type->getTypes());
                return implode('&', $names) . ' ';
            }
            if ($type instanceof ReflectionNamedType) {
                $prefix = ($type->allowsNull() && $type->getName() !== 'mixed') ? '?' : '';
                $name = $processName($type->getName());
                if ($prefix === '?' && $name[0] === '\\') return '?' . $name;
                return $prefix . $name . ' ';
            }
            return 'mixed ';
        };


        $output->writeln("<info>Starting IDE Helper generation...</info>");
        $output->writeln("[DEBUG] 检查模型路径: " . $modelBasePath);
        if (!is_dir($modelBasePath)) {
            $output->writeln("<error>[ERROR] 模型路径不存在! 请检查路径定义。</error>");
            return Command::FAILURE;
        }

        $finalOutput = "<?php
// @formatter:off
// phpcs:ignoreFile

/**
 * 这是一个 IDE 辅助文件，由 JnmPHP Console Command 自动生成。
 * 它不应在运行时被加载。
 *
 * 生成时间: " . date('Y-m-d H:i:s') . "
 */
\n";

        // --- 1. 获取 HasCrud Trait 的方法 ---
        $crudMethodsDoc = "";
        try {
            $crudRef = new ReflectionClass(\Kernel\Database\Traits\HasCrud::class);
            $crudMethods = $crudRef->getMethods(ReflectionMethod::IS_PUBLIC | ReflectionMethod::IS_STATIC);
            if (!empty($crudMethods)) {
                $crudMethodsDoc .= " * --- 静态 HasCrud Trait 方法 ---\n";
                foreach ($crudMethods as $method) {
                    $params = array_map(function ($p) use ($reflectionTypeToString) {
                        $type = $reflectionTypeToString($p->getType());
                        $default = $p->isDefaultValueAvailable() ? (' = ' . str_replace(["\n", "\r"], "", var_export($p->getDefaultValue(), true))) : '';
                        return $type . '$' . $p->getName() . $default;
                    }, $method->getParameters());
                    $returnType = $reflectionTypeToString($method->getReturnType(), true);
                    if (trim($returnType) === '') $returnType = 'mixed';
                    $returnType = str_replace(['static', '?static'], ['$MODEL_NAME$', '?$MODEL_NAME$'], $returnType);
                    $crudMethodsDoc .= " * @method static {$returnType} {$method->getName()}(" . implode(', ', $params) . ")\n";
                }
            }
        } catch (ReflectionException $e) {
            $output->writeln("<warning>Warning: Could not reflect HasCrud trait: {$e->getMessage()}</warning>");
        }


        // --- 2. 扫描模型目录 ---
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($modelBasePath, \FilesystemIterator::SKIP_DOTS)
        );

        $modelCount = 0;

        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDir() || $fileInfo->getExtension() !== 'php') continue;
            $filePath = $fileInfo->getRealPath();

            $output->writeln("[DEBUG] 找到文件: " . str_replace(APP_ROOT, '', $filePath));

            // --- 动态构建类名 ---
            $relativePath = str_replace(APP_ROOT . '/app/', '', $filePath);
            $classPath = str_replace(['/', '.php'], ['\\', ''], $relativePath);
            $className = $appNamespace . $classPath;
            $namespace = substr($className, 0, strrpos($className, '\\'));

            try {
                if (!class_exists($className)) {
                    $output->writeln("    <comment>[SKIP] '{$className}' 类不存在 (自动加载问题?).</comment>");
                    continue;
                }
                $ref = new ReflectionClass($className);
                if ($ref->isAbstract() || !$ref->isSubclassOf($baseModelClass)) {
                    $output->writeln("    <comment>[SKIP] '{$className}' 不是 {$baseModelClass} 的子类.</comment>");
                    continue;
                }

                $modelCount++;
                $shortName = $ref->getShortName();
                $output->writeln("    [OK] 正在处理模型: {$shortName} (Namespace: {$namespace})");

                $properties = $ref->getProperties(ReflectionProperty::IS_PROTECTED);
                $methods = $ref->getMethods(ReflectionMethod::IS_PUBLIC);

                $doc = "\nnamespace {$namespace} {\n";
                $doc .= "    /**\n * {$className}\n *\n";

                // --- @property 和 @property-read ---
                $doc .= " * --- 属性 (Properties) ---\n";
                $magicColumns = [];
                foreach ($properties as $prop) {
                    if ($prop->getDeclaringClass()->getName() !== $className) continue; // 只处理当前类定义的
                    $propName = $prop->getName();
                    $isRelation = false;
                    foreach ($relationAttributes as $attrName) {
                        if (!empty($prop->getAttributes($attrName))) {
                            $isRelation = true;
                            $attrInstance = $prop->getAttributes($attrName)[0]->newInstance();
                            $relatedClass = $attrInstance->related;
                            if (strpos($relatedClass, '\\') !== 0) $relatedClass = '\\' . $relatedClass;
                            $propType = "\\Illuminate\\Database\\Eloquent\\Collection|{$relatedClass}[]";
                            if ($attrName === $attrHasOne || $attrName === $attrBelongsTo) $propType = $relatedClass;
                            $doc .= " * @property-read {$propType} \${$propName}\n";
                            break;
                        }
                    }
                    if (!$isRelation && !empty($prop->getAttributes($attrTableField))) {
                        $propTypeOutput = $reflectionTypeToString($prop->getType(), true);
                        if (trim($propTypeOutput) === '') $propTypeOutput = 'mixed';
                        $doc .= " * @property {$propTypeOutput} \${$propName}\n";
                    }
                    if ($isRelation || !empty($prop->getAttributes($attrTableField))) {
                        $magicColumns[] = $propName;
                    }
                }

                // --- @method (Static Magic Columns) ---
                $doc .= " *\n * --- 静态魔术列名 (Static Magic Columns) ---\n";
                foreach ($magicColumns as $propName) {
                    $methodName = '_' . ucfirst($propName);
                    $doc .= " * @method static string {$methodName}()\n";
                }

                // --- @method (Static Magic Wheres) ---
                $doc .= " *\n * --- 静态魔术查询 (Static Magic Wheres) ---\n";
                $builder = "\\Illuminate\\Database\\Eloquent\\Builder|{$shortName}";
                foreach ($magicColumns as $propName) {
                    $methodName = 'where' . ucfirst($propName);
                    $doc .= " * @method static {$builder} {$methodName}(\$value)\n";
                }

                // --- @method (HasCrud Trait) ---
                if ($crudMethodsDoc) {
                    $doc .= " *\n" . str_replace('$MODEL_NAME$', $shortName, $crudMethodsDoc);
                }

                // --- @method (Base Eloquent Static) ---
                $doc .= " *\n * --- 常用静态 Eloquent 方法 ---\n";
                $doc .= " * @method static {$builder} newModelQuery()\n";
                $doc .= " * @method static {$builder} newQuery()\n";
                $doc .= " * @method static {$builder} query()\n";
                $doc .= " * @method static {$shortName}|null find(mixed \$id, array \$columns = ['*'])\n";
                $doc .= " * @method static \Illuminate\Database\Eloquent\Collection findMany(array \$ids, array \$columns = ['*'])\n";
                $doc .= " * @method static {$shortName} findOrFail(mixed \$id, array \$columns = ['*'])\n";
                $doc .= " * @method static {$shortName} firstOrFail(array \$columns = ['*'])\n";
                $doc .= " * @method static {$shortName} firstOrNew(array \$attributes = [], array \$values = [])\n";
                $doc .= " * @method static {$shortName} firstOrCreate(array \$attributes = [], array \$values = [])\n";
                $doc .= " * @method static {$shortName} updateOrCreate(array \$attributes, array \$values = [])\n";
                $doc .= " * @method static {$shortName} firstOr(array \$columns = ['*'], \Closure \$callback = null)\n";
                $doc .= " * @method static \Illuminate\Database\Eloquent\Collection all(array \$columns = ['*'])\n";
                $doc .= " * @method static {$shortName} create(array \$attributes)\n";
                $doc .= " * @method static int insert(array \$values)\n";
                $doc .= " * @method static int insertOrIgnore(array \$values)\n";
                $doc .= " * @method static int upsert(array \$values, mixed \$uniqueBy, array|null \$update = null)\n";
                $doc .= " * @method static {$builder} withGlobalScope(string \$identifier, \Closure \$scope)\n";
                $doc .= " * @method static {$builder} withoutGlobalScope(string \$scope)\n";
                $doc .= " * @method static {$builder} withoutGlobalScopes(array|null \$scopes = null)\n";

                // --- @method (Accessors/Mutators) ---
                $amMethods = [];
                foreach ($methods as $method) {
                    if (!empty($method->getAttributes($attrAccessor)) || !empty($method->getAttributes($attrMutator))) {
                        if ($method->getDeclaringClass()->getName() !== $className) continue;
                        $params = array_map(function ($p) use ($reflectionTypeToString) {
                            $type = $reflectionTypeToString($p->getType(), true);
                            return $type . '$' . $p->getName();
                        }, $method->getParameters());
                        $returnType = $reflectionTypeToString($method->getReturnType(), true);
                        if (trim($returnType) === '') $returnType = 'mixed';
                        $amMethods[] = " * @method {$returnType} {$method->getName()}(" . implode(', ', $params) . ")\n";
                    }
                }
                if (!empty($amMethods)) {
                    $doc .= " *\n * --- 访问器/修改器 (Accessors/Mutators) ---\n";
                    $doc .= implode("", $amMethods);
                }

                // --- @method (Eloquent Instance Methods) ---
                $doc .= " *\n * --- 常用 Eloquent 实例方法 ---\n";
                $doc .= " * @method bool save(array \$options = [])\n";
                $doc .= " * @method bool update(array \$attributes = [], array \$options = [])\n";
                $doc .= " * @method bool delete()\n";
                $doc .= " * @method bool forceDelete()\n";
                $doc .= " * @method bool restore()\n";
                $doc .= " * @method \$this refresh()\n";
                $doc .= " * @method \$this fill(array \$attributes)\n";
                $doc .= " * @method \$this replicate(array \$except = null)\n";
                $doc .= " * @method bool isDirty(\$attributes = null)\n";
                $doc .= " * @method bool isClean(\$attributes = null)\n";
                $doc .= " * @method bool wasChanged(\$attributes = null)\n";
                $doc .= " * @method array getDirty()\n";
                $doc .= " * @method array getChanges()\n";
                $doc .= " * @method mixed getOriginal(\$key = null, \$default = null)\n";
                $doc .= " * @method bool is(\$model)\n";
                $doc .= " * @method bool isNot(\$model)\n";
                $doc .= " * @method bool exists()\n";
                $doc .= " * @method bool doesntExist()\n";
                $doc .= " * @method \Illuminate\Database\Eloquent\Relations\BelongsTo belongsTo(\$related, \$foreignKey = null, \$ownerKey = null, \$relation = null)\n";
                $doc .= " * @method \Illuminate\Database\Eloquent\Relations\HasOne hasOne(\$related, \$foreignKey = null, \$localKey = null)\n";
                $doc .= " * @method \Illuminate\Database\Eloquent\Relations\HasMany hasMany(\$related, \$foreignKey = null, \$localKey = null)\n";
                $doc .= " * @method \Illuminate\Database\Eloquent\Relations\BelongsToMany belongsToMany(\$related, \$table = null, \$foreignPivotKey = null, \$relatedPivotKey = null, \$parentKey = null, \$relatedKey = null, \$relation = null)\n";

                $doc .= " *\n * @mixin \\Eloquent\n";
                $doc .= " */\n";
                $doc .= " abstract class {$shortName} extends {$baseModelClass} {}\n";
                $doc .= "}\n";

                $finalOutput .= $doc;

            } catch (Throwable $e) {
                $output->writeln("<error>[ERROR] 处理类 {$className} 时发生致命错误: {$e->getMessage()}</error>");
                $output->writeln("    File: {$e->getFile()} on line {$e->getLine()}");
                $output->writeln("    <comment>请检查该文件及其依赖项。</comment>");

            }
        }

        $output->writeln("\n[DEBUG] 扫描完成。总共处理了 {$modelCount} 个模型。");

        // --- 3. 写入文件 ---
        try {
            file_put_contents($outputFile, $finalOutput);
            if ($modelCount > 0) {
                $output->writeln("\n<info>[SUCCESS] 成功生成 IDE 辅助文件 (包含 {$modelCount} 个模型) 于:</info>");
                $output->writeln($outputFile);
            } else {
                $output->writeln("\n<comment>[WARNING] 生成了文件，但是没有找到任何模型。请检查模型路径和文件。</comment>");
                $output->writeln($outputFile);
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln("\n<error>[FATAL] 无法写入文件 {$outputFile}: {$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }
}