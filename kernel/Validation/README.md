# 验证系统

Validation 命名空间为 JnmPHP 框架提供了强大的数据验证功能。它基于 Laravel 验证组件构建，提供了丰富的验证规则、自定义验证器、数据库存在性验证等功能，确保数据的完整性和安全性。

## 概述

验证系统是 JnmPHP 框架的核心组件之一，负责验证和处理用户输入数据。通过集成 Laravel 的验证组件，框架提供了成熟稳定的验证功能，同时支持国际化消息和自定义验证规则。

## 核心功能

- **丰富的验证规则**：支持所有 Laravel 标准验证规则
- **数据库验证**：支持 unique、exists 等数据库相关验证
- **自定义验证器**：支持自定义验证规则和消息
- **国际化支持**：基于 Laravel 翻译组件的多语言支持
- **错误处理**：结构化的错误消息和异常处理
- **类型安全**：强类型的验证接口设计
- **性能优化**：高效的验证执行机制

## 核心组件

### ValidatorFactory.php

验证器工厂类，负责创建和配置验证器实例。

#### 主要方法

**构造方法：**
```php
public function __construct(Translator $translator)
```

**核心方法：**
- `make(array $data, array $rules): Validator` - 创建验证器实例

#### 初始化过程

1. **翻译器设置**：使用 Laravel 的翻译组件
2. **数据库连接**：通过 DB 类获取数据库实例
3. **存在性验证器**：配置数据库存在性验证功能
4. **工厂配置**：完成验证器工厂的所有配置

#### 设计特点

- **数据库集成**：与框架的 DB 类深度集成
- **错误处理**：完善的异常处理机制
- **可扩展性**：支持自定义验证规则和消息
- **类型安全**：强类型的方法签名

## 使用示例

### 基本验证

```php
use Kernel\Validation\ValidatorFactory;
use Illuminate\Translation\Translator;
use Illuminate\Translation\FileLoader;
use Illuminate\Filesystem\Filesystem;

// 创建翻译器
$loader = new FileLoader(new Filesystem(), lang_path());
$translator = new Translator($loader, 'zh_CN');

// 创建验证器工厂
$validatorFactory = new ValidatorFactory($translator);

// 验证数据
$data = [
    'name' => 'John Doe',
    'email' => 'john@example.com',
    'age' => 25,
    'password' => 'secret123',
    'password_confirmation' => 'secret123'
];

$rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users',
    'age' => 'required|integer|min:18|max:100',
    'password' => 'required|string|min:6|confirmed'
];

$validator = $validatorFactory->make($data, $rules);

if ($validator->fails()) {
    // 验证失败
    $errors = $validator->errors()->all();
    return JsonResponse::error('验证失败', 422, ['errors' => $errors]);
}

// 验证成功
$validatedData = $validator->validated();
```

### 在控制器中使用

```php
class UserController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        // 获取验证器工厂
        $validatorFactory = app('validator');

        // 验证请求数据
        $validator = $validatorFactory->make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ]);

        // 验证失败处理
        if ($validator->fails()) {
            return JsonResponse::error('验证失败', 422, [
                'errors' => $validator->errors()->toArray()
            ]);
        }

        // 获取验证后的数据
        $validatedData = $validator->validated();

        // 创建用户
        $user = User::create($validatedData);

        return JsonResponse::success($user, 201);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validator = app('validator')->make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $id,
            'password' => 'sometimes|required|string|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return JsonResponse::error('验证失败', 422, [
                'errors' => $validator->errors()->toArray()
            ]);
        }

        $user->update($validator->validated());

        return JsonResponse::success($user);
    }
}
```

### 与 RequestBody 结合使用

```php
class UserCreateRequest extends BaseModel
{
    #[TableField('user_name')]
    #[Validate(['required', 'string', 'max:50'])]
    public string $userName;

    #[TableField('email')]
    #[Validate(['required', 'email', 'unique:users'])]
    public string $email;

    #[TableField('password')]
    #[Validate(['required', 'string', 'min:6'])]
    public string $password;

    #[TableField('confirm_password')]
    #[Validate(['required', 'string', 'same:password'])]
    public string $confirmPassword;
}

class UserController extends BaseController
{
    #[Post('/users')]
    public function store(
        #[RequestBody] UserCreateRequest $request
    ): JsonResponse {
        // $request 已经通过属性验证和自动填充
        $user = User::create([
            'user_name' => $request->userName,
            'email' => $request->email,
            'password' => bcrypt($request->password)
        ]);

        return JsonResponse::success($user, 201);
    }
}
```

### 数据库验证

```php
// unique 验证
$rules = [
    'email' => 'required|email|unique:users,email',
    'username' => 'required|string|unique:users,username',
    'slug' => 'required|string|unique:posts:slug,deleted_at'
];

// exists 验证
$rules = [
    'user_id' => 'required|exists:users,id',
    'category_id' => 'required|exists:categories,id,deleted_at,NULL',
    'country_code' => 'required|exists:countries,code,active,1'
];

// 复杂的数据库验证
$rules = [
    'email' => 'required|email|unique:users,email,' . $userId,
    'promotion_code' => [
        'required',
        'string',
        'exists:promotion_codes,code,active,1,expires_at,>=' . date('Y-m-d')
    ]
];
```

### 自定义验证规则

```php
// 在服务提供者中注册自定义验证规则
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $validatorFactory = app('validator');

        // 添加自定义验证规则
        $validatorFactory->extend('phone', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^1[3-9]\d{9}$/', $value);
        });

        $validatorFactory->extend('id_card', function ($attribute, $value, $parameters, $validator) {
            return preg_match('/^[1-9]\d{5}(19|20)\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])\d{3}[\dXx]$/', $value);
        });

        // 添加自定义验证消息
        $validatorFactory->replacer('phone', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute 必须是有效的手机号码');
        });

        $validatorFactory->replacer('id_card', function ($message, $attribute, $rule, $parameters) {
            return str_replace(':attribute', $attribute, ':attribute 必须是有效的身份证号码');
        });
    }
}

// 使用自定义验证规则
$rules = [
    'phone' => 'required|phone',
    'id_card' => 'required|id_card'
];
```

### 条件验证

```php
// 有时验证字段
$rules = [
    'email' => 'required|email',
    'phone' => 'sometimes|required|phone',
    'address' => 'required_with:phone|string|max:255'
];

// 条件验证
$data = $request->all();
$rules = [
    'type' => 'required|in:individual,company',
    'company_name' => 'required_if:type,company|string|max:255',
    'tax_number' => 'required_if:type,company|string|max:50',
    'personal_name' => 'required_if:type,individual|string|max:255',
    'id_card' => 'required_if:type,individual|id_card'
];

$validator = app('validator')->make($data, $rules);
```

### 表单请求验证

```php
abstract class FormRequest
{
    protected ValidatorFactory $validator;
    protected array $data;

    public function __construct(ValidatorFactory $validator, array $data)
    {
        $this->validator = $validator;
        $this->data = $data;
    }

    abstract public function rules(): array;

    public function validate(): array
    {
        $validator = $this->validator->make($this->data, $this->rules());

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}

class CreateUserRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ];
    }
}

// 在控制器中使用
class UserController extends BaseController
{
    public function store(Request $request): JsonResponse
    {
        $formRequest = new CreateUserRequest(app('validator'), $request->all());

        try {
            $validatedData = $formRequest->validate();
            $user = User::create($validatedData);
            return JsonResponse::success($user, 201);
        } catch (ValidationException $e) {
            return JsonResponse::error('验证失败', 422, [
                'errors' => $e->errors()
            ]);
        }
    }
}
```

## 验证规则详解

### 基础验证规则

```php
$rules = [
    // 必填验证
    'field' => 'required',

    // 类型验证
    'name' => 'string',
    'age' => 'integer',
    'price' => 'numeric',
    'active' => 'boolean',
    'avatar' => 'file',
    'settings' => 'array',

    // 字符串验证
    'title' => 'string|max:255',
    'description' => 'string|min:10',
    'slug' => 'string|alpha_dash',
    'password' => 'string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',

    // 数值验证
    'age' => 'integer|min:18|max:100',
    'score' => 'numeric|between:0,100',
    'discount' => 'numeric|max:0.99',

    // 日期验证
    'birthday' => 'date',
    'start_date' => 'date|after:today',
    'end_date' => 'date|after:start_date',
    'published_at' => 'date|before:tomorrow'
];
```

### 文件验证

```php
$rules = [
    'avatar' => 'required|file|image|mimes:jpeg,png,jpg|max:2048',
    'document' => 'required|file|mimes:pdf,doc,docx|max:10240',
    'photos' => 'required|array|max:3',
    'photos.*' => 'file|image|mimes:jpeg,png,jpg|max:1024'
];
```

### 数组验证

```php
$rules = [
    'tags' => 'required|array|min:1|max:5',
    'tags.*' => 'required|string|max:50',
    'settings' => 'required|array',
    'settings.theme' => 'required|string|in:light,dark',
    'settings.notifications' => 'required|boolean'
];
```

### 正则表达式验证

```php
$rules = [
    'phone' => 'required|regex:/^1[3-9]\d{9}$/',
    'password' => 'required|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/',
    'slug' => 'required|regex:/^[a-z0-9-]+$/'
];
```

## 错误处理

### 基本错误处理

```php
$validator = app('validator')->make($data, $rules);

if ($validator->fails()) {
    // 获取所有错误消息
    $allErrors = $validator->errors()->all();

    // 获取指定字段的错误
    $emailErrors = $validator->errors()->get('email');

    // 获取第一条错误消息
    $firstError = $validator->errors()->first();

    // 转换为数组格式
    $errorsArray = $validator->errors()->toArray();

    return JsonResponse::error('验证失败', 422, ['errors' => $errorsArray]);
}
```

### 自定义错误消息

```php
$rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users'
];

$messages = [
    'name.required' => '姓名不能为空',
    'name.max' => '姓名不能超过255个字符',
    'email.required' => '邮箱地址不能为空',
    'email.email' => '请输入有效的邮箱地址',
    'email.unique' => '该邮箱地址已被注册'
];

$validator = app('validator')->make($data, $rules, $messages);
```

### 自定义属性名称

```php
$attributes = [
    'name' => '姓名',
    'email' => '邮箱地址',
    'password' => '密码',
    'confirm_password' => '确认密码'
];

$validator = app('validator')->make($data, $rules, $messages, $attributes);
```

### 验证异常处理

```php
class ValidationException extends \Exception
{
    protected Validator $validator;

    public function __construct(Validator $validator)
    {
        $this->validator = $validator;
        parent::__construct('验证失败');
    }

    public function errors(): array
    {
        return $this->validator->errors()->toArray();
    }

    public function getValidator(): Validator
    {
        return $this->validator;
    }
}

// 在服务提供者中注册全局异常处理器
class Handler
{
    public function handle(\Exception $e): JsonResponse
    {
        if ($e instanceof ValidationException) {
            return JsonResponse::error('验证失败', 422, [
                'errors' => $e->errors()
            ]);
        }

        // 处理其他异常...
    }
}
```

## 国际化支持

### 配置翻译文件

```php
// lang/zh_CN/validation.php
return [
    'required' => ':attribute 是必填字段',
    'string' => ':attribute 必须是字符串',
    'email' => ':attribute 必须是有效的邮箱地址',
    'unique' => ':attribute 已存在',
    'min' => [
        'string' => ':attribute 至少需要 :min 个字符',
        'numeric' => ':attribute 最小值为 :min',
        'file' => ':attribute 大小至少为 :min KB'
    ],
    'max' => [
        'string' => ':attribute 不能超过 :max 个字符',
        'numeric' => ':attribute 最大值为 :max',
        'file' => ':attribute 大小不能超过 :max KB'
    ],
    'between' => ':attribute 必须在 :min 到 :max 之间',
    'in' => ':attribute 必须是以下值之一：:values',
    'regex' => ':attribute 格式不正确'
];

// lang/zh_CN/validation.php (自定义验证规则)
'phone' => ':attribute 必须是有效的手机号码',
'id_card' => ':attribute 必须是有效的身份证号码'
```

### 多语言验证

```php
// 根据请求语言设置翻译器
class LocalizationMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $locale = $request->header('Accept-Language', 'zh_CN');

        // 设置应用语言
        app()->setLocale($locale);

        // 创建对应语言的验证器
        $loader = new FileLoader(new Filesystem(), lang_path());
        $translator = new Translator($loader, $locale);

        app()->singleton('validator', function () use ($translator) {
            return new ValidatorFactory($translator);
        });

        return $next($request);
    }
}
```

## 性能优化

### 验证规则缓存

```php
class CachedValidationRules
{
    private static array $cache = [];

    public static function getRules(string $key): array
    {
        if (!isset(self::$cache[$key])) {
            self::$cache[$key] = self::loadRules($key);
        }

        return self::$cache[$key];
    }

    private static function loadRules(string $key): array
    {
        return match($key) {
            'user.create' => [
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|string|min:6|confirmed'
            ],
            'user.update' => [
                'name' => 'sometimes|required|string|max:255',
                'email' => 'sometimes|required|email|unique:users,email,{id}',
                'password' => 'sometimes|required|string|min:6|confirmed'
            ],
            default => []
        };
    }
}
```

### 延迟验证

```php
class LazyValidation
{
    public function validateWhenNeeded(array $data, string $ruleSet): array
    {
        // 只在需要时才创建验证器
        static $validators = [];

        if (!isset($validators[$ruleSet])) {
            $rules = CachedValidationRules::getRules($ruleSet);
            $validators[$ruleSet] = app('validator')->make([], $rules);
        }

        $validator = clone $validators[$ruleSet];
        $validator->setData($data);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }
}
```

### 批量验证

```php
class BatchValidation
{
    public function validateBatch(array $items, array $rules): array
    {
        $results = [];
        $errors = [];

        foreach ($items as $index => $item) {
            $validator = app('validator')->make($item, $rules);

            if ($validator->fails()) {
                $errors[$index] = $validator->errors()->toArray();
            } else {
                $results[$index] = $validator->validated();
            }
        }

        if (!empty($errors)) {
            throw new BatchValidationException($errors, '批量验证失败');
        }

        return $results;
    }
}
```

## 测试验证

### 单元测试示例

```php
class ValidationTest extends TestCase
{
    private ValidatorFactory $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = app('validator');
    }

    public function testValidEmail()
    {
        $data = ['email' => 'test@example.com'];
        $rules = ['email' => 'required|email'];

        $validator = $this->validator->make($data, $rules);

        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->fails());
    }

    public function testInvalidEmail()
    {
        $data = ['email' => 'invalid-email'];
        $rules = ['email' => 'required|email'];

        $validator = $this->validator->make($data, $rules);

        $this->assertFalse($validator->passes());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('email', $validator->errors()->toArray());
    }

    public function testCustomValidationRule()
    {
        $this->validator->extend('phone', function ($attribute, $value) {
            return preg_match('/^1[3-9]\d{9}$/', $value);
        });

        $data = ['phone' => '13812345678'];
        $rules = ['phone' => 'required|phone'];

        $validator = $this->validator->make($data, $rules);

        $this->assertTrue($validator->passes());
    }
}
```

## 故障排除

### 常见问题

1. **数据库验证失败**
   ```php
   // 检查数据库连接
   try {
       DB::connection()->getPdo();
   } (\Exception $e) {
       error_log("数据库连接失败: " . $e->getMessage());
   }

   // 检查表名和字段名
   $rules = [
       'email' => 'required|email|unique:users,email_address' // 确保字段名正确
   ];
   ```

2. **自定义验证规则不生效**
   ```php
   // 确保在服务提供者的 boot 方法中注册
   public function boot(): void
   {
       $validator = app('validator');
       $validator->extend('custom_rule', function ($attribute, $value) {
           return true; // 验证逻辑
       });
   }
   ```

3. **翻译文件不生效**
   ```php
   // 检查翻译文件路径
   $loader = new FileLoader(new Filesystem(), lang_path());
   $translator = new Translator($loader, 'zh_CN');

   // 检查语言包是否存在
   if (!$loader->load('zh_CN', 'validation')) {
       error_log("验证语言包加载失败");
   }
   ```

### 调试技巧

```php
class DebugValidator extends ValidatorFactory
{
    public function make(array $data, array $rules, array $messages = [], array $customAttributes = []): Validator
    {
        $validator = parent::make($data, $rules, $messages, $customAttributes);

        if (DEBUG) {
            error_log("验证数据: " . json_encode($data));
            error_log("验证规则: " . json_encode($rules));
            error_log("验证结果: " . ($validator->fails() ? '失败' : '成功'));

            if ($validator->fails()) {
                error_log("验证错误: " . json_encode($validator->errors()->toArray()));
            }
        }

        return $validator;
    }
}
```

## 最佳实践

### 1. 验证规则组织

```php
// 创建专门的验证规则类
class UserValidationRules
{
    public static function create(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:6|confirmed'
        ];
    }

    public static function update(int $userId): array
    {
        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,' . $userId,
            'password' => 'sometimes|required|string|min:6|confirmed'
        ];
    }

    public static function login(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required|string'
        ];
    }
}
```

### 2. 验证中间件

```php
class ValidationMiddleware implements MiddlewareInterface
{
    public function handle(Request $request, callable $next): Response
    {
        $rules = $this->getRulesForRoute($request);

        if (!empty($rules)) {
            $validator = app('validator')->make($request->all(), $rules);

            if ($validator->fails()) {
                return JsonResponse::error('验证失败', 422, [
                    'errors' => $validator->errors()->toArray()
                ]);
            }
        }

        return $next($request);
    }

    private function getRulesForRoute(Request $request): array
    {
        $uri = $request->uri;
        $method = $request->method;

        return match("{$method}@{$uri}") {
            'POST@/api/users' => UserValidationRules::create(),
            'PUT@/api/users/{id}' => UserValidationRules::update($request->route('id')),
            'POST@/api/login' => UserValidationRules::login(),
            default => []
        };
    }
}
```

### 3. 安全验证实践

```php
class SecurityValidationRules
{
    public static function password(): array
    {
        return [
            'required',
            'string',
            'min:8',
            'confirmed',
            'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/'
        ];
    }

    public static function upload(): array
    {
        return [
            'required',
            'file',
            'max:10240', // 10MB
            'mimes:jpeg,jpg,png,gif,pdf,doc,docx',
            'image' // 如果是图片
        ];
    }

    public static function inputSanitization(): array
    {
        return [
            'string',
            'regex:/^[a-zA-Z0-9\s\-_.]+$/', // 只允许安全字符
            'max:1000' // 限制输入长度
        ];
    }
}
```