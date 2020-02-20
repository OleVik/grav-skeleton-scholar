<?php
return [
    '@class' => 'Grav\\Common\\File\\CompiledYamlFile',
    'filename' => 'themes://scholar/scholar.yaml',
    'modified' => 1577236833,
    'data' => [
        'enabled' => true,
        'style' => 'metal',
        'toolbar' => [
            'breadcrumbs' => true,
            'search' => true,
            'navigation' => true
        ],
        'css' => true,
        'js' => true,
        'itemize' => true,
        'linked_data' => true,
        'highlighter' => true,
        'highlighter_theme' => 'enlighter',
        'components' => [
            0 => 'blog',
            1 => 'book',
            2 => 'cv',
            3 => 'docs',
            4 => 'tufte'
        ],
        'router' => true,
        'routes' => [
            'data' => '/data',
            'embed' => '/embed',
            'search' => '/search',
            'print' => '/print'
        ],
        'api' => [
            'content' => 'Content\\Content',
            'linked_data' => [
                'default' => 'LinkedData\\PageLinkedData',
                'cv' => 'LinkedData\\CVLinkedData'
            ],
            'router' => 'Router\\Router',
            'source' => 'Source\\Source',
            'taxonomy_map' => 'TaxonomyMap\\TaxonomyMap'
        ],
        'debug' => false,
        'flexsearch' => [
            'enabled' => true,
            'index' => [
                'limit' => 10,
                'profile' => 'speed',
                'encode' => 'icase',
                'tokenize' => 'strict',
                'cache' => true,
                'async' => true
            ],
            'full' => [
                'limit' => 10,
                'profile' => 'balance',
                'encode' => 'advanced',
                'tokenize' => 'full',
                'cache' => true,
                'async' => true
            ]
        ]
    ]
];
