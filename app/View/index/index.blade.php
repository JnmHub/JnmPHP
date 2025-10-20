{{-- 1. 继承我们刚刚创建的 app 布局 --}}
@extends('layouts.app')

{{-- 2. 定义 title 部分 --}}
@section('title', '欢迎来到首页')

{{-- 3. 定义 content 部分 --}}
@section('content')
<p>你好, {{ $name }}!</p>
<p>欢迎使用 JnmPHP 框架，现在已成功接入 Blade 模板引擎。</p>

<h3>产品列表 (来自控制器):</h3>
<ul>
    {{-- 4. 使用 @foreach 循环 --}}
    @foreach($products as $product)
    <li>{{ $product['name'] }} - ￥{{ $product['price'] }}</li>
    @endforeach
</ul>

{{-- 5. 测试翻译功能 (它会使用你 AppServiceProvider 中配置的翻译服务) --}}
<p>
    翻译测试: @lang('validation.accepted', ['attribute' => '条款'])
</p>
@endsection