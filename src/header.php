<?php
/**
 * Variáveis esperadas: $titulo (string), $base (prefixo de caminho: '' ou '../')
 * Opcional: $semPainel = true (conteúdo direto no fundo escuro, usado na vitrine da loja)
 */
$base      = $base ?? '';
$semPainel = $semPainel ?? false;
$catAtiva  = (string) ($_GET['cat'] ?? '');
$qBusca    = $semPainel ? (string) ($_GET['q'] ?? '') : '';

$categoriasMenu = [];
try {
    $categoriasMenu = db()->query("SELECT DISTINCT categoria FROM produtos WHERE categoria <> '' ORDER BY categoria")
                          ->fetchAll(PDO::FETCH_COLUMN);
} catch (Throwable $ex) {
    // sem categorias no menu se o banco ainda não respondeu
}

$qtdCarrinho = carrinho_qtd_itens();
$pagina      = basename($_SERVER['SCRIPT_NAME'] ?? '');
$naLoja      = str_contains($_SERVER['SCRIPT_NAME'] ?? '', '/loja/');
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo ?? 'Supermercado') ?> · Supermercado</title>
    <link rel="stylesheet" href="<?= $base ?>assets/style.css">
</head>
<body>

<!-- Barra superior -->
<div class="barra-topo">
    <div class="wrap barra-topo-in">
        <a href="<?= $base ?>loja/index.php">Nossa loja</a>
        <a href="<?= $base ?>loja/index.php#ofertas">Ofertas</a>
        <a href="<?= $base ?>loja/carrinho.php">Meu carrinho</a>
        <a href="<?= $base ?>admin/produtos.php">Gerenciar produtos</a>
        <a href="<?= $base ?>admin/vendas.php">Histórico de vendas</a>
    </div>
</div>

<!-- Cabeçalho principal -->
<header class="cabecalho">
    <div class="wrap cabecalho-in">
        <a class="logo" href="<?= $base ?>loja/index.php" aria-label="Supermercado - início">
            <span class="logo-marca">S</span>
            <span class="logo-nome">Super<b>mercado</b></span>
        </a>

        <form class="busca-pilula" method="get" action="<?= $base ?>loja/index.php" role="search">
            <input type="search" name="q" value="<?= e($qBusca) ?>" placeholder="O que você procura?" aria-label="Buscar produtos">
            <button type="submit" aria-label="Buscar">
                <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M20 20l-4-4"/></svg>
            </button>
        </form>

        <div class="cabecalho-acoes">
            <div class="bloco-entrega">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round"><path d="M3 10l2-6h14l2 6M4 10v10h16V10M3 10h18M9 20v-6h6v6"/></svg>
                <div>
                    <span>Retire na loja</span>
                    <strong>Simulação de compra</strong>
                </div>
            </div>

            <a class="bloco-conta" href="<?= $base ?>admin/produtos.php">
                <svg viewBox="0 0 24 24" width="34" height="34" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
                <div>
                    <strong>Bem-vindo</strong>
                    <span>Área administrativa</span>
                </div>
            </a>

            <a class="carrinho-icone" href="<?= $base ?>loja/carrinho.php" aria-label="Carrinho, <?= $qtdCarrinho ?> item(ns)">
                <svg viewBox="0 0 24 24" width="38" height="38" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M2 3h3l2.6 12.2a2 2 0 002 1.6h8.2a2 2 0 002-1.5L21 8H6"/><circle cx="10" cy="20.5" r="1.2"/><circle cx="18" cy="20.5" r="1.2"/></svg>
                <span class="badge"><?= $qtdCarrinho ?></span>
            </a>
        </div>
    </div>
</header>

<!-- Categorias -->
<nav class="faixa-categorias" aria-label="Categorias">
    <div class="wrap faixa-in">
        <a class="chip <?= ($naLoja && $pagina === 'index.php' && $catAtiva === '' && !isset($_GET['q'])) ? 'chip-ativo' : '' ?>"
           href="<?= $base ?>loja/index.php">Todos</a>
        <?php foreach ($categoriasMenu as $c): ?>
            <a class="chip <?= $catAtiva === $c ? 'chip-ativo' : '' ?>"
               href="<?= $base ?>loja/index.php?cat=<?= urlencode($c) ?>"><?= e($c) ?></a>
        <?php endforeach; ?>
        <a class="chip chip-destaque" href="<?= $base ?>loja/index.php#ofertas">🔥 5% de desconto</a>
    </div>
</nav>

<main class="wrap conteudo <?= $semPainel ? '' : 'painel' ?>">
<?php foreach (flash_pegar() as $f): ?>
    <div class="alerta alerta-<?= e($f['tipo']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
