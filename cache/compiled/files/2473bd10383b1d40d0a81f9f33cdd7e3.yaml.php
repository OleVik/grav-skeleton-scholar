<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'plugins://static-generator/static-generator.yaml',
    'modified' => 1581454751,
    'data' => [
        'enabled' => true,
        'index' => 'user://data/persist',
        'content' => 'user://data/persist',
        'content_max_length' => 100000,
        'content_permissions' => [
            0 => 'admin.super',
            1 => 'admin.maintenance'
        ],
        'admin' => true,
        'js' => true,
        'css' => true,
        'quick_tray' => true,
        'quick_tray_permissions' => [
            0 => 'admin.super',
            1 => 'admin.maintenance'
        ],
        'presets' => [
            0 => [
                'name' => 'default'
            ]
        ]
    ]
];
