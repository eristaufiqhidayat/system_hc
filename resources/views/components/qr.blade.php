@props(['seed' => 7])
@php
    // Pola QR ilustratif (bukan QR asli) — ganti dengan QRIS dinamis dari payment gateway.
    $s = (int) $seed; $rects = '';
    $rnd = function () use (&$s) { $s = ($s * 9301 + 49297) % 233280; return $s / 233280; };
    $finder = fn ($x, $y, $a, $b) => $x >= $a && $x < $a + 7 && $y >= $b && $y < $b + 7;
    for ($y = 0; $y < 25; $y++) {
        for ($x = 0; $x < 25; $x++) {
            if ($finder($x, $y, 0, 0) || $finder($x, $y, 18, 0) || $finder($x, $y, 0, 18)) continue;
            if ($rnd() > .5) $rects .= '<rect x="'.$x.'" y="'.$y.'" width="1" height="1"/>';
        }
    }
    $f = fn ($a, $b) => '<rect x="'.$a.'" y="'.$b.'" width="7" height="7" fill="#1C2620"/><rect x="'.($a + 1).'" y="'.($b + 1).'" width="5" height="5" fill="#fff"/><rect x="'.($a + 2).'" y="'.($b + 2).'" width="3" height="3" fill="#1C2620"/>';
@endphp
<svg viewBox="0 0 25 25" width="100%" height="100%" shape-rendering="crispEdges" role="img" aria-label="Kode QR pembayaran"><g fill="#1C2620">{!! $rects !!}</g>{!! $f(0, 0) !!}{!! $f(18, 0) !!}{!! $f(0, 18) !!}<rect x="10" y="10" width="5" height="5" fill="#fff"/><rect x="10.8" y="10.8" width="3.4" height="3.4" rx=".6" fill="#239A45"/></svg>
