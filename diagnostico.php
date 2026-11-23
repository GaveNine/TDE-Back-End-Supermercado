<?php
/** Página de diagnóstico: mostra qual config está sendo usada e testa a conexão. */
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
require __DIR__ . '/src/helpers.php';

$cfg = config();
echo '<meta charset="utf-8"><body style="font-family:system-ui;max-width:760px;margin:30px auto;line-height:1.6">';
echo '<h2>Diagnóstico</h2>';

function linha(string $rotulo, string $valor, ?bool $ok = null): void {
    $cor = $ok === null ? '#000' : ($ok ? '#0d652d' : '#c5221f');
    echo "<div><b>$rotulo:</b> <span style='color:$cor'>" . htmlspecialchars($valor) . '</span></div>';
}

linha('Pasta do projeto', __DIR__);
linha('Arquivo de config lido', realpath(__DIR__ . '/config.php') ?: 'não encontrado');
linha('Driver no config', (string) $cfg['driver'], $cfg['driver'] === 'mysql');
linha('Versão do PHP', PHP_VERSION);
linha('Extensão pdo_mysql', extension_loaded('pdo_mysql') ? 'ativada' : 'DESATIVADA', extension_loaded('pdo_mysql'));
linha('Extensão pdo_sqlite', extension_loaded('pdo_sqlite') ? 'ativada' : 'desativada');

if ($cfg['driver'] === 'mysql') {
    $m = $cfg['mysql'];
    linha('Host / porta', $m['host'] . ' : ' . $m['port']);
    linha('Banco (dbname)', (string) $m['dbname']);
    linha('Usuário', (string) $m['user']);
    try {
        $pdo = new PDO("mysql:host={$m['host']};port={$m['port']};charset=utf8mb4", $m['user'], $m['pass']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        linha('Conexão com o servidor MySQL', 'OK', true);

        $bancos = $pdo->query('SHOW DATABASES')->fetchAll(PDO::FETCH_COLUMN);
        linha('Bancos existentes', implode(', ', $bancos));
        linha('Banco do config existe?', in_array($m['dbname'], $bancos, true) ? 'sim' : 'não (será criado ao abrir o sistema)',
              in_array($m['dbname'], $bancos, true));

        if (in_array($m['dbname'], $bancos, true)) {
            $pdo->exec('USE `' . str_replace('`', '', $m['dbname']) . '`');
            $tabelas = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
            linha('Tabelas no banco', $tabelas ? implode(', ', $tabelas) : '(nenhuma — abra index.php para criar)');
        }
    } catch (Throwable $ex) {
        linha('ERRO', $ex->getMessage(), false);
    }
} else {
    linha('Banco SQLite em', (string) $cfg['sqlite_path']);
    echo '<p style="color:#c5221f">O driver está como <b>sqlite</b>. Troque para <code>\'mysql\'</code> no config.php.</p>';
}
