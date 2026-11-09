<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$spec = json_decode(file_get_contents(base_path('docs/openapi.json')), true, 512, JSON_THROW_ON_ERROR);
$errors = [];
$operations = 0;
foreach ($spec['paths'] as $path => $methods) {
    foreach ($methods as $method => $operation) {
        $operations++;
        $uri = '/api'.preg_replace('/\{[^}]+\}/', '1', $path);
        $request = \Illuminate\Http\Request::create($uri, strtoupper($method));
        $found = false;
        foreach (app('router')->getRoutes() as $route) {
            if ($route->matches($request)) {
                $found = true;
                break;
            }
        }
        if (!$found) $errors[] = strtoupper($method).' '.$path;
    }
}
if ($errors) throw new RuntimeException('OpenAPI có endpoint không khớp: '.implode(', ', $errors));
echo "OpenAPI hợp lệ JSON, $operations operations khớp route thực tế.\n";
