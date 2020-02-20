<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'C:/Users/Ole/GitHub/grav-skeleton-scholar/user/plugins/translator/blueprints.yaml',
    'modified' => 1571689760,
    'data' => [
        'name' => 'Translator',
        'version' => '1.0.1',
        'description' => 'A grav CMS plugin for easily allowing translators to translate your website without needing admin access.',
        'icon' => 'globe',
        'author' => [
            'name' => 'ricardo',
            'email' => 'ricardo@urbansquid.london'
        ],
        'homepage' => 'https://github.com/ricardo118/grav-plugin-translator',
        'demo' => 'http://translator.urbansquid.london',
        'keywords' => 'grav, plugin, etc',
        'bugs' => 'https://github.com/ricardo118/grav-plugin-translator/issues',
        'docs' => 'https://github.com/ricardo118/grav-plugin-translator/blob/develop/README.md',
        'license' => 'MIT',
        'form' => [
            'validation' => 'strict',
            'fields' => [
                'enabled' => [
                    'type' => 'toggle',
                    'label' => 'PLUGIN_ADMIN.PLUGIN_STATUS',
                    'highlight' => 1,
                    'default' => 0,
                    'options' => [
                        1 => 'PLUGIN_ADMIN.ENABLED',
                        0 => 'PLUGIN_ADMIN.DISABLED'
                    ],
                    'validate' => [
                        'type' => 'bool'
                    ]
                ],
                'base_route' => [
                    'type' => 'text',
                    'label' => 'Base Route',
                    'default' => '/translator',
                    'placeholder' => '/translator',
                    'description' => 'The base route of the Plugin (needs to be unique)'
                ],
                'fields' => [
                    'type' => 'array',
                    'value_only' => true,
                    'label' => 'Fields to Translate',
                    'description' => 'Which blueprint field types the plugin allows for translation, every other field is ignored.'
                ],
                'spacer_style' => [
                    'type' => 'spacer',
                    'underline' => true
                ],
                'style_section' => [
                    'type' => 'section',
                    'title' => 'STYLE',
                    'fields' => [
                        'style.logo' => [
                            'type' => 'file',
                            'label' => 'Logo',
                            'description' => 'Used in the translators area and the emails',
                            'destination' => 'plugins://translator/images',
                            'multiple' => false
                        ],
                        'style.color' => [
                            'type' => 'colorpicker',
                            'default' => '#39CCCC',
                            'label' => 'Pick a primary color for the plugin',
                            'description' => 'Other color suggestions: #39CCCC #984B43 #94618E #4484CE #ff6300'
                        ]
                    ]
                ],
                'spacer' => [
                    'type' => 'spacer',
                    'underline' => true
                ],
                'slack' => [
                    'type' => 'section',
                    'title' => 'NOTIFICATIONS',
                    'text' => 'We recommend the use of Slack notifications, as it is more secure and nicer. However, email notifications are the default. To enable Slack you must follow the instructions provided on the README of the plugin. Linked above on the plugin details section.',
                    'fields' => [
                        'slack.enabled' => [
                            'type' => 'toggle',
                            'label' => 'Enable Slack Notification',
                            'highlight' => 1,
                            'default' => 0,
                            'markdown' => 1,
                            'description' => '**WARNING**: Don\'t enable slack without a valid webhook. You will need to follow the [README](https://github.com/ricardo118/grav-plugin-translator/blob/develop/README.md) instructions to create your own app and enable the webhook.',
                            'options' => [
                                1 => 'Slack',
                                0 => 'Email'
                            ],
                            'validate' => [
                                'type' => 'bool'
                            ]
                        ],
                        'slack.webhook' => [
                            'type' => 'text',
                            'label' => 'Webhook',
                            'placeholder' => 'https://hooks.slack.com/services/...'
                        ],
                        'slack.channel' => [
                            'type' => 'text',
                            'label' => 'Slack Channel',
                            'size' => 'small',
                            'placeholder' => '#general',
                            'description' => 'Use # before the channel name'
                        ]
                    ]
                ]
            ]
        ]
    ]
];
