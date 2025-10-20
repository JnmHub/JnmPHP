<!DOCTYPE html>
<html lang="zh_CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'JnmPHP 项目')</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; padding: 20px; line-height: 1.6; }
        .container { max-width: 800px; margin: 0 auto; background: #f9f9f9; border: 1px solid #ddd; border-radius: 8px; }
        .content { padding: 20px; }
        header, footer { padding: 20px; background: #eee; }
        footer { margin-top: 20px; text-align: center; font-size: 0.9em; }
    </style>
</head>
<body>
<div class="container">
    <header>
        <h1>我的 JnmPHP 网站 (已启用 Blade)</h1>
    </header>

    <main class="content">
        @yield('content')
    </main>

    <footer>
        <p>&copy; {{ date('Y') }} JnmPHP 版权所有</p>
    </footer>
</div>
</body>
</html>