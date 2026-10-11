<?php
/**
 * Gerador de QR Code em PHP puro (modo byte, correcao de erro nivel M,
 * versoes 1 a 10 — suficiente para URLs de ate 216 caracteres).
 *
 * Sem dependencia de extensao, biblioteca ou servico externo: o QR do
 * link publico e desenhado pelo proprio servidor, em SVG.
 *
 * Referencia: ISO/IEC 18004.
 */
final class QrCode
{
    /** [versao => [codewords de correcao por bloco, blocos g1, dados g1, blocos g2, dados g2]] — nivel M. */
    private const BLOCOS_M = [
        1  => [10, 1, 16, 0, 0],
        2  => [16, 1, 28, 0, 0],
        3  => [26, 1, 44, 0, 0],
        4  => [18, 2, 32, 0, 0],
        5  => [24, 2, 43, 0, 0],
        6  => [16, 4, 27, 0, 0],
        7  => [18, 4, 31, 0, 0],
        8  => [22, 2, 38, 2, 39],
        9  => [22, 3, 36, 2, 37],
        10 => [26, 4, 43, 1, 44],
    ];

    /** Posicoes dos padroes de alinhamento por versao. */
    private const ALINHAMENTO = [
        1 => [], 2 => [6, 18], 3 => [6, 22], 4 => [6, 26], 5 => [6, 30],
        6 => [6, 34], 7 => [6, 22, 38], 8 => [6, 24, 42], 9 => [6, 26, 46], 10 => [6, 28, 50],
    ];

    /** Informacao de versao (18 bits), obrigatoria a partir da versao 7. */
    private const INFO_VERSAO = [7 => 0x07C94, 8 => 0x085BC, 9 => 0x09A99, 10 => 0x0A4D3];

    private static array $exp = [];
    private static array $log = [];

    private int $versao;
    private int $tam;
    /** @var array<int,array<int,int|null>> null = ainda livre */
    private array $matriz = [];
    /** @var array<int,array<int,bool>> true = modulo de funcao (nao recebe dados) */
    private array $reservado = [];

    // -----------------------------------------------------------------
    // API publica
    // -----------------------------------------------------------------

