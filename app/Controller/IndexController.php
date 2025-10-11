<?php
namespace App\Controller;

use App\Core\Attribute\Get;
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
        // ... 创建用户的逻辑
        $name = JSON['name'];
        echo "User '{$name}' created!";
    }
}