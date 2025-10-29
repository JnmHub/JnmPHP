<?php
namespace App\Controller;

use App\Dto\Department;
use App\Models\Product;
use App\Models\Tag;
use App\Models\User;
use Kernel\Attribute\Http\Get;
use Kernel\Attribute\Http\PathVariable;
use Kernel\Attribute\Http\Post;
use Kernel\Attribute\Http\RequestBody;
use Kernel\Attribute\Http\RoutePrefix;
use Kernel\Attribute\Middleware\Middleware;
use Kernel\Exception\HttpException;
use Kernel\Request\Request;
use Kernel\Response\JsonResponse;
use Kernel\Response\ViewResponse;

#[RoutePrefix('/')]
class IndexController extends BaseController
{

    /**
     * 测试 CSRF 表单提交
     * 对应路由: POST /submit-data
     *
     * @param Request $request 框架会自动注入 Request 对象
     * @return ViewResponse
     */
    #[Post('/submit-data')]
    #[Middleware('CSRF')]
    public function submitData(Request $request): ViewResponse
    {
        // 1. 您不需要在这里写任何 CSRF 验证代码。
        // 2. 因为 `VerifyCsrfTokenMiddleware` 是全局中间件，
        //    它在执行此方法 *之前* 就已经自动验证了 `_token`。
        // 3. 如果 `_token` 无效，中间件会抛出 419 异常，根本不会执行到这里。

        // 4. 从 Request 对象中获取 POST 表单数据
        $body = $request->post; //
        $data = $body['data'] ?? '没有提交 data 字段';

        // 5. 返回一个 JSON 响应，证明成功
        $user = new User();
        $user->setId("111");
        $products = [
            ['name' => User::_UserName(), 'price' => 100],
            ['name' => $user->posts, 'price' => 200],
        ];

        // 关键改动：
        // 将 'index/index' 修改为 'index.index'
        // ViewResponse 会自动寻找 .blade.php 文件
        return $this->view('index.index', [
            'name'     => $data,
            'products' => $products
        ]);
    }
    #[Get('/')]
    #[Middleware('CSRF')]
    public function indexView()
    {

        $user = new User();
        $user->setId("111");
        $products = [
            ['name' => User::_UserName(), 'price' => 100],
            ['name' => $user->posts, 'price' => 200],
        ];

        // 关键改动：
        // 将 'index/index' 修改为 'index.index'
        // ViewResponse 会自动寻找 .blade.php 文件
        return $this->view('index.index', [
            'name'     => 'JnmPHP 开发者',
            'products' => $products
        ]);
    }
    #[Get('/index')]
    #[Middleware("log")]
    public function index($aaa = null)
    {
        // 查找ID为1的用户
        $user = User::find(1);
        $user->name = "asd";
        return $user->toArray();
    }
    #[Get('/info/{aid}')]
    public function getInfo(#[PathVariable('aid')]int $id,Request $rrr): string
    {
        return "Fetching user info for ID: " . ($id ?? 'all');
    }

    /**
     * @param Department $department
     * @return Department
     */
    #[Post('/department')]
    public function createDepartment(#[RequestBody] Department $department,Request $request): Department
    {
        $department->id = rand(100, 999);

        // 框架会自动将返回的对象转为 JSON
        return $department;
    }


    #[Get('/user/{uid}/order/{oid}')]
    public function getOrder(
        #[PathVariable('uid', '用户ID不能为空')] string $userId,
        #[PathVariable('oid', '订单ID缺失')] string $orderId
    ): string {
        return "用户ID：{$userId}，订单ID：{$orderId}";
    }

    #[Get('/user/{id}')]
    public function getUser(
        #[PathVariable('id', '用户ID未提供')] string $id,
        string $extra = '默认信息'
    ): string {
        return "用户：{$id}，额外：{$extra}";
    }
    /**
     * 直接将请求体绑定到 User 模型
     * @param User $user
     * @return User
     */
    #[Post('/users')]
    public function createUser(#[RequestBody] User $user): User
    {
        // 保存到数据库
        $user->save();

        // 返回创建好的用户（框架会自动转为 JSON）
        return $user;
    }

    /**
     * 获取单个用户及其所有文章
     * 访问 GET /users/1/posts
     */
    #[Get('/{id}/posts')]
    public function getUserWithPosts(int $id)
    {
        $user = User::getById($id);


        // ✅ 就像访问普通属性一样，触发关联关系加载！
        $posts = $user->posts;

        // 演示反向关联
        // $firstPost = $posts->first();
        // $postOwner = $firstPost->user; // 触发 BelongsTo

        return [
            'user' => $user->toArray(), // toArray 不会包含关联，除非我们之后再扩展
            'posts' => $posts->toArray(),
        ];
    }
    #[Get('/posts/{id}')]
    public function getPost(int $id)
    {
        $post = \App\Models\Post::getById($id);
        if (!$post) {
            return ['error' => 'Post not found'];
        }
        $user = $post->user;
        return [
            'post' => $post,
            'user_from_relation' => $user
        ];
    }


    #[Get('/posts/{id}/tags')]
    public function getPostWithTags(int $id)
    {
        $post = \App\Models\Post::getById($id);
        if (!$post) {
            return ['error' => 'Post not found'];
        }
        // ✅ 触发 BelongsToMany 关联加载
        $tags = $post->tags;

        return [
            'post' => $post->toArray(),
            'tags' => $tags->toArray()
        ];
    }

    #[Get('/postsa/tags')]
    public function getAllPostWithTags()
    {
        $posts = \App\Models\User::with('posts')->limit(10)->get();

        $result = [];
        // 4. 变量名更正为 $posts 和 $post，更符合逻辑
        foreach ($posts as $post) {
            // 5. 访问 post 的属性和已经预加载好的 tags 关联
            $result[] = [
                'post_title' => $post->name,
                'tags' => $post->posts
            ];
        }

        return $result;
    }

    /**
     * 测试 #[Validate] 注解
     * 使用新的 Product 模型
     *
     * @param Product $product
     * @return Product
     */
    #[Post('/products')] //
    public function createProduct(#[RequestBody] Product $product): Product //
    {
        // 1. 验证已在 Router 中自动完成
        //    如果能执行到这里，说明 $product 已经通过了所有验证规则
        //    并且已经被 fill() 方法填充了数据

        // 2. (可选) 保存到数据库
        // $product->save();

        // 3. 将验证和填充后的模型实例返回
        return $product;
    }
    /**
     * 测试 DTO 接受参数
     * 使用新的 Department Dto
     *
     * @param Department $department
     * @return Department
     */
    #[Post('/dto_demo')]
    public function dtoDemo(Department $department){
        // 支持接受Dto 参数接受
        // 自动json 序列化
        return $department;
    }
}