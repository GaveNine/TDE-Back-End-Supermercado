<?php
/** Histórico de vendas. */
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$db = db();
$vendas = $db->query('SELECT * FROM vendas ORDER BY id DESC')->fetchAll();

$itensStmt = $db->prepare('SELECT * FROM itens_venda WHERE venda_id = ? ORDER BY id');

$titulo = 'Vendas';
$base   = '../';
require __DIR__ . '/../src/header.php';
?>
<h1>Vendas realizadas</h1>

<?php if (!$vendas): ?>
    <p class="vazio">Nenhuma venda realizada ainda. <a href="../loja/index.php">Ir para a loja</a></p>
<?php endif; ?>

<?php foreach ($vendas as $v): ?>
    <?php $itensStmt->execute([$v['id']]); $itens = $itensStmt->fetchAll(); ?>
    <details class="venda">
        <summary>
            <strong>Venda #<?= (int) $v['id'] ?></strong>
            <span><?= e($v['data_hora']) ?></span>
            <span class="total"><?= moeda((int) $v['total_centavos']) ?></span>
            <?php if ((int) $v['desconto_centavos'] > 0): ?><span class="tag tag-ok">com desconto</span><?php endif; ?>
        </summary>
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
            <tfoot>
                <tr><td colspan="3" class="dir">Subtotal</td><td class="dir"><?= moeda((int) $v['subtotal_centavos']) ?></td></tr>
                <tr><td colspan="3" class="dir">Desconto</td><td class="dir">− <?= moeda((int) $v['desconto_centavos']) ?></td></tr>
                <tr class="forte"><td colspan="3" class="dir">Total</td><td class="dir"><?= moeda((int) $v['total_centavos']) ?></td></tr>
            </tfoot>
        </table>
    </details>
<?php endforeach; ?>
<?php require __DIR__ . '/../src/footer.php'; ?>
