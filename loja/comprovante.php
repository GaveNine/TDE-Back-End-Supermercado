<?php
/** Comprovante da compra finalizada. */
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$db = db();
$id = (int) ($_GET['id'] ?? 0);

$st = $db->prepare('SELECT * FROM vendas WHERE id = ?');
$st->execute([$id]);
$venda = $st->fetch();
if (!$venda) {
    flash('erro', 'Venda não encontrada.');
    redirect('index.php');
}

$st = $db->prepare('SELECT * FROM itens_venda WHERE venda_id = ? ORDER BY id');
$st->execute([$id]);
$itens = $st->fetchAll();

$titulo = 'Compra #' . $id;
$base   = '../';
require __DIR__ . '/../src/header.php';
?>
<div class="alerta alerta-ok">✅ Compra realizada com sucesso! O estoque foi atualizado.</div>

<h1>Comprovante — Venda #<?= (int) $venda['id'] ?></h1>
<p><?= e($venda['data_hora']) ?></p>

<table class="tabela">
    <thead><tr><th>Produto</th><th class="dir">Preço un.</th><th class="dir">Qtd</th><th class="dir">Subtotal</th></tr></thead>
    <tbody>
    <?php foreach ($itens as $i): ?>
        <tr>
            <td><?= e($i['produto_nome']) ?></td>
            <td class="dir"><?= moeda((int) $i['preco_unit_centavos']) ?></td>
            <td class="dir"><?= (int) $i['quantidade'] ?></td>
            <td class="dir"><?= moeda((int) $i['preco_unit_centavos'] * (int) $i['quantidade']) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="resumo">
    <div><span>Subtotal</span><span><?= moeda((int) $venda['subtotal_centavos']) ?></span></div>
    <?php if ((int) $venda['desconto_centavos'] > 0): ?>
        <div class="desconto"><span>Desconto (<?= (int) config()['desconto_percentual'] ?>%)</span><span>− <?= moeda((int) $venda['desconto_centavos']) ?></span></div>
    <?php endif; ?>
    <div class="total"><span>Total pago</span><span><?= moeda((int) $venda['total_centavos']) ?></span></div>
    <div class="acoes-form"><a class="btn btn-primario" href="index.php">Nova compra</a></div>
</div>
<?php require __DIR__ . '/../src/footer.php'; ?>
