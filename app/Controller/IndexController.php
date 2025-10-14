<?php
namespace App\Controller;

use App\Attribute\Get;
use App\Attribute\Middleware;
use App\Attribute\PathVariable;
use App\Attribute\Post;
use App\Attribute\RequestBody;
use App\Attribute\RoutePrefix;
use App\Dto\Department;
use App\Http\Request\Request;
use App\Http\Response\JsonResponse;
use App\Models\User;

#[RoutePrefix('/')]
class IndexController extends BaseController
{
    #[Get('/index'),Get('/')]
    #[Middleware("log")]
    public function index($aaa = null): User
    {
        // 查找ID为1的用户
        $user = User::find(1);
        return $user;
    }
    #[Get('/info/{aid}')]
    public function getInfo(#[PathVariable('aid')]int $id,Request $rrr): string
    {
        // ... 查询用户信息的逻辑
        var_dump($rrr);
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

        if (!$user) {
            return JsonResponse::error('User not found', 404);
        }

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
}