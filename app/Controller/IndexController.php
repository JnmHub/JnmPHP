<?php
namespace App\Controller;

use App\Attribute\Get;
use App\Attribute\Middleware;
use App\Attribute\PathVariable;
use App\Attribute\Post;
use App\Attribute\RoutePrefix;
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
    public function getInfo(Request $request,#[PathVariable('aid')]int $id = null): string // 结合参数注入，可以接收 ?id=1 这样的参数
    {
        // ... 查询用户信息的逻辑
        return "Fetching user info for ID: " . ($id ?? 'all');
    }

    #[Post('/create')]
    public function createUser(): string
    {
//        $name = JSON['name'];
        return "User  created!";
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


}