<?php

function deploy_detect_package_manager($dir)
{
    if (file_exists($dir . '/pnpm-lock.yaml')) {
        return ['install' => 'pnpm install --frozen-lockfile', 'run' => 'pnpm'];
    }

    if (file_exists($dir . '/yarn.lock')) {
        return ['install' => 'yarn install --frozen-lockfile', 'run' => 'yarn'];
    }

    if (file_exists($dir . '/package-lock.json')) {
        return ['install' => 'npm ci', 'run' => 'npm run'];
    }

    return ['install' => 'npm install', 'run' => 'npm run'];
}

function deploy_detect_node_version($dir, $pkg)
{
    if (file_exists($dir . '/.nvmrc')) {
        $v = trim(file_get_contents($dir . '/.nvmrc'));
        if (preg_match('/(\d+)/', $v, $m)) {
            return $m[1];
        }
    }

    if (isset($pkg['engines']['node']) && preg_match('/(\d+)/', $pkg['engines']['node'], $m)) {
        return $m[1];
    }

    return '22';
}

function deploy_detect($codespaceDir)
{
    $pkgFile = $codespaceDir . '/package.json';

    if (!file_exists($pkgFile)) {
        return [
            'framework' => 'static',
            'install_cmd' => '',
            'build_cmd' => '',
            'output_dir' => '.',
            'runtime' => 'static',
            'start_cmd' => '',
            'node_version' => '22',
        ];
    }

    $pkg = json_decode(file_get_contents($pkgFile), true) ?: [];
    $deps = array_merge($pkg['dependencies'] ?? [], $pkg['devDependencies'] ?? []);
    $scripts = $pkg['scripts'] ?? [];
    $pm = deploy_detect_package_manager($codespaceDir);
    $node = deploy_detect_node_version($codespaceDir, $pkg);
    $hasBuild = isset($scripts['build']);

    $base = [
        'install_cmd' => $pm['install'],
        'build_cmd' => $hasBuild ? $pm['run'] . ' build' : '',
        'node_version' => $node,
    ];

    if (isset($deps['next'])) {
        return array_merge($base, [
            'framework' => 'next',
            'output_dir' => '.next',
            'runtime' => 'node',
            'start_cmd' => $pm['run'] . ' start',
        ]);
    }

    if (isset($deps['nuxt']) || isset($deps['nuxt3'])) {
        return array_merge($base, [
            'framework' => 'nuxt',
            'output_dir' => '.output',
            'runtime' => 'node',
            'start_cmd' => 'node .output/server/index.mjs',
        ]);
    }

    if (isset($deps['@angular/core'])) {
        return array_merge($base, [
            'framework' => 'angular',
            'output_dir' => 'dist',
            'runtime' => 'static',
            'start_cmd' => '',
        ]);
    }

    if (isset($deps['@sveltejs/kit'])) {
        return array_merge($base, [
            'framework' => 'sveltekit',
            'output_dir' => 'build',
            'runtime' => 'node',
            'start_cmd' => 'node build',
        ]);
    }

    if (isset($deps['vite']) || isset($deps['react']) || isset($deps['vue']) || isset($deps['svelte'])) {
        return array_merge($base, [
            'framework' => 'vite',
            'output_dir' => 'dist',
            'runtime' => 'static',
            'start_cmd' => '',
        ]);
    }

    if (isset($scripts['start']) || file_exists($codespaceDir . '/server.js') || file_exists($codespaceDir . '/index.js')) {
        $start = isset($scripts['start']) ? $pm['run'] . ' start' : (file_exists($codespaceDir . '/server.js') ? 'node server.js' : 'node index.js');
        return array_merge($base, [
            'framework' => 'node',
            'output_dir' => '.',
            'runtime' => 'node',
            'start_cmd' => $start,
        ]);
    }

    return array_merge($base, [
        'framework' => 'static',
        'output_dir' => $hasBuild ? 'dist' : '.',
        'runtime' => 'static',
        'start_cmd' => '',
    ]);
}

function deploy_effective_config($codespaceId, $codespaceDir)
{
    $detected = deploy_detect($codespaceDir);
    $saved = deploy_get_config($codespaceId);
    if (!$saved) {
        return $detected;
    }
    $merged = $detected;
    foreach (['framework', 'install_cmd', 'build_cmd', 'output_dir', 'runtime', 'start_cmd', 'node_version'] as $k) {
        if (isset($saved[$k]) && $saved[$k] !== null && $saved[$k] !== '') {
            $merged[$k] = $saved[$k];
        }
    }
    return $merged;
}
