<?php
/** CRUD de Produtos — CRIAR (Create) e EDITAR/ATUALIZAR (Update). */
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$db = db();
$id = (int) ($_GET['id'] ?? $_POST['id'] ?? 0);

$produto = ['nome' => '', 'categoria' => '', 'descricao' => '', 'preco' => '', 'estoque' => '0'];

// Carrega o produto existente (modo edição)
if ($id > 0) {
    $st = $db->prepare('SELECT * FROM produtos WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) {
        flash('erro', 'Produto não encontrado.');
        redirect('produtos.php');
    }
    $produto = [
        'nome'      => $row['nome'],
        'categoria' => $row['categoria'],
        'descricao' => $row['descricao'],
        'preco'     => number_format($row['preco_centavos'] / 100, 2, ',', ''),
        'estoque'   => (string) $row['estoque'],
    ];
}

$erros = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_validar();

    $produto = [
        'nome'      => trim((string) ($_POST['nome'] ?? '')),
        'categoria' => trim((string) ($_POST['categoria'] ?? '')),
        'descricao' => trim((string) ($_POST['descricao'] ?? '')),
        'preco'     => trim((string) ($_POST['preco'] ?? '')),
        'estoque'   => trim((string) ($_POST['estoque'] ?? '')),
    ];

    // ---------- Validação ----------
    if ($produto['nome'] === '' || mb_strlen($produto['nome']) > 100) {
        $erros[] = 'Informe o nome (até 100 caracteres).';
    }
    if (mb_strlen($produto['categoria']) > 50) {
        $erros[] = 'Categoria deve ter no máximo 50 caracteres.';
    }
    if (mb_strlen($produto['descricao']) > 255) {
        $erros[] = 'Descrição deve ter no máximo 255 caracteres.';
    }
    $centavos = parse_moeda($produto['preco']);
    if ($centavos === null || $centavos <= 0) {
        $erros[] = 'Informe um preço válido maior que zero (ex.: 12,50).';
    }
    if (!preg_match('/^\d+$/', $produto['estoque'])) {
        $erros[] = 'O estoque deve ser um número inteiro maior ou igual a zero.';
    }

    if (!$erros) {
        $estoque = (int) $produto['estoque'];

        if ($id > 0) {
            // ---------- UPDATE ----------
            $st = $db->prepare('UPDATE produtos
                                SET nome = ?, categoria = ?, descricao = ?, preco_centavos = ?, estoque = ?
                                WHERE id = ?');
            $st->execute([$produto['nome'], $produto['categoria'], $produto['descricao'], $centavos, $estoque, $id]);
            flash('ok', 'Produto atualizado com sucesso.');
        } else {
            // ---------- CREATE ----------
            $st = $db->prepare('INSERT INTO produtos (nome, categoria, descricao, preco_centavos, estoque)
                                VALUES (?, ?, ?, ?, ?)');
            $st->execute([$produto['nome'], $produto['categoria'], $produto['descricao'], $centavos, $estoque]);
            flash('ok', 'Produto cadastrado com sucesso.');
        }
        redirect('produtos.php');
    }
}

$titulo = $id > 0 ? 'Editar produto' : 'Novo produto';
$base   = '../';
require __DIR__ . '/../src/header.php';
?>
<h1><?= e($titulo) ?></h1>

<?php if ($erros): ?>
    <div class="alerta alerta-erro">
        <ul><?php foreach ($erros as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
    </div>
<?php endif; ?>

<form method="post" class="formulario">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <label>Nome *
        <input type="text" name="nome" maxlength="100" required value="<?= e($produto['nome']) ?>">
    </label>
    <label>Categoria
        <input type="text" name="categoria" maxlength="50" list="categorias" value="<?= e($produto['categoria']) ?>">
        <datalist id="categorias">
            <?php foreach ($db->query("SELECT DISTINCT categoria FROM produtos WHERE categoria <> '' ORDER BY categoria") as $c): ?>
                <option value="<?= e($c['categoria']) ?>">
            <?php endforeach; ?>
        </datalist>
    </label>
    <label>Descrição
        <input type="text" name="descricao" maxlength="255" value="<?= e($produto['descricao']) ?>">
    </label>
    <div class="linha">
        <label>Preço (R$) *
            <input type="text" name="preco" inputmode="decimal" required placeholder="0,00" value="<?= e($produto['preco']) ?>">
        </label>
        <label>Estoque (unidades) *
            <input type="number" name="estoque" min="0" step="1" required value="<?= e($produto['estoque']) ?>">
        </label>
    </div>

    <div class="acoes-form">
        <button class="btn btn-primario"><?= $id > 0 ? 'Salvar alterações' : 'Cadastrar' ?></button>
        <a class="btn" href="produtos.php">Cancelar</a>
    </div>
</form>
<?php require __DIR__ . '/../src/footer.php'; ?>