    /**
     * Devolve o QR Code como SVG pronto para embutir no HTML.
     *
     * @param string $texto    Conteudo a codificar (ate 216 bytes).
     * @param int    $tamanho  Lado do SVG em pixels.
     * @param int    $margem   Margem em modulos (o padrao da norma e 4).
     */
    public static function svg(string $texto, int $tamanho = 160, int $margem = 4): string
    {
        try {
            $matriz = (new self($texto))->construir();
        } catch (Throwable $e) {
            return '';
        }

        $n = count($matriz);
        $total = $n + $margem * 2;

        $caminho = '';
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($matriz[$y][$x]) {
                    $caminho .= 'M' . ($x + $margem) . ',' . ($y + $margem) . 'h1v1h-1z';
                }
            }
        }

        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%1$d" height="%1$d" viewBox="0 0 %2$d %2$d" '
            . 'shape-rendering="crispEdges" role="img" aria-label="QR Code do link">'
            . '<rect width="%2$d" height="%2$d" fill="#ffffff"/>'
            . '<path d="%3$s" fill="#000000"/></svg>',
            $tamanho,
            $total,
            $caminho
        );
    }

    // -----------------------------------------------------------------
    // Construcao
    // -----------------------------------------------------------------

    private function __construct(private string $texto)
    {
        $this->versao = $this->escolherVersao(strlen($texto));
        $this->tam = 17 + 4 * $this->versao;
    }

    private function escolherVersao(int $bytes): int
    {
        foreach (self::BLOCOS_M as $v => [$ec, $b1, $d1, $b2, $d2]) {
            $capacidade = $b1 * $d1 + $b2 * $d2;
            $indicador = $v < 10 ? 1 : 2;          // bytes do contador de caracteres
            if ($bytes + $indicador + 1 <= $capacidade) {
                return $v;
            }
        }
        throw new RuntimeException('Texto longo demais para um QR Code versao 10.');
    }

    /** @return array<int,array<int,int>> matriz de 0/1 */
    private function construir(): array
    {
        $dados = $this->montarCodewords();

        $this->iniciarMatriz();
        $this->desenharPadroes();
        $this->colocarDados($dados);

        // Escolhe a mascara com menor penalidade.
        $melhor = 0;
        $menorPenalidade = PHP_INT_MAX;
        $base = $this->matriz;

        for ($m = 0; $m < 8; $m++) {
            $this->matriz = $base;
            $this->aplicarMascara($m);
            $this->gravarFormato($m);
            $p = $this->penalidade();
            if ($p < $menorPenalidade) {
                $menorPenalidade = $p;
                $melhor = $m;
            }
        }

        $this->matriz = $base;
        $this->aplicarMascara($melhor);
        $this->gravarFormato($melhor);
        $this->gravarVersao();

        $saida = [];
        for ($y = 0; $y < $this->tam; $y++) {
            for ($x = 0; $x < $this->tam; $x++) {
                $saida[$y][$x] = (int) ($this->matriz[$y][$x] ?? 0);
            }
        }
        return $saida;
    }

    // -----------------------------------------------------------------
    // Codificacao dos dados + correcao de erro
    // -----------------------------------------------------------------

    /** @return int[] sequencia final de codewords (dados intercalados + correcao) */
    private function montarCodewords(): array
    {
        [$ecPorBloco, $b1, $d1, $b2, $d2] = self::BLOCOS_M[$this->versao];
        $totalDados = $b1 * $d1 + $b2 * $d2;

        // --- fluxo de bits ---------------------------------------------
        $bits = '0100';                                       // modo byte
        $bitsContador = $this->versao < 10 ? 8 : 16;
        $bits .= str_pad(decbin(strlen($this->texto)), $bitsContador, '0', STR_PAD_LEFT);
        foreach (str_split($this->texto) as $c) {
            $bits .= str_pad(decbin(ord($c)), 8, '0', STR_PAD_LEFT);
        }

        // terminador (ate 4 zeros) + alinhamento em byte
        $bits .= str_repeat('0', min(4, $totalDados * 8 - strlen($bits)));
        if (strlen($bits) % 8 !== 0) {
            $bits .= str_repeat('0', 8 - strlen($bits) % 8);
        }

        $codewords = [];
        foreach (str_split($bits, 8) as $byte) {
            $codewords[] = bindec($byte);
        }
        // bytes de preenchimento alternados, conforme a norma
        $preenchimento = [0xEC, 0x11];
        $i = 0;
        while (count($codewords) < $totalDados) {
            $codewords[] = $preenchimento[$i++ % 2];
        }

        // --- divisao em blocos + correcao de erro ----------------------
        $blocosDados = [];
        $blocosEc = [];
        $pos = 0;

        foreach ([[$b1, $d1], [$b2, $d2]] as [$qtd, $tamanho]) {
            for ($b = 0; $b < $qtd; $b++) {
                $bloco = array_slice($codewords, $pos, $tamanho);
                $pos += $tamanho;
                $blocosDados[] = $bloco;
                $blocosEc[] = self::reedSolomon($bloco, $ecPorBloco);
            }
        }

        // --- intercalacao ----------------------------------------------
        $saida = [];
        $maiorDados = max(array_map('count', $blocosDados));
        for ($i = 0; $i < $maiorDados; $i++) {
            foreach ($blocosDados as $bloco) {
                if (isset($bloco[$i])) {
                    $saida[] = $bloco[$i];
                }
            }
        }
        for ($i = 0; $i < $ecPorBloco; $i++) {
            foreach ($blocosEc as $bloco) {
                if (isset($bloco[$i])) {
                    $saida[] = $bloco[$i];
                }
            }
        }

        return $saida;
    }

    /** Inicializa as tabelas de log/antilog do corpo de Galois GF(256). */
    private static function iniciarGalois(): void
    {
        if (self::$exp) {
            return;
        }
        $x = 1;
        for ($i = 0; $i < 256; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;                                  // polinomio primitivo
            }
        }
        for ($i = 256; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
    }

    /**
     * Calcula os codewords de correcao de erro (Reed-Solomon).
     *
     * @param int[] $dados
     * @return int[]
     */
    private static function reedSolomon(array $dados, int $quantidade): array
    {
        self::iniciarGalois();

        // polinomio gerador
        $gerador = [1];
        for ($i = 0; $i < $quantidade; $i++) {
            $novo = array_fill(0, count($gerador) + 1, 0);
            foreach ($gerador as $j => $coef) {
                $novo[$j] ^= $coef;
                $novo[$j + 1] ^= self::multiplicar($coef, self::$exp[$i]);
            }
            $gerador = $novo;
        }

        $resto = array_merge($dados, array_fill(0, $quantidade, 0));
        for ($i = 0; $i < count($dados); $i++) {
            $coef = $resto[$i];
            if ($coef === 0) {
                continue;
            }
            foreach ($gerador as $j => $g) {
                $resto[$i + $j] ^= self::multiplicar($g, $coef);
            }
        }

        return array_slice($resto, count($dados), $quantidade);
    }

    private static function multiplicar(int $a, int $b): int
    {
        if ($a === 0 || $b === 0) {
            return 0;
        }
        return self::$exp[self::$log[$a] + self::$log[$b]];
    }

    // -----------------------------------------------------------------
    // Matriz: padroes de funcao
    // -----------------------------------------------------------------

    private function iniciarMatriz(): void
    {
        for ($y = 0; $y < $this->tam; $y++) {
            for ($x = 0; $x < $this->tam; $x++) {
                $this->matriz[$y][$x] = null;
                $this->reservado[$y][$x] = false;
            }
        }
    }

    private function marcar(int $x, int $y, int $valor): void
    {
        if ($x < 0 || $y < 0 || $x >= $this->tam || $y >= $this->tam) {
            return;
        }
        $this->matriz[$y][$x] = $valor;
        $this->reservado[$y][$x] = true;
    }

    private function desenharPadroes(): void
    {
        $n = $this->tam;

        // Localizadores (cantos) + separadores
        foreach ([[0, 0], [$n - 7, 0], [0, $n - 7]] as [$ox, $oy]) {
            for ($y = -1; $y <= 7; $y++) {
                for ($x = -1; $x <= 7; $x++) {
                    $dentro = $x >= 0 && $x <= 6 && $y >= 0 && $y <= 6;
                    $preto = $dentro && (
                        $x === 0 || $x === 6 || $y === 0 || $y === 6
                        || ($x >= 2 && $x <= 4 && $y >= 2 && $y <= 4)
                    );
                    $this->marcar($ox + $x, $oy + $y, $preto ? 1 : 0);
                }
            }
        }

        // Padroes de tempo
        for ($i = 8; $i < $n - 8; $i++) {
            $v = ($i % 2 === 0) ? 1 : 0;
            $this->marcar($i, 6, $v);
            $this->marcar(6, $i, $v);
        }

        // Padroes de alinhamento
        $centros = self::ALINHAMENTO[$this->versao];
        foreach ($centros as $cy) {
            foreach ($centros as $cx) {
                // nao sobrepoe os localizadores
                if (($cx === 6 && $cy === 6)
                    || ($cx === 6 && $cy === $n - 7)
                    || ($cx === $n - 7 && $cy === 6)) {
                    continue;
                }
                for ($y = -2; $y <= 2; $y++) {
                    for ($x = -2; $x <= 2; $x++) {
                        $preto = (abs($x) === 2 || abs($y) === 2 || ($x === 0 && $y === 0));
                        $this->marcar($cx + $x, $cy + $y, $preto ? 1 : 0);
                    }
                }
            }
        }

        // Modulo escuro fixo
        $this->marcar(8, $n - 8, 1);

        // Reserva as areas de formato
        for ($i = 0; $i < 9; $i++) {
            if ($this->matriz[$i][8] === null) { $this->marcar(8, $i, 0); }
            if ($this->matriz[8][$i] === null) { $this->marcar($i, 8, 0); }
        }
        for ($i = 0; $i < 8; $i++) {
            if ($this->matriz[$n - 1 - $i][8] === null) { $this->marcar(8, $n - 1 - $i, 0); }
            if ($this->matriz[8][$n - 1 - $i] === null) { $this->marcar($n - 1 - $i, 8, 0); }
        }

        // Reserva as areas de versao (a partir da versao 7)
        if ($this->versao >= 7) {
            for ($i = 0; $i < 6; $i++) {
                for ($j = 0; $j < 3; $j++) {
                    $this->marcar($i, $n - 11 + $j, 0);
                    $this->marcar($n - 11 + $j, $i, 0);
                }
            }
        }
    }

    // -----------------------------------------------------------------
    // Dados na matriz
    // -----------------------------------------------------------------

    /** @param int[] $codewords */
    private function colocarDados(array $codewords): void
    {
        $bits = '';
        foreach ($codewords as $cw) {
            $bits .= str_pad(decbin($cw), 8, '0', STR_PAD_LEFT);
        }

        $n = $this->tam;
        $i = 0;
        $subindo = true;

        for ($coluna = $n - 1; $coluna > 0; $coluna -= 2) {
            if ($coluna === 6) {
                $coluna--;                                    // pula a coluna do padrao de tempo
            }
            for ($passo = 0; $passo < $n; $passo++) {
                $y = $subindo ? $n - 1 - $passo : $passo;
                foreach ([$coluna, $coluna - 1] as $x) {
                    if ($this->reservado[$y][$x]) {
                        continue;
                    }
                    $this->matriz[$y][$x] = isset($bits[$i]) ? (int) $bits[$i] : 0;
                    $i++;
                }
            }
            $subindo = !$subindo;
        }
    }

    private function aplicarMascara(int $mascara): void
    {
        for ($y = 0; $y < $this->tam; $y++) {
            for ($x = 0; $x < $this->tam; $x++) {
                if ($this->reservado[$y][$x]) {
                    continue;
                }
                $inverter = match ($mascara) {
                    0 => ($y + $x) % 2 === 0,
                    1 => $y % 2 === 0,
                    2 => $x % 3 === 0,
                    3 => ($y + $x) % 3 === 0,
                    4 => (intdiv($y, 2) + intdiv($x, 3)) % 2 === 0,
                    5 => (($y * $x) % 2) + (($y * $x) % 3) === 0,
                    6 => ((($y * $x) % 2) + (($y * $x) % 3)) % 2 === 0,
                    default => ((($y + $x) % 2) + (($y * $x) % 3)) % 2 === 0,
                };
                if ($inverter) {
                    $this->matriz[$y][$x] ^= 1;
                }
            }
        }
    }

    private function gravarFormato(int $mascara): void
    {
        // nivel M = 00
        $dados = (0b00 << 3) | $mascara;
        $resto = $dados << 10;
        for ($i = 4; $i >= 0; $i--) {
            if ($resto & (1 << ($i + 10))) {
                $resto ^= 0b10100110111 << $i;
            }
        }
        $formato = (($dados << 10) | $resto) ^ 0b101010000010010;

        $n = $this->tam;
        for ($i = 0; $i < 15; $i++) {
            $bit = ($formato >> $i) & 1;

            // copia 1: coluna 8 descendo, depois linha 8 indo para a esquerda
            if ($i < 6) {
                $this->matriz[$i][8] = $bit;
            } elseif ($i === 6) {
                $this->matriz[7][8] = $bit;
            } elseif ($i === 7) {
                $this->matriz[8][8] = $bit;
            } elseif ($i === 8) {
                $this->matriz[8][7] = $bit;
            } else {
                $this->matriz[8][14 - $i] = $bit;
            }

            // copia 2 (redundante): linha 8 a direita, depois coluna 8 embaixo
            if ($i < 8) {
                $this->matriz[8][$n - 1 - $i] = $bit;
            } else {
                $this->matriz[$n - 15 + $i][8] = $bit;
            }
        }

        $this->matriz[$n - 8][8] = 1;                          // modulo escuro
    }

    private function gravarVersao(): void
    {
        if ($this->versao < 7) {
            return;
        }
        $info = self::INFO_VERSAO[$this->versao];
        $n = $this->tam;

        for ($i = 0; $i < 18; $i++) {
            $bit = ($info >> $i) & 1;
            $linha = intdiv($i, 3);
            $coluna = $i % 3;
            $this->matriz[$n - 11 + $coluna][$linha] = $bit;
            $this->matriz[$linha][$n - 11 + $coluna] = $bit;
        }
    }

    // -----------------------------------------------------------------
    // Penalidade das mascaras
    // -----------------------------------------------------------------

    private function penalidade(): int
    {
        $n = $this->tam;
        $total = 0;
        $escuros = 0;

        // Regra 1: sequencias de 5 ou mais modulos iguais
        for ($i = 0; $i < $n; $i++) {
            foreach ([true, false] as $horizontal) {
                $anterior = -1;
                $seq = 0;
                for ($j = 0; $j < $n; $j++) {
                    $v = $horizontal ? $this->matriz[$i][$j] : $this->matriz[$j][$i];
                    if ($v === $anterior) {
                        $seq++;
                    } else {
                        if ($seq >= 5) {
                            $total += 3 + ($seq - 5);
                        }
                        $anterior = $v;
                        $seq = 1;
                    }
                }
                if ($seq >= 5) {
                    $total += 3 + ($seq - 5);
                }
            }
        }

        // Regra 2: blocos 2x2 da mesma cor / Regra 4: contagem de escuros
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                if ($this->matriz[$y][$x]) {
                    $escuros++;
                }
                if ($y + 1 < $n && $x + 1 < $n) {
                    $v = $this->matriz[$y][$x];
                    if ($v === $this->matriz[$y][$x + 1]
                        && $v === $this->matriz[$y + 1][$x]
                        && $v === $this->matriz[$y + 1][$x + 1]) {
                        $total += 3;
                    }
                }
            }
        }

        // Regra 3: padroes 1:1:3:1:1 parecidos com localizador
        $padroes = [
            [1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0],
            [0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1],
        ];
        for ($y = 0; $y < $n; $y++) {
            for ($x = 0; $x < $n; $x++) {
                foreach ($padroes as $p) {
                    if ($x + 11 <= $n) {
                        $bate = true;
                        for ($k = 0; $k < 11; $k++) {
                            if ($this->matriz[$y][$x + $k] !== $p[$k]) { $bate = false; break; }
                        }
                        if ($bate) { $total += 40; }
                    }
                    if ($y + 11 <= $n) {
                        $bate = true;
                        for ($k = 0; $k < 11; $k++) {
                            if ($this->matriz[$y + $k][$x] !== $p[$k]) { $bate = false; break; }
                        }
                        if ($bate) { $total += 40; }
                    }
                }
            }
        }

        // Regra 4: desvio da proporcao ideal de 50% escuros
        $proporcao = (int) (abs($escuros * 100 / ($n * $n) - 50) / 5);
        $total += $proporcao * 10;

        return $total;
    }
}
