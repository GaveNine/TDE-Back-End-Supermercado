<?php
/** CRUD de Produtos — LISTAR (Read) e EXCLUIR (Delete). */
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$db = db();

// ---------- DELETE ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
    csrf_validar();
    $id = (int) ($_POST['id'] ?? 0);

    $st = $db->prepare('DELETE FROM produtos WHERE id = ?');
    $st->execute([$id]);

    if ($st->rowCount() > 0) {
        unset($_SESSION['carrinho'][$id]);
        flash('ok', 'Produto excluído com sucesso.');
    } else {
        flash('erro', 'Produto não encontrado.');
    }
    redirect('produtos.php');
}

// ---------- READ (lista + busca) ----------
$busca = trim((string) ($_GET['q'] ?? ''));
if ($busca !== '') {
    $st = $db->prepare('SELECT * FROM produtos WHERE nome LIKE ? OR categoria LIKE ? ORDER BY nome');
    $st->execute(["%$busca%", "%$busca%"]);
} else {
    $st = $db->query('SELECT * FROM produtos ORDER BY nome');
}
$produtos = $st->fetchAll();

$titulo = 'Produtos';
$base   = '../';
require __DIR__ . '/../src/header.php';
?>
<div class="barra">
    <h1>Produtos e Estoque</h1>
    <a class="btn btn-primario" href="produto_form.php">+ Novo produto</a>
</div>

<form method="get" class="busca">
    <input type="search" name="q" value="<?= e($busca) ?>" placeholder="Buscar por nome ou categoria…">
    <button class="btn">Buscar</button>
    <?php if ($busca !== ''): ?><a class="btn" href="produtos.php">Limpar</a><?php endif; ?>
</form>

<table class="tabela">
    <thead>
        <tr><th>#</th><th>Nome</th><th>Categoria</th><th class="dir">Preço</th><th class="dir">Estoque</th><th class="dir">Ações</th></tr>
    </thead>
    <tbody>
    <?php foreach ($produtos as $p): ?>
        <tr>
            <td><?= (int) $p['id'] ?></td>
            <td><?= e($p['nome']) ?><br><small><?= e($p['descricao']) ?></small></td>
            <td><?= e($p['categoria']) ?></td>
            <td class="dir"><?= moeda((int) $p['preco_centavos']) ?></td>
            <td class="dir">
                <?php $est = (int) $p['estoque']; ?>
                <span class="tag <?= $est === 0 ? 'tag-erro' : ($est <= 10 ? 'tag-aviso' : 'tag-ok') ?>"><?= $est ?></span>
            </td>
            <td class="dir acoes">
                <a class="btn btn-pequeno" href="produto_form.php?id=<?= (int) $p['id'] ?>">Editar</a>
                <form method="post" onsubmit="return confirm('Excluir &quot;<?= e(addslashes($p['nome'])) ?>&quot;?');">
                    <?= csrf_campo() ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                    <button class="btn btn-pequeno btn-perigo">Excluir</button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (!$produtos): ?>
        <tr><td colspan="6" class="vazio">Nenhum produto encontrado.</td></tr>
    <?php endif; ?>
    </tbody>
</table>
<?php require __DIR__ . '/../src/footer.php'; ?>
