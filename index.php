<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>欢迎访问 - 新闻调度器服务</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f3f4f6;
        }
        .card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
        }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen bg-gray-100">
    <div class="text-center">
        <h1 class="text-4xl font-bold text-gray-800 mb-8">新闻调度器服务面板</h1>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
            <a href="management/" class="card bg-white p-10 rounded-xl shadow-lg flex flex-col items-center justify-center">
                <i class="fas fa-desktop text-5xl text-blue-500 mb-4"></i>
                <h2 class="text-2xl font-semibold text-gray-700">公开展示面板</h2>
                <p class="text-gray-500 mt-2">查看程序版本和下载文件。</p>
            </a>
            <a href="admin/" class="card bg-white p-10 rounded-xl shadow-lg flex flex-col items-center justify-center">
                <i class="fas fa-user-shield text-5xl text-green-500 mb-4"></i>
                <h2 class="text-2xl font-semibold text-gray-700">管理员后台</h2>
                <p class="text-gray-500 mt-2">管理文件、查看日志和设备状态。</p>
            </a>
        </div>
    </div>
</body>
</html>
