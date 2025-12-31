<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="火锅店管理后台系统">
    <title>火锅店管理后台</title>
    @php
        $isDev = !file_exists(public_path('build/.vite/manifest.json'));
        $vitePort = env('VITE_PORT', 5173);
        $viteClient = '@vite/client';
    @endphp
    @if($isDev)
        <!-- 开发模式：使用 Vite 开发服务器 -->
        <script type="module" src="http://localhost:{{ $vitePort }}/{{ $viteClient }}"></script>
        <script type="module" src="http://localhost:{{ $vitePort }}/resources/js/main.ts"></script>
    @else
        <!-- 生产模式：使用构建后的文件 -->
        @php
            $manifest = json_decode(file_get_contents(public_path('build/.vite/manifest.json')), true);
            $mainJs = $manifest['resources/js/main.ts']['file'] ?? 'assets/main.js';
            $mainCss = $manifest['resources/js/main.ts']['css'][0] ?? null;
        @endphp
        @if($mainCss)
        <link rel="stylesheet" href="{{ asset('build/' . $mainCss) }}">
        @endif
        <script type="module" src="{{ asset('build/' . $mainJs) }}"></script>
    @endif
</head>
<body>
    <div id="app"></div>
</body>
</html>

