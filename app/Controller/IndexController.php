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
use App\Http\Response\ViewResponse;
use App\Models\User;

#[RoutePrefix('/')]
class IndexController extends BaseController
{
    #[Get('/index'),Get('/')]
    #[Middleware("log")]
    public function index($aaa = null): ViewResponse
    {
        // 查找ID为1的用户
        $user = User::find(1);
        $data = [
            'title' => 'User Info'.$aaa,
            'message' => 'Hello, ' . ($user ? $user->name : 'Guest')
        ];
        return $this->view('index/index', $data);
    }
    #[Get('/info/{aid}')]
    public function getInfo(#[PathVariable('aid')]int $id,Request $rrr): string // 结合参数注入，可以接收 ?id=1 这样的参数
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
        // 此时，$user 对象已经根据请求的 JSON 和 $fillable 属性安全地填充了数据

        // 您可以继续处理，例如哈希密码
        // $user->password = password_hash($user->password, PASSWORD_DEFAULT);

        // 保存到数据库
        $user->save();

        // 返回创建好的用户（框架会自动转为 JSON）
        return $user;
    }

}