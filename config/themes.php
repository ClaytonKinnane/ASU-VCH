<?php

declare(strict_types=1);

return [
    'default' => 'asu-blue',
    'themes' => [
        'asu-blue' => [
            'name' => 'АСУ Синяя',
            'description' => 'Тёмная сине-бирюзовая тема АСУ-ВЧ.',
            'appearance' => 'dark',
            'preview_colors' => ['#131e30', '#18acea', '#17a58b'],
            'required_assets' => [
                'css/theme.css',
                'css/auth.css',
                'css/account.css',
                'css/users.css',
                'css/theme-management.css',
                'css/directories.css',
                'css/organization.css',
                'css/operation-result-modal.css',
            ],
        ],
        'asu-light-blue' => [
            'name' => 'АСУ Светлая синяя',
            'description' => 'Светлая минималистичная тема с синими контурами.',
            'appearance' => 'light',
            'preview_colors' => ['#ffffff', '#086ad5', '#054f9e'],
            'required_assets' => [
                'css/theme.css',
                'css/auth.css',
                'css/account.css',
                'css/users.css',
                'css/theme-management.css',
                'css/directories.css',
                'css/organization.css',
                'css/operation-result-modal.css',
            ],
        ],
        'asu-evgeniya-rostova' => [
            'name' => 'Евгения Ростова',
            'description' => 'Светлая розово-лиловая тема с сердечками, воздушными шариками и мягкими игрушками.',
            'appearance' => 'light',
            'preview_colors' => ['#fff7fb', '#c12a70', '#9a6bc4'],
            'required_assets' => [
                'css/theme.css',
                'css/auth.css',
                'css/account.css',
                'css/users.css',
                'css/theme-management.css',
                'css/directories.css',
                'css/organization.css',
                'css/operation-result-modal.css',
                'img/hearts-pattern.svg',
                'img/balloons.svg',
                'img/teddy-bear.svg',
                'img/plush-bunny.svg',
            ],
        ],
    ],
];
