<?php
namespace App\Controller;

use App\Core\Attribute\Get;
use App\Core\Attribute\PathVariable;
use App\Core\Attribute\Post;
use App\Core\Attribute\RoutePrefix;
use App\Model\User;
#[RoutePrefix('/')]
class IndexController extends BaseController
{
    #[Get('/index'),Get('/')]
    public function index($aaa = null)
    {
        // 查找ID为1的用户
        $user = User::find(1);
        $data = [
            'title' => 'User Info'.$aaa,
            'message' => 'Hello, ' . ($user ? $user->name : 'Guest')
        ];
        $this->view('index/index', $data);
    }
    #[Get('/info')]
    public function getInfo($id = null) // 结合参数注入，可以接收 ?id=1 这样的参数
    {
        // ... 查询用户信息的逻辑
        echo "Fetching user info for ID: " . ($id ?? 'all');
    }

    #[Post('/create')]
    public function createUser()
    {
        $name = JSON['name'];
        echo "User '{$name}' created!";
    }

    #[Get('/user/{uid}/order/{oid}')]
    public function getOrder(
        #[PathVariable('uid', '用户ID不能为空')] string $userId,
        #[PathVariable('oid', '订单ID缺失')] string $orderId
    ): void {
        echo "用户ID：{$userId}，订单ID：{$orderId}";
    }

    #[Get('/user/{id}')]
    public function getUser(
        #[PathVariable('id', '用户ID未提供')] string $id,
        string $extra = '默认信息'
    ): void {
        echo "用户：{$id}，额外：{$extra}";
    }

    #[Get('/user/{uid}/order/{oid}')]
    public function showOrder(int $uid, int $oid)
    {
        echo "用户ID={$uid}, 订单ID={$oid}";
    }
}