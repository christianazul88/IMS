# Generates deliberately simple starter PNGs. Replace any resulting file in assets/ with your own art.
param([switch]$MapOnly)
Add-Type -AssemblyName System.Drawing
$dir = Join-Path $PSScriptRoot 'assets'; New-Item -ItemType Directory -Force -Path $dir | Out-Null
function Map {
  $b=New-Object Drawing.Bitmap 4096,720; $g=[Drawing.Graphics]::FromImage($b); $g.SmoothingMode='AntiAlias'
  $g.Clear([Drawing.ColorTranslator]::FromHtml('#121323'))
  for($x=0;$x -lt 4096;$x+=64){for($y=0;$y -lt 720;$y+=64){$shade=18+(($x/64+$y/64)%3)*5;$c=[Drawing.Color]::FromArgb(255,$shade+10,$shade+8,$shade+25);$br=New-Object Drawing.SolidBrush($c);$g.FillRectangle($br,$x,$y,63,63);$br.Dispose()}}
  $road=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml('#3c2940'));$g.FillRectangle($road,0,100,2680,520);$road.Dispose()
  for($x=80;$x -lt 2680;$x+=180){$br=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml('#6e4354'));$g.FillRectangle($br,$x,120,8,480);$br.Dispose();$br=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml('#e25c62'));$g.FillRectangle($br,$x+8,120,2,480);$br.Dispose()}
  $arena=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml('#2a1837'));$g.FillRectangle($arena,2680,100,1416,520);$arena.Dispose()
  for($x=2740;$x -lt 4096;$x+=110){$br=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml('#4d2953'));$g.FillRectangle($br,$x,125,9,470);$br.Dispose()}
  $gate=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml('#211427'));$g.FillRectangle($gate,2660,80,48,560);$gate.Dispose()
  $glow=New-Object Drawing.Pen([Drawing.ColorTranslator]::FromHtml('#c94e61'),5);$g.DrawLine($glow,2684,92,2684,628);$glow.Dispose()
  $b.Save((Join-Path $dir 'map.png'),[Drawing.Imaging.ImageFormat]::Png);$g.Dispose();$b.Dispose()
}
if($MapOnly){Map;return}
Map
function Sprite($name, $draw) {
  $b=New-Object Drawing.Bitmap 128,128; $g=[Drawing.Graphics]::FromImage($b); $g.SmoothingMode='AntiAlias'; $g.Clear([Drawing.Color]::Transparent)
  & $draw $g
  $b.Save((Join-Path $dir "$name.png"),[Drawing.Imaging.ImageFormat]::Png); $g.Dispose();$b.Dispose()
}
function E($g,$c,$x,$y,$w,$h){$b=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml($c));$g.FillEllipse($b,$x,$y,$w,$h);$b.Dispose()}
function Rect($g,$c,$x,$y,$w,$h){$b=New-Object Drawing.SolidBrush([Drawing.ColorTranslator]::FromHtml($c));$g.FillRectangle($b,$x,$y,$w,$h);$b.Dispose()}
Sprite 'hero' {param($g) E $g '#152842' 28 24 72 82; E $g '#65dce6' 37 17 54 54; E $g '#f7c778' 48 28 32 34; Rect $g '#e75b67' 32 56 64 47; Rect $g '#a5f3ff' 83 45 32 12; E $g '#111827' 52 42 8 8; E $g '#111827' 72 42 8 8}
Sprite 'minion' {param($g) E $g '#2b1631' 25 21 78 84; E $g '#efaa48' 37 26 54 51; Rect $g '#7d364b' 31 65 66 41; Rect $g '#f2d669' 80 54 26 10; E $g '#291d29' 50 46 9 9; E $g '#291d29' 72 46 9 9}
Sprite 'boss' {param($g) E $g '#4b1932' 14 8 100 112; E $g '#d63455' 27 21 75 76; E $g '#f0c78b' 45 33 38 38; Rect $g '#6e2038' 25 76 78 37; Rect $g '#ffdf75' 86 50 35 15; E $g '#1b1120' 52 48 9 10; E $g '#1b1120' 70 48 9 10}
Sprite 'princess' {param($g) E $g '#f4ba78' 39 26 50 51; Rect $g '#f0d15a' 38 11 52 19; E $g '#7c4a92' 27 67 75 48; E $g '#fff1d6' 49 42 8 8; E $g '#fff1d6' 71 42 8 8}
Sprite 'gun' {param($g) Rect $g '#dce9f0' 22 46 78 18; Rect $g '#37677f' 39 62 20 38; Rect $g '#f8c958' 80 50 31 10; Rect $g '#12212b' 22 64 16 19}
Sprite 'shotgun' {param($g) Rect $g '#f4cf72' 15 48 94 18; Rect $g '#a4543d' 36 65 25 40; Rect $g '#241822' 94 50 18 14; Rect $g '#ffe59b' 15 53 24 8}
Sprite 'buff' {param($g) E $g '#ffe674' 23 23 82 82; E $g '#f06a58' 34 34 60 60; Rect $g '#fff8cb' 56 43 16 42; Rect $g '#fff8cb' 43 56 42 16}
Sprite 'bullet' {param($g) E $g '#fff3b0' 29 47 72 34; E $g '#ffb64d' 47 51 48 26}
Sprite 'boss-bullet' {param($g) E $g '#fd5a69' 20 20 88 88; E $g '#ffe085' 42 42 44 44}
