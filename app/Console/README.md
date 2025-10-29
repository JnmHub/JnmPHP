# 控制台命令

此目录包含 JnmPHP 框架的控制台命令类。所有命令都基于 Symfony Console 组件构建。

## 目录结构

```
app/Console/
├── Commands/                    # 命令类文件目录
│   ├── GenerateIdeHelperCommand.php  # IDE 辅助文件生成命令
│   └── HelloWorldCommand.php         # Hello World 示例命令
└── README.md                   # 本文件
```

## 可用命令

### 1. ide-helper:models
为 Eloquent 模型生成 PHPDoc 辅助文件，帮助 IDE 提供更好的代码提示。

**功能特性：**
- 扫描 `app/Models` 目录下的所有模型类
- 自动生成基于属性的 PHPDoc 注解
- 支持数据库关系（HasMany、BelongsTo 等）
- 生成访问器/修改器的方法提示
- 包含 Eloquent 魔术方法（where、find 等）的 IDE 支持

**使用方法：**
```bash
php jnm ide-helper:models
```

**输出文件：** `cache/_ide_helper_models.php`

### 2. app:hello-world
Hello World 示例命令，展示如何创建基础的控制台命令。

**参数：**
- `name` (可选): 要问候的对象名称，默认为 "World"

**选项：**
- `--uppercase`, `-u`: 以大写形式输出问候语

**使用示例：**
```bash
# 基础用法
php jnm app:hello-world

# 指定名称
php jnm app:hello-world "JnmPHP"

# 使用大写选项
php jnm app:hello-world "JnmPHP" --uppercase
php jnm app:hello-world -u "JnmPHP"
```

## 创建新命令

### 基础命令模板

```php
<?php

namespace App\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class YourCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->setName('command:name')
            ->setDescription('命令描述')
            ->setHelp('命令详细帮助信息')
            ->addArgument('argument_name', InputArgument::REQUIRED, '参数描述')
            ->addOption('option_name', 'o', InputOption::VALUE_OPTIONAL, '选项描述', 'default_value');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // 命令逻辑实现
        $output->writeln('<info>命令执行成功</info>');

        return Command::SUCCESS; // 或者 Command::FAILURE
    }
}
```

### 命令开发指南

1. **命名规范：** 命令类名应以 `Command` 结尾，使用描述性的类名
2. **命名空间：** 所有命令类都应放在 `App\Console\Commands` 命名空间下
3. **文件位置：** 将命令文件放在 `app/Console/Commands/` 目录中
4. **返回值：** `execute()` 方法应返回 `Command::SUCCESS`（成功）或 `Command::FAILURE`（失败）

### 参数和选项

**参数 (Arguments)：**
- `InputArgument::REQUIRED` - 必需参数
- `InputArgument::OPTIONAL` - 可选参数
- `InputArgument::IS_ARRAY` - 可接收多个值

**选项 (Options)：**
- `InputOption::VALUE_NONE` - 布尔值标志
- `InputOption::VALUE_REQUIRED` - 需要值的选项
- `InputOption::VALUE_OPTIONAL` - 可选值的选项
- `InputOption::VALUE_IS_ARRAY` - 可接收多个值的选项

## 输出格式化

使用不同的标签来格式化输出：

```php
$output->writeln('<info>绿色信息</info>');
$output->writeln('<comment>黄色注释</comment>');
$output->writeln('<question>蓝色问题</question>');
$output->writeln('<error>红色错误</error>');
```

## 运行命令

所有命令都通过 `jnm` 脚本运行：

```bash
php jnm <command:name> [arguments] [options]
```

查看所有可用命令：
```bash
php jnm list
```

查看特定命令的帮助：
```bash
php jnm <command:name> --help
```