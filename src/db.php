<?php
declare(strict_types=1);

/** Retorna a conexão PDO (singleton) e garante que as tabelas existam. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }

    $cfg = config();

    if ($cfg['driver'] === 'mysql') {
        $m = $cfg['mysql'];
        try {
            // Cria o banco automaticamente se ainda não existir
            $tmp = new PDO("mysql:host={$m['host']};port={$m['port']};charset=utf8mb4", $m['user'], $m['pass']);
            $tmp->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $nomeBanco = str_replace('`', '', $m['dbname']);
            $tmp->exec("CREATE DATABASE IF NOT EXISTS `$nomeBanco` CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci");
            $tmp = null;

            $dsn = "mysql:host={$m['host']};port={$m['port']};dbname={$m['dbname']};charset=utf8mb4";
            $pdo = new PDO($dsn, $m['user'], $m['pass']);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('<h2>Erro ao conectar no MySQL</h2><p>Verifique se o MySQL está iniciado no XAMPP e se host/usuário/senha '
                . 'em <code>config.php</code> estão corretos.</p><pre>' . htmlspecialchars($ex->getMessage()) . '</pre>');
        }
    } else {
        $dir = dirname($cfg['sqlite_path']);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $cfg['sqlite_path']);
        $pdo->exec('PRAGMA foreign_keys = ON');
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    criar_schema($pdo, $cfg['driver']);
    popular_exemplos($pdo);

    return $pdo;
}

function criar_schema(PDO $pdo, string $driver): void
{
    $pk = $driver === 'mysql'
        ? 'INT AUTO_INCREMENT PRIMARY KEY'
        : 'INTEGER PRIMARY KEY AUTOINCREMENT';
    $engine = $driver === 'mysql' ? 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4' : '';

    // Valores monetários são guardados em CENTAVOS (inteiros) para evitar erros de ponto flutuante.
    $pdo->exec("CREATE TABLE IF NOT EXISTS produtos (
        id $pk,
        nome VARCHAR(100) NOT NULL,
        categoria VARCHAR(50) NOT NULL DEFAULT '',
        descricao VARCHAR(255) NOT NULL DEFAULT '',
        preco_centavos INT NOT NULL,
        estoque INT NOT NULL DEFAULT 0
    ) $engine");

    $pdo->exec("CREATE TABLE IF NOT EXISTS vendas (
        id $pk,
        data_hora VARCHAR(19) NOT NULL,
        subtotal_centavos INT NOT NULL,
        desconto_centavos INT NOT NULL DEFAULT 0,
        total_centavos INT NOT NULL
    ) $engine");

    // O nome/preço do produto são copiados para o item (histórico preservado
    // mesmo que o produto seja editado ou excluído depois).
    $pdo->exec("CREATE TABLE IF NOT EXISTS itens_venda (
        id $pk,
        venda_id INT NOT NULL,
        produto_id INT NULL,
        produto_nome VARCHAR(100) NOT NULL,
        preco_unit_centavos INT NOT NULL,
        quantidade INT NOT NULL,
        FOREIGN KEY (venda_id) REFERENCES vendas(id) ON DELETE CASCADE
    ) $engine");
}

/** Insere produtos de exemplo na primeira execução. */
function popular_exemplos(PDO $pdo): void
{
    if ((int) $pdo->query('SELECT COUNT(*) FROM produtos')->fetchColumn() > 0) {
        return;
    }

    $exemplos = [
        ['Arroz Tipo 1 5kg',        'Mercearia',  'Pacote de 5kg',            2890, 40],
        ['Feijão Carioca 1kg',      'Mercearia',  'Pacote de 1kg',             799, 60],
        ['Óleo de Soja 900ml',      'Mercearia',  'Garrafa 900ml',             749, 50],
        ['Leite Integral 1L',       'Laticínios', 'Caixa 1 litro',             569, 100],
        ['Queijo Mussarela 500g',   'Laticínios', 'Fatiado, 500g',            2490, 25],
        ['Café Torrado 500g',       'Mercearia',  'Café moído tradicional',   1990, 35],
        ['Carne Bovina Patinho 1kg','Açougue',    'Bandeja 1kg',              4990, 20],
        ['Peito de Frango 1kg',     'Açougue',    'Congelado, 1kg',           1690, 30],
        ['Sabão em Pó 1,6kg',       'Limpeza',    'Caixa 1,6kg',              2290, 30],
        ['Detergente 500ml',        'Limpeza',    'Neutro 500ml',              249, 80],
        ['Refrigerante Cola 2L',    'Bebidas',    'Garrafa PET 2L',            999, 70],
        ['Cerveja Lata 350ml',      'Bebidas',    'Lata 350ml',                449, 120],
    ];

    $st = $pdo->prepare('INSERT INTO produtos (nome, categoria, descricao, preco_centavos, estoque)
                         VALUES (?, ?, ?, ?, ?)');
    foreach ($exemplos as $e) {
        $st->execute($e);
    }
}
