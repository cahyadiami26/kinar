<?php
/**
 * Kinar Resto - Generator QR Code murni PHP (mode byte, ECC level L, versi 1-10)
 * Menghasilkan SVG. Tidak butuh ekstensi/library eksternal.
 */

function qr_svg(string $text, int $scale = 6): string {
    $data = qr_encode($text);
    $size = count($data);
    $w = $size * $scale;
    $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $w . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
    $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="#fff"/>';
    foreach ($data as $r => $row) {
        foreach ($row as $c => $v) {
            if ($v) $svg .= '<rect x="' . $c . '" y="' . $r . '" width="1" height="1" fill="#1F3D1B"/>';
        }
    }
    return $svg . '</svg>';
}

function qr_encode(string $text): array {
    // ---- tabel kapasitas (ECC L) ----
    $cap = [0,19,34,55,80,108,136,156,194,232,274];          // data codewords
    $ecc = [0,7,10,15,20,26,18,20,24,30,18];                  // ecc per blok
    $blk = [0,1,1,1,1,1,2,2,2,2,4];                           // jumlah blok
    $len = strlen($text);
    $v = 1;
    for ($v = 1; $v <= 10; $v++) {
        $ccbits = $v <= 9 ? 8 : 16;
        if ($len + 2 + (int)ceil($ccbits/8) <= $cap[$v]) break;
    }
    if ($v > 10) $v = 10;

    // ---- bitstream ----
    $bits = '';
    $bits .= '0100';                                            // mode byte
    $bits .= str_pad(decbin($len), $v <= 9 ? 8 : 16, '0', STR_PAD_LEFT);
    foreach (str_split($text) as $ch) $bits .= str_pad(decbin(ord($ch)), 8, '0', STR_PAD_LEFT);
    $capacityBits = $cap[$v] * 8;
    $bits = substr($bits . str_repeat('0', 4), 0, $capacityBits);
    while (strlen($bits) % 8) $bits .= '0';
    $pads = [0xEC, 0x11]; $pi = 0;
    while (strlen($bits) < $capacityBits) $bits .= str_pad(decbin($pads[$pi++ % 2]), 8, '0', STR_PAD_LEFT);
    $data = array_map('bindec', str_split($bits, 8));

    // ---- split blok & ECC Reed-Solomon ----
    $blocks = [];
    if ($blk[$v] === 1) {
        $blocks[] = qr_rs($data, $ecc[$v]);
    } else {
        $per = intdiv($cap[$v], $blk[$v]);
        $extra = $cap[$v] % $blk[$v];
        $off = 0;
        for ($b = 0; $b < $blk[$v]; $b++) {
            $n = $per + ($b < $extra ? 1 : 0);
            $blocks[] = qr_rs(array_slice($data, $off, $n), $ecc[$v]);
            $off += $n;
        }
    }
    // interleave
    $codewords = [];
    $maxd = 0; foreach ($blocks as $b) $maxd = max($maxd, count($b[0]));
    for ($i = 0; $i < $maxd; $i++) foreach ($blocks as $b) if (isset($b[0][$i])) $codewords[] = $b[0][$i];
    $maxe = count($blocks[0][1]);
    for ($i = 0; $i < $maxe; $i++) foreach ($blocks as $b) if (isset($b[1][$i])) $codewords[] = $b[1][$i];

    // ---- matriks ----
    $n = 17 + 4 * $v;
    $m = array_fill(0, $n, array_fill(0, $n, null));
    $set = function ($r, $c, $val) use (&$m) { $m[$r][$c] = $val; };
    $finder = function ($r0, $c0) use (&$m, $set) {
        for ($r = -1; $r <= 7; $r++) for ($c = -1; $c <= 7; $c++) {
            $rr = $r0 + $r; $cc = $c0 + $c;
            if ($rr < 0 || $cc < 0 || $rr >= count($m) || $cc >= count($m)) continue;
            if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                $v = ($r == 0 || $r == 6 || $c == 0 || $c == 6 || ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4)) ? 1 : 0;
                $set($rr, $cc, $v);
            } else $set($rr, $cc, 0);
        }
    };
    $finder(0, 0); $finder(0, $n - 7); $finder($n - 7, 0);
    for ($i = 8; $i < $n - 8; $i++) { $set(6, $i, $i % 2 == 0 ? 1 : 0); $set($i, 6, $i % 2 == 0 ? 1 : 0); }
    $align = [1=>[],2=>[6,18],3=>[6,22],4=>[6,26],5=>[6,30],6=>[6,34],7=>[6,22,38],8=>[6,24,42],9=>[6,26,46],10=>[6,28,50]][$v];
    foreach ($align as $ar) foreach ($align as $ac) {
        if (($ar == 6 && $ac == 6) || ($ar == 6 && $ac == $n - 7) || ($ar == $n - 7 && $ac == 6)) continue;
        for ($r = -2; $r <= 2; $r++) for ($c = -2; $c <= 2; $c++)
            $set($ar + $r, $ac + $c, (abs($r) == 2 || abs($c) == 2 || ($r == 0 && $c == 0)) ? 1 : 0);
    }
    $set(4 * $v + 9, 8, 1); // dark module
    // format placeholder
    $fmt = array_fill(0, $n, array_fill(0, $n, false));
    $placeFmt = function ($r, $c) use (&$fmt, $n) { if ($r >= 0 && $c >= 0 && $r < $n && $c < $n) $fmt[$r][$c] = true; };
    for ($i = 0; $i <= 5; $i++) $placeFmt(8, $i);
    $placeFmt(8, 7); $placeFmt(8, 8); $placeFmt(7, 8);
    for ($i = 9; $i <= 14; $i++) $placeFmt(14 - $i, 8);
    for ($i = 0; $i <= 7; $i++) $placeFmt($n - 1 - $i, 8);
    for ($i = 8; $i <= 14; $i++) $placeFmt(8, $n - 15 + $i);

    // ---- data + mask terbaik ----
    $bitsArr = [];
    foreach ($codewords as $cw) foreach (str_split(str_pad(decbin($cw), 8, '0', STR_PAD_LEFT)) as $b) $bitsArr[] = (int)$b;
    $best = null; $bestPen = PHP_INT_MAX;
    for ($mask = 0; $mask < 8; $mask++) {
        $mm = $m;
        $bi = 0;
        // penempatan zigzag standar: berpasangan kolom dari kanan ke kiri
        for ($right = $n - 1; $right >= 1; $right -= 2) {
            if ($right == 6) $right = 5; // lewati kolom timing
            for ($vert = 0; $vert < $n; $vert++) {
                for ($j = 0; $j < 2; $j++) {
                    $c = $right - $j;
                    $up = (($right + 1) & 2) == 0;
                    $r = $up ? $n - 1 - $vert : $vert;
                    if ($mm[$r][$c] !== null) continue;
                    $bit = $bi < count($bitsArr) ? $bitsArr[$bi++] : 0;
                    if (qr_mask($mask, $r, $c)) $bit ^= 1;
                    $mm[$r][$c] = $bit;
                }
            }
        }
        qr_format($mm, $v, $mask);
        $pen = qr_penalty($mm);
        if ($pen < $bestPen) { $bestPen = $pen; $best = $mm; }
    }
    return $best;
}

