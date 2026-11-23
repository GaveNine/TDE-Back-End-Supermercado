<?php
/** Interface do cliente — vitrine (banners + catálogo de produtos). */
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$db = db();

// ---------- Adicionar ao carrinho ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $id  = (int) ($_POST['id'] ?? 0);
    $qtd = max(1, (int) ($_POST['qtd'] ?? 1));

    $st = $db->prepare('SELECT * FROM produtos WHERE id = ?');
    $st->execute([$id]);
    $p = $st->fetch();

    if (!$p) {
        flash('erro', 'Produto não encontrado.');
    } else {
        $novo = ($_SESSION['carrinho'][$id] ?? 0) + $qtd;
        if ($novo > (int) $p['estoque']) {
            flash('erro', 'Estoque insuficiente de "' . $p['nome'] . '". Disponível: ' . (int) $p['estoque'] . '.');
        } else {
            $_SESSION['carrinho'][$id] = $novo;
            flash('ok', '"' . $p['nome'] . '" adicionado ao carrinho.');
        }
    }
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    redirect('index.php' . ($qs !== '' ? '?' . $qs : '') . '#produtos');
}

// ---------- Listagem (busca + categoria) ----------
$busca = trim((string) ($_GET['q'] ?? ''));
$cat   = trim((string) ($_GET['cat'] ?? ''));

$where  = [];
$params = [];
if ($busca !== '') {
    $where[]  = '(nome LIKE ? OR categoria LIKE ? OR descricao LIKE ?)';
    array_push($params, "%$busca%", "%$busca%", "%$busca%");
}
if ($cat !== '') {
    $where[]  = 'categoria = ?';
    $params[] = $cat;
}
$sql = 'SELECT * FROM produtos' . ($where ? ' WHERE ' . implode(' AND ', $where) : '') . ' ORDER BY categoria, nome';
$st = $db->prepare($sql);
$st->execute($params);
$produtos = $st->fetchAll();

$cfg    = config();
$minimo = moeda((int) round($cfg['desconto_minimo_reais'] * 100));
$perc   = (int) $cfg['desconto_percentual'];
$filtrando = ($busca !== '' || $cat !== '');

$tituloSecao = $cat !== '' ? $cat : ($busca !== '' ? 'Resultados para “' . $busca . '”' : 'Todos os produtos');

$titulo    = 'Loja';
$base      = '../';
$semPainel = true;
require __DIR__ . '/../src/header.php';
?>

<?php if (!$filtrando): ?>
<!-- Faixa de aviso -->
<div class="faixa-aviso" id="ofertas">
    <span>🎉 Compras acima de <b><?= $minimo ?></b> ganham <b><?= $perc ?>% de desconto</b> automático no carrinho</span>
</div>

<!-- Carrossel de banners -->
<section class="carrossel" id="carrossel" aria-label="Destaques">
    <div class="slides">
        <div class="slide slide-laranja ativo">
            <div class="slide-txt">
                <small>Oferta especial</small>
                <h2>Compre mais,<br><em>pague menos</em></h2>
                <p>Passou de <?= $minimo ?> no carrinho? Você ganha <b><?= $perc ?>% de desconto</b>.</p>
                <a class="btn-laranja" href="#produtos">Aproveitar agora</a>
            </div>
            <div class="slide-arte" aria-hidden="true"><span>🛒</span><span>🥖</span><span>🍎</span><span>🥛</span></div>
        </div>

        <div class="slide slide-verde">
            <div class="slide-txt">
                <small>Sempre fresquinho</small>
                <h2>Estoque atualizado<br><em>em tempo real</em></h2>
                <p>A cada compra finalizada, o estoque dá baixa automaticamente.</p>
                <a class="btn-laranja" href="#produtos">Ver produtos</a>
            </div>
            <div class="slide-arte" aria-hidden="true"><span>🥩</span><span>🧀</span><span>🍚</span><span>☕</span></div>
        </div>

        <div class="slide slide-escuro">
            <div class="slide-txt">
                <small>Bebidas geladas</small>
                <h2>Monte seu carrinho<br><em>e simule a compra</em></h2>
                <p>Escolha as quantidades, veja o desconto e finalize em poucos cliques.</p>
                <a class="btn-laranja" href="carrinho.php">Ir para o carrinho</a>
            </div>
            <div class="slide-arte" aria-hidden="true"><span>🥤</span><span>🍺</span><span>🧃</span><span>💧</span></div>
        </div>
    </div>
    <button type="button" class="seta seta-ant" aria-label="Banner anterior">‹</button>
    <button type="button" class="seta seta-prox" aria-label="Próximo banner">›</button>
    <div class="pontos"></div>
</section>
<?php endif; ?>

<section class="vitrine" id="produtos">
    <div class="vitrine-topo">
        <h1><?= e($tituloSecao) ?></h1>
        <span class="contagem"><?= count($produtos) ?> produto(s)</span>
        <?php if ($filtrando): ?><a class="chip" href="index.php">✕ Limpar filtro</a><?php endif; ?>
    </div>

    <div class="grade-produtos">
    <?php foreach ($produtos as $p):
        $est   = (int) $p['estoque'];
        $c     = (int) $p['preco_centavos'];
        $reais = intdiv($c, 100);
        $cents = str_pad((string) ($c % 100), 2, '0', STR_PAD_LEFT);
    ?>
        <article class="card <?= $est === 0 ? 'card-esgotado' : '' ?>">
            <div class="card-img" aria-hidden="true">
                <span><?= emoji_produto($p['nome'], $p['categoria']) ?></span>
                <?php if ($est === 0): ?><em class="selo selo-esgotado">Esgotado</em>
                <?php elseif ($est <= 10): ?><em class="selo selo-aviso">Últimas <?= $est ?> un.</em><?php endif; ?>
            </div>
            <div class="card-corpo">
                <span class="card-cat"><?= e($p['categoria'] ?: 'Geral') ?></span>
                <h3><?= e($p['nome']) ?></h3>
                <p class="card-desc"><?= e($p['descricao']) ?></p>

                <p class="card-preco"><small>R$</small> <strong><?= $reais ?></strong><sup>,<?= $cents ?></sup></p>

                <?php if ($est > 0): ?>
                <form method="post" class="card-compra">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <div class="qtd">
                        <button type="button" data-passo="-1" aria-label="Diminuir">−</button>
                        <input type="number" name="qtd" value="1" min="1" max="<?= $est ?>" aria-label="Quantidade">
                        <button type="button" data-passo="1" aria-label="Aumentar">+</button>
                    </div>
                    <button class="btn-laranja">Adicionar</button>
                </form>
                <?php else: ?>
                    <button class="btn-laranja" disabled>Indisponível</button>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>

    <?php if (!$produtos): ?>
        <p class="vazio-escuro">Nenhum produto encontrado. <a href="index.php">Ver todos</a></p>
    <?php endif; ?>
</section>
<?php require __DIR__ . '/../src/footer.php'; ?>
