<?php
declare(strict_types=1);

function config(): array
{
    static $cfg = null;
    return $cfg ??= require __DIR__ . '/../config.php';
}

/** Escapa HTML (proteção contra XSS). */
function e(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
}

/** 12345 -> "R$ 123,45" */
function moeda(int $centavos): string
{
    return 'R$ ' . number_format($centavos / 100, 2, ',', '.');
}

/** Emoji ilustrativo do produto (a loja não usa fotos), pelo nome ou, se não achar, pela categoria. */
function emoji_produto(string $nome, string $categoria): string
{
    $porNome = [
        'arroz' => '🍚', 'feij' => '🥫', 'óleo' => '🛢️', 'oleo' => '🛢️', 'leite' => '🥛', 'queijo' => '🧀',
        'café' => '☕', 'cafe' => '☕', 'carne' => '🥩', 'frango' => '🍗', 'sabão' => '🧺', 'sabao' => '🧺',
        'detergente' => '🧴', 'refrigerante' => '🥤', 'cerveja' => '🍺', 'pão' => '🍞', 'pao' => '🍞',
        'ovo' => '🥚', 'maçã' => '🍎', 'banana' => '🍌', 'açúcar' => '🍬', 'macarr' => '🍝', 'suco' => '🧃',
        'água' => '💧', 'agua' => '💧', 'sal ' => '🧂', 'chocolate' => '🍫', 'biscoito' => '🍪',
    ];
    $n = mb_strtolower($nome) . ' ';
    foreach ($porNome as $trecho => $emoji) {
        if (str_contains($n, $trecho)) {
            return $emoji;
        }
    }
    $porCategoria = [
        'mercearia' => '🛒', 'laticínios' => '🧀', 'laticinios' => '🧀', 'açougue' => '🥩', 'acougue' => '🥩',
        'limpeza' => '🧼', 'bebidas' => '🥤', 'hortifruti' => '🥬', 'padaria' => '🥖', 'congelados' => '🧊',
    ];
    return $porCategoria[mb_strtolower($categoria)] ?? '📦';
}

/** "12,50" | "1.234,56" | "R$ 9.9" -> centavos (int) ou null se inválido. */
function parse_moeda(string $txt): ?int
{
    $t = trim(str_replace(['R$', ' '], '', $txt));
    if ($t === '') {
        return null;
    }
    if (str_contains($t, ',')) {
        $t = str_replace('.', '', $t);   // pontos são separador de milhar
        $t = str_replace(',', '.', $t);  // vírgula é o decimal
    }
    if (!preg_match('/^\d+(\.\d{1,2})?$/', $t)) {
        return null;
    }
    return (int) round(((float) $t) * 100);
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/* ---------- Mensagens flash ---------- */

function flash(string $tipo, string $msg): void
{
    $_SESSION['flash'][] = ['tipo' => $tipo, 'msg' => $msg];
}

function flash_pegar(): array
{
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- CSRF ---------- */

function csrf_token(): string
{
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(16));
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_validar(): void
{
    $ok = isset($_POST['csrf']) && hash_equals(csrf_token(), (string) $_POST['csrf']);
    if (!$ok) {
        http_response_code(400);
        exit('Requisição inválida (token CSRF).');
    }
}

/* ---------- REGRA DE NEGÓCIO ---------- */

/**
 * Calcula os totais de uma compra.
 * Regra: compras com subtotal MAIOR que R$ 200,00 ganham 5% de desconto.
 * (Exatamente R$ 200,00 NÃO ganha desconto.)
 *
 * @return array{subtotal:int, desconto:int, total:int, aplicou_desconto:bool}
 */
function calcular_totais(int $subtotalCentavos): array
{
    $cfg    = config();
    $minimo = (int) round($cfg['desconto_minimo_reais'] * 100);

    $aplicou  = $subtotalCentavos > $minimo;
    $desconto = $aplicou ? (int) round($subtotalCentavos * $cfg['desconto_percentual'] / 100) : 0;

    return [
        'subtotal'         => $subtotalCentavos,
        'desconto'         => $desconto,
        'total'            => $subtotalCentavos - $desconto,
        'aplicou_desconto' => $aplicou,
    ];
}

/* ---------- Carrinho (guardado na sessão: [produto_id => quantidade]) ---------- */

function carrinho(): array
{
    return $_SESSION['carrinho'] ?? [];
}

function carrinho_qtd_itens(): int
{
    return array_sum(carrinho());
}

/**
 * Carrega os produtos do carrinho com dados atuais do banco.
 * @return array{itens: array, subtotal:int}
 */
function carrinho_detalhado(): array
{
    $itens = [];
    $subtotal = 0;
    $cart = carrinho();

    if ($cart) {
        $ids = array_map('intval', array_keys($cart));
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $st  = db()->prepare("SELECT * FROM produtos WHERE id IN ($in)");
        $st->execute($ids);
        $produtos = [];
        foreach ($st->fetchAll() as $p) {
            $produtos[(int) $p['id']] = $p;
        }

        foreach ($cart as $id => $qtd) {
            if (!isset($produtos[(int) $id])) {
                unset($_SESSION['carrinho'][$id]); // produto foi excluído
                continue;
            }
            $p = $produtos[(int) $id];
            $linha = (int) $p['preco_centavos'] * $qtd;
            $itens[] = [
                'produto' => $p,
                'qtd'     => $qtd,
                'linha'   => $linha,
                'sem_estoque' => $qtd > (int) $p['estoque'],
            ];
            $subtotal += $linha;
        }
    }

    return ['itens' => $itens, 'subtotal' => $subtotal];
}
