<?php
/** Interface do cliente — carrinho, simulação de compra e finalização (baixa de estoque). */
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();
    $acao = $_POST['acao'] ?? '';
    $id   = (int) ($_POST['id'] ?? 0);

    switch ($acao) {
        case 'atualizar':
            $qtd = (int) ($_POST['qtd'] ?? 0);
            if ($qtd <= 0) {
                unset($_SESSION['carrinho'][$id]);
            } else {
                $st = $db->prepare('SELECT estoque FROM produtos WHERE id = ?');
                $st->execute([$id]);
                $estoque = $st->fetchColumn();
                if ($estoque === false) {
                    unset($_SESSION['carrinho'][$id]);
                } elseif ($qtd > (int) $estoque) {
                    $_SESSION['carrinho'][$id] = (int) $estoque;
                    flash('erro', "Quantidade ajustada ao estoque disponível ($estoque).");
                } else {
                    $_SESSION['carrinho'][$id] = $qtd;
                }
            }
            redirect('carrinho.php');

        case 'remover':
            unset($_SESSION['carrinho'][$id]);
            redirect('carrinho.php');

        case 'limpar':
            unset($_SESSION['carrinho']);
            redirect('carrinho.php');

        case 'finalizar':
            finalizar_compra($db);
            // (finalizar_compra sempre redireciona)
    }
}

/**
 * Finaliza a compra dentro de uma transação:
 *  1) dá baixa no estoque (só se houver quantidade suficiente);
 *  2) calcula subtotal, desconto (regra dos 5% acima de R$ 200) e total;
 *  3) grava a venda e seus itens.
 * Se qualquer item não tiver estoque, TUDO é desfeito (rollback).
 */
function finalizar_compra(PDO $db): never
{
    $cart = carrinho();
    if (!$cart) {
        flash('erro', 'Seu carrinho está vazio.');
        redirect('carrinho.php');
    }

    try {
        $db->beginTransaction();

        $subtotal = 0;
        $linhas   = [];
        $busca    = $db->prepare('SELECT * FROM produtos WHERE id = ?');
        $baixa    = $db->prepare('UPDATE produtos SET estoque = estoque - ? WHERE id = ? AND estoque >= ?');

        foreach ($cart as $id => $qtd) {
            $busca->execute([(int) $id]);
            $p = $busca->fetch();
            if (!$p) {
                throw new RuntimeException('Um dos produtos do carrinho não existe mais.');
            }

            // Baixa condicional: só atualiza se ainda houver estoque suficiente.
            $baixa->execute([$qtd, (int) $id, $qtd]);
            if ($baixa->rowCount() === 0) {
                throw new RuntimeException(
                    'Estoque insuficiente para "' . $p['nome'] . '" (disponível: ' . (int) $p['estoque'] . ').'
                );
            }

            $preco = (int) $p['preco_centavos'];
            $subtotal += $preco * $qtd;
            $linhas[] = [(int) $id, $p['nome'], $preco, $qtd];
        }

        $t = calcular_totais($subtotal);

        $db->prepare('INSERT INTO vendas (data_hora, subtotal_centavos, desconto_centavos, total_centavos)
                      VALUES (?, ?, ?, ?)')
           ->execute([date('Y-m-d H:i:s'), $t['subtotal'], $t['desconto'], $t['total']]);
        $vendaId = (int) $db->lastInsertId();

        $ins = $db->prepare('INSERT INTO itens_venda (venda_id, produto_id, produto_nome, preco_unit_centavos, quantidade)
                             VALUES (?, ?, ?, ?, ?)');
        foreach ($linhas as [$pid, $nome, $preco, $qtd]) {
            $ins->execute([$vendaId, $pid, $nome, $preco, $qtd]);
        }

        $db->commit();
    } catch (Throwable $ex) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        flash('erro', $ex instanceof RuntimeException ? $ex->getMessage() : 'Erro ao finalizar a compra.');
        redirect('carrinho.php');
    }

    unset($_SESSION['carrinho']);
    redirect('comprovante.php?id=' . $vendaId);
}

// ---------- Exibição do carrinho ----------
$dados  = carrinho_detalhado();
$itens  = $dados['itens'];
$t      = calcular_totais($dados['subtotal']);
$cfg    = config();
$minimo = (int) round($cfg['desconto_minimo_reais'] * 100);
$temProblemaEstoque = (bool) array_filter($itens, fn($i) => $i['sem_estoque']);

$titulo = 'Carrinho';
$base   = '../';
require __DIR__ . '/../src/header.php';
?>
<h1>Seu carrinho</h1>

<?php if (!$itens): ?>
    <p class="vazio">Seu carrinho está vazio. <a href="index.php">Continuar comprando</a></p>
<?php else: ?>
    <table class="tabela">
        <thead><tr><th>Produto</th><th class="dir">Preço un.</th><th>Qtd</th><th class="dir">Subtotal</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($itens as $i): $p = $i['produto']; ?>
            <tr>
                <td>
                    <?= e($p['nome']) ?>
                    <?php if ($i['sem_estoque']): ?><br><span class="tag tag-erro">Estoque disponível: <?= (int) $p['estoque'] ?></span><?php endif; ?>
                </td>
                <td class="dir"><?= moeda((int) $p['preco_centavos']) ?></td>
                <td>
                    <form method="post" class="add">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="acao" value="atualizar">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <input type="number" name="qtd" value="<?= (int) $i['qtd'] ?>" min="0" max="<?= (int) $p['estoque'] ?>" aria-label="Quantidade">
                        <button class="btn btn-pequeno">Atualizar</button>
                    </form>
                </td>
                <td class="dir"><?= moeda($i['linha']) ?></td>
                <td class="dir">
                    <form method="post">
                        <?= csrf_campo() ?>
                        <input type="hidden" name="acao" value="remover">
                        <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                        <button class="btn btn-pequeno btn-perigo">Remover</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

    <div class="resumo">
        <div><span>Subtotal</span><span><?= moeda($t['subtotal']) ?></span></div>
        <?php if ($t['aplicou_desconto']): ?>
            <div class="desconto"><span>Desconto (<?= (int) $cfg['desconto_percentual'] ?>%)</span><span>− <?= moeda($t['desconto']) ?></span></div>
        <?php else: ?>
            <div class="dica">Faltam <?= moeda($minimo - $t['subtotal'] + 1) ?> para ganhar <?= (int) $cfg['desconto_percentual'] ?>% de desconto
                (acima de <?= moeda($minimo) ?>).</div>
        <?php endif; ?>
        <div class="total"><span>Total</span><span><?= moeda($t['total']) ?></span></div>

        <div class="acoes-form">
            <form method="post">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="finalizar">
                <button class="btn btn-primario btn-grande" <?= $temProblemaEstoque ? 'disabled' : '' ?>>Finalizar compra</button>
            </form>
            <form method="post">
                <?= csrf_campo() ?>
                <input type="hidden" name="acao" value="limpar">
                <button class="btn">Esvaziar carrinho</button>
            </form>
            <a class="btn" href="index.php">Continuar comprando</a>
        </div>
    </div>
<?php endif; ?>
<?php require __DIR__ . '/../src/footer.php'; ?>
