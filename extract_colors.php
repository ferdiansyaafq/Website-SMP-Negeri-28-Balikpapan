<?php
$img = imagecreatefromjpeg(__DIR__ . '/assets/img/interface2.jpeg');
if (!$img) { echo 'Failed to load image'; exit; }
$w = imagesx($img); $h = imagesy($img);
echo "Image size: {$w}x{$h}\n";

$colors = [];
$step = 15;
for ($y = 0; $y < $h; $y += $step) {
    for ($x = 0; $x < $w; $x += $step) {
        $rgb = imagecolorat($img, $x, $y);
        $r = ($rgb >> 16) & 0xFF;
        $g = ($rgb >> 8) & 0xFF;
        $b = $rgb & 0xFF;
        $qr = round($r / 24) * 24;
        $qg = round($g / 24) * 24;
        $qb = round($b / 24) * 24;
        $key = "$qr,$qg,$qb";
        if (!isset($colors[$key])) $colors[$key] = ['count'=>0,'sr'=>0,'sg'=>0,'sb'=>0];
        $colors[$key]['count']++;
        $colors[$key]['sr'] += $r;
        $colors[$key]['sg'] += $g;
        $colors[$key]['sb'] += $b;
    }
}
usort($colors, fn($a,$b) => $b['count'] - $a['count']);
echo "Top 30 dominant colors:\n";
foreach (array_slice($colors, 0, 30) as $i => $c) {
    $ar = round($c['sr']/$c['count']);
    $ag = round($c['sg']/$c['count']);
    $ab = round($c['sb']/$c['count']);
    $hex = sprintf('#%02x%02x%02x', $ar, $ag, $ab);
    $n = $i + 1;
    echo "$n. $hex (pixels: {$c['count']})\n";
}

// Also sample specific regions (top, middle, bottom, sidebar areas)
echo "\n--- Region samples ---\n";
$regions = [
    'top-left' => [0, 0, (int)($w*0.25), (int)($h*0.15)],
    'top-center' => [(int)($w*0.25), 0, (int)($w*0.75), (int)($h*0.15)],
    'sidebar-left' => [0, (int)($h*0.15), (int)($w*0.15), (int)($h*0.85)],
    'main-center' => [(int)($w*0.15), (int)($h*0.15), (int)($w*0.85), (int)($h*0.85)],
    'bottom' => [0, (int)($h*0.85), $w, $h],
    'card-area' => [(int)($w*0.2), (int)($h*0.3), (int)($w*0.8), (int)($h*0.7)],
];
foreach ($regions as $name => [$x1,$y1,$x2,$y2]) {
    $rc = []; $cnt = 0;
    $rs = 8;
    for ($y = $y1; $y < $y2; $y += $rs) {
        for ($x = $x1; $x < $x2; $x += $rs) {
            if ($x >= $w || $y >= $h) continue;
            $rgb = imagecolorat($img, $x, $y);
            $rc[] = [($rgb>>16)&0xFF, ($rgb>>8)&0xFF, $rgb&0xFF];
            $cnt++;
        }
    }
    if ($cnt === 0) continue;
    $avgR = (int)(array_sum(array_column($rc,0))/$cnt);
    $avgG = (int)(array_sum(array_column($rc,1))/$cnt);
    $avgB = (int)(array_sum(array_column($rc,2))/$cnt);
    $hex = sprintf('#%02x%02x%02x', $avgR, $avgG, $avgB);
    
    // Also find dominant in this region
    $rcolors = [];
    foreach ($rc as [$r,$g,$b]) {
        $qr = round($r/32)*32; $qg = round($g/32)*32; $qb = round($b/32)*32;
        $k = "$qr,$qg,$qb";
        if (!isset($rcolors[$k])) $rcolors[$k] = ['c'=>0,'sr'=>0,'sg'=>0,'sb'=>0];
        $rcolors[$k]['c']++;
        $rcolors[$k]['sr']+=$r; $rcolors[$k]['sg']+=$g; $rcolors[$k]['sb']+=$b;
    }
    usort($rcolors, fn($a,$b) => $b['c'] - $a['c']);
    $top3 = [];
    foreach (array_slice($rcolors, 0, 3) as $tc) {
        $top3[] = sprintf('#%02x%02x%02x', round($tc['sr']/$tc['c']), round($tc['sg']/$tc['c']), round($tc['sb']/$tc['c']));
    }
    echo "$name: avg=$hex top=" . implode(', ', $top3) . "\n";
}
imagedestroy($img);