function qr_rs(array $data, int $eccLen): array {
    static $EXP = null, $LOG = null;
    if ($EXP === null) {
        $EXP = [1]; $LOG = [];
        for ($i = 0, $x = 1; $i < 255; $i++) { $EXP[$i] = $x; $LOG[$x] = $i; $x <<= 1; if ($x & 0x100) $x ^= 0x11D; }
    }
    $mul = function ($a, $b) use ($EXP, $LOG) { return ($a && $b) ? $EXP[($LOG[$a] + $LOG[$b]) % 255] : 0; };
    $gen = [1];
    for ($i = 0; $i < $eccLen; $i++) {
        $ng = array_fill(0, count($gen) + 1, 0);
        foreach ($gen as $k => $coef) {
            $ng[$k] ^= $mul($coef, 1);
            $ng[$k + 1] ^= $mul($coef, $EXP[$i]);
        }
        $gen = $ng;
    }
    $res = array_fill(0, $eccLen, 0);
    foreach ($data as $d) {
        $f = $d ^ array_shift($res);
        array_push($res, 0);
        foreach ($res as $k => $r) $res[$k] = $r ^ $mul($f, $gen[$k + 1] ?? 0);
    }
    return [$data, $res];
}

function qr_mask(int $m, int $r, int $c): bool {
    switch ($m) {
        case 0: return ($r + $c) % 2 == 0;
        case 1: return $r % 2 == 0;
        case 2: return $c % 3 == 0;
        case 3: return ($r + $c) % 3 == 0;
        case 4: return (intdiv($r, 2) + intdiv($c, 3)) % 2 == 0;
        case 5: return ($r * $c) % 2 + ($r * $c) % 3 == 0;
        case 6: return (($r * $c) % 2 + ($r * $c) % 3) % 2 == 0;
        default: return (($r + $c) % 2 + ($r * $c) % 3) % 2 == 0;
    }
}

function qr_format(array &$m, int $v, int $mask): void {
    $n = count($m);
    $data = (0b01 << 3) | $mask; // ECC L
    $rem = $data;
    for ($i = 0; $i < 10; $i++) $rem = ($rem << 1) ^ (($rem >> 9) ? 0x537 : 0);
    $bits = (($data << 10) | $rem) ^ 0x5412;
    $bit = function ($i) use ($bits) { return ($bits >> $i) & 1; };
    for ($i = 0; $i <= 5; $i++) $m[8][$i] = $bit($i);
    $m[8][7] = $bit(6); $m[8][8] = $bit(7); $m[7][8] = $bit(8);
    for ($i = 9; $i <= 14; $i++) $m[14 - $i][8] = $bit($i);
    for ($i = 0; $i <= 7; $i++) $m[$n - 1 - $i][8] = $bit($i);
    for ($i = 8; $i <= 14; $i++) $m[8][$n - 15 + $i] = $bit($i);
}

function qr_penalty(array $m): int {
    $n = count($m); $pen = 0;
    for ($r = 0; $r < $n; $r++) {
        $run = 1;
        for ($c = 1; $c < $n; $c++) {
            if ($m[$r][$c] === $m[$r][$c - 1]) { $run++; if ($run == 5) $pen += 3; elseif ($run > 5) $pen++; }
            else $run = 1;
        }
    }
    for ($c = 0; $c < $n; $c++) {
        $run = 1;
        for ($r = 1; $r < $n; $r++) {
            if ($m[$r][$c] === $m[$r - 1][$c]) { $run++; if ($run == 5) $pen += 3; elseif ($run > 5) $pen++; }
            else $run = 1;
        }
    }
    for ($r = 0; $r < $n - 1; $r++) for ($c = 0; $c < $n - 1; $c++)
        if ($m[$r][$c] === $m[$r][$c+1] && $m[$r][$c] === $m[$r+1][$c] && $m[$r][$c] === $m[$r+1][$c+1]) $pen += 3;
    // aturan 4: pola 1:1:3:1:1 (finder-like) pada baris & kolom
    $pat1 = [1,0,1,1,1,0,1,0,0,0,0]; $pat2 = [0,0,0,0,1,0,1,1,1,0,1];
    for ($r = 0; $r < $n; $r++) {
        for ($c = 0; $c + 11 <= $n; $c++) {
            $w = array_slice($m[$r], $c, 11);
            if ($w === $pat1 || $w === $pat2) $pen += 40;
        }
    }
    for ($c = 0; $c < $n; $c++) {
        for ($r = 0; $r + 11 <= $n; $r++) {
            $w = array_map(fn($rr) => $m[$rr][$c], range($r, $r + 10));
            if ($w === $pat1 || $w === $pat2) $pen += 40;
        }
    }
    $dark = 0; for ($r = 0; $r < $n; $r++) for ($c = 0; $c < $n; $c++) if ($m[$r][$c]) $dark++;
    $pen += (int)(abs($dark * 20 - $n * $n * 10) / ($n * $n)) * 10;
    return $pen;
}
