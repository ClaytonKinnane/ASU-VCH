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
                'css/operation-result-modal.css',
            ],
        ],
    ],
];
