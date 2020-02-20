<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/Users/Ole/GitHub/grav-skeleton-scholar/user/plugins/static-generator/blueprints.yaml',
    'modified' => 1581455192,
    'data' => [
        'name' => 'Static Generator',
        'version' => '2.0.0-alpha.2',
        'description' => 'Static generation of Page(s) and Index.',
        'icon' => 'bolt',
        'author' => [
            'name' => 'Ole Vik',
            'email' => 'git@olevik.net'
        ],
        'homepage' => 'https://github.com/OleVik/grav-plugin-static-generator',
        'keywords' => 'grav, plugin, static, index, search, data, json, html',
        'bugs' => 'https://github.com/OleVik/grav-plugin-static-generator/issues',
        'docs' => 'https://github.com/OleVik/grav-plugin-static-generator/blob/develop/README.md',
        'license' => 'MIT',
        'form' => [
            'validation' => 'strict',
            'fields' => [
                'tabs' => [
                    'type' => 'tabs',
                    'active' => 1,
                    'fields' => [
                        'generate' => [
                            'type' => 'tab',
                            'title' => 'PLUGIN_STATIC_GENERATOR.ADMIN.GENERATE.TITLE',
                            'data-fields@' => [
                                0 => '\\Grav\\Plugin\\StaticGeneratorPlugin::getBlueprintFields',
                                1 => 'plugin://static-generator/blueprints/partials/generate.yaml'
                            ]
                        ],
                        'presets' => [
                            'type' => 'tab',
                            'title' => 'PLUGIN_STATIC_GENERATOR.ADMIN.PRESETS.TITLE',
                            'data-fields@' => [
                                0 => '\\Grav\\Plugin\\StaticGeneratorPlugin::getBlueprintFields',
                                1 => 'plugin://static-generator/blueprints/partials/presets.yaml'
                            ]
                        ],
                        'options' => [
                            'type' => 'tab',
                            'title' => 'PLUGIN_ADMIN.OPTIONS',
                            'fields' => [
                                'basic' => [
                                    'type' => 'section',
                                    'data-fields@' => [
                                        0 => '\\Grav\\Plugin\\StaticGeneratorPlugin::getBlueprintFields',
                                        1 => 'plugin://static-generator/blueprints/partials/options.yaml'
                                    ]
                                ],
                                'permissions' => [
                                    'type' => 'section',
                                    'title' => 'PLUGIN_ADMIN.PERMISSIONS',
                                    'underline' => true,
                                    'security' => [
                                        0 => 'admin.super',
                                        1 => 'admin.maintenance'
                                    ],
                                    'fields' => [
                                        'content_permissions' => [
                                            'type' => 'selectize',
                                            'label' => 'PLUGIN_STATIC_GENERATOR.ADMIN.CONTENT_PERMISSIONS',
                                            'description' => 'PLUGIN_STATIC_GENERATOR.ADMIN.DESCRIPTION.CONTENT_PERMISSIONS',
                                            'allowEmptyOption' => true,
                                            'merge_items' => true,
                                            'selectize' => [
                                                'create' => false,
                                                'data-options@' => '\\Grav\\Plugin\\StaticGeneratorPlugin::getAdminPermissionsBlueprint'
                                            ],
                                            'validate' => [
                                                'type' => 'commalist'
                                            ]
                                        ],
                                        'quick_tray_permissions' => [
                                            'type' => 'selectize',
                                            'label' => 'PLUGIN_STATIC_GENERATOR.ADMIN.QUICK_TRAY_PERMISSIONS',
                                            'description' => 'PLUGIN_STATIC_GENERATOR.ADMIN.DESCRIPTION.QUICK_TRAY_PERMISSIONS',
                                            'allowEmptyOption' => true,
                                            'merge_items' => true,
                                            'selectize' => [
                                                'create' => false,
                                                'data-options@' => '\\Grav\\Plugin\\StaticGeneratorPlugin::getAdminPermissionsBlueprint'
                                            ],
                                            'validate' => [
                                                'type' => 'commalist'
                                            ]
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ]
];
