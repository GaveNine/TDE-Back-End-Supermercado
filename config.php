<?php
/**
 * Configuração do sistema.
 *
 * driver: 'sqlite' (padrão, não precisa instalar nada) ou 'mysql' (XAMPP/WAMP).
 * Para usar MySQL: crie o banco "supermercado" e troque o driver abaixo.
 * As tabelas são criadas automaticamente na primeira execução.
 */
return [
    'driver' => 'mysql',

    'sqlite_path' => __DIR__ . '/data/supermercado.sqlite',

    'mysql' => [
        'host'   => 'localhost',
        'port'   => 3306,
        'dbname' => 'supermercado',
        'user'   => 'root',
        'pass'   => '',
    ],

    // Regra de negócio: desconto para compras acima do valor mínimo
    'desconto_minimo_reais' => 200.00,   // compras ESTRITAMENTE maiores que isso
    'desconto_percentual'   => 5,        // 5%
];
