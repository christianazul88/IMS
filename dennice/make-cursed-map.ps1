# Render the supplied Free Cursed Land Tiled map into one browser-ready PNG.
# The source TMX is a user-supplied asset; this script only composites its
# existing layers and tiles, with no external downloads or generated art.
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Drawing

$sourceRoot = 'C:\Users\CHRISTIAN\Downloads\Free-Cursed-Land-Top-Down-Pixel-Art-Tileset\Tiled_files'
$tmxPath = Join-Path $sourceRoot 'Cursed_land.tmx'
$outputPath = 'C:\xampp\htdocs\princess_protocol\assets\adapted-cursed-land.png'

if (!(Test-Path -LiteralPath $tmxPath)) { throw "Missing Tiled map: $tmxPath" }
$xml = [xml](Get-Content -LiteralPath $tmxPath -Raw)
$map = $xml.map
$tileW = [int]$map.tilewidth
$tileH = [int]$map.tileheight
$chunks = @($map.layer.data.chunk)
if (!$chunks.Count) { throw 'The supplied TMX has no infinite-map chunks.' }
$minTileX = (($chunks | ForEach-Object { [int]$_.x }) | Measure-Object -Minimum).Minimum
$maxTileX = (($chunks | ForEach-Object { [int]$_.x + [int]$_.width }) | Measure-Object -Maximum).Maximum
$minTileY = (($chunks | ForEach-Object { [int]$_.y }) | Measure-Object -Minimum).Minimum
$maxTileY = (($chunks | ForEach-Object { [int]$_.y + [int]$_.height }) | Measure-Object -Maximum).Maximum
$mapW = [int]($maxTileX - $minTileX)
$mapH = [int]($maxTileY - $minTileY)

function Resolve-AssetPath([string]$baseDir, [string]$relative) {
  return [IO.Path]::GetFullPath((Join-Path $baseDir $relative))
}

# Decode Tiled's base64+zlib tile data into little-endian 32-bit GIDs.
function Read-Gids($dataNode, [int]$count) {
  $bytes = [Convert]::FromBase64String(($dataNode.InnerText -replace '\s',''))
  # DeflateStream accepts the raw DEFLATE payload; Tiled's zlib wrapper has a
  # two-byte header and a four-byte Adler32 trailer around that payload.
  $payload = New-Object byte[] ($bytes.Length - 6)
  [Array]::Copy($bytes, 2, $payload, 0, $payload.Length)
  $input = New-Object IO.MemoryStream(,$payload)
  $inflate = New-Object IO.Compression.DeflateStream($input,[IO.Compression.CompressionMode]::Decompress)
  $rawStream = New-Object IO.MemoryStream
  $inflate.CopyTo($rawStream)
  $inflate.Dispose(); $input.Dispose()
  $raw = $rawStream.ToArray(); $rawStream.Dispose()
  $gids = New-Object uint32[] $count
  for ($i=0; $i -lt $count; $i++) {
    $o = $i * 4
    $gids[$i] = [BitConverter]::ToUInt32($raw, $o)
  }
  return $gids
}

# Collect external and inline tilesets. Each entry keeps its first global ID,
# atlas bitmap, columns, and tile dimensions for the render loop below.
$tilesets = @()
foreach ($ts in $map.tileset) {
  $first = [int]$ts.firstgid
  if ($ts.source) {
    $tsxPath = Resolve-AssetPath $sourceRoot ([string]$ts.source)
    $tsx = [xml](Get-Content -LiteralPath $tsxPath -Raw)
    $def = $tsx.tileset
    $base = Split-Path -Parent $tsxPath
  } else {
    $def = $ts
    $base = $sourceRoot
  }
  $imageNode = $def.image
  $imagePath = Resolve-AssetPath $base ([string]$imageNode.source)
  $tilesets += [pscustomobject]@{
    first = $first
    count = [int]$def.tilecount
    columns = [int]$def.columns
    tileW = [int]$def.tilewidth
    tileH = [int]$def.tileheight
    bitmap = [Drawing.Bitmap]::new($imagePath)
  }
}
$tilesets = @($tilesets | Sort-Object first)

$nativeW = $mapW * $tileW
$nativeH = $mapH * $tileH
$native = [Drawing.Bitmap]::new($nativeW,$nativeH,[Drawing.Imaging.PixelFormat]::Format32bppArgb)
$g = [Drawing.Graphics]::FromImage($native)
$g.Clear([Drawing.Color]::Transparent)
$g.CompositingMode = [Drawing.Drawing2D.CompositingMode]::SourceOver
$g.InterpolationMode = [Drawing.Drawing2D.InterpolationMode]::NearestNeighbor
$g.PixelOffsetMode = [Drawing.Drawing2D.PixelOffsetMode]::Half

foreach ($layer in $map.layer) {
  foreach ($chunk in @($layer.data.chunk)) {
    $chunkW = [int]$chunk.width
    $chunkH = [int]$chunk.height
    $gids = Read-Gids $chunk ($chunkW * $chunkH)
    for ($i=0; $i -lt $gids.Length; $i++) {
      $rawGid = [uint32]$gids[$i]
      if ($rawGid -eq 0) { continue }
      # Tiled reserves the high four bits for horizontal/vertical/diagonal flips.
      $gid = $rawGid -band 0x0fffffff
      $ts = $null
      foreach ($candidate in ($tilesets | Sort-Object first -Descending)) {
        if ($gid -ge $candidate.first) { $ts = $candidate; break }
      }
      if (!$ts) { continue }
      $local = [int]($gid - $ts.first)
      if ($local -lt 0 -or $local -ge $ts.count) { continue }
      $sx = ($local % $ts.columns) * $ts.tileW
      $sy = [math]::Floor($local / $ts.columns) * $ts.tileH
      $dx = (([int]$chunk.x - $minTileX) + ($i % $chunkW)) * $tileW
      $dy = (([int]$chunk.y - $minTileY) + [math]::Floor($i / $chunkW)) * $tileH
      $srcRect = [Drawing.Rectangle]::new($sx,$sy,$ts.tileW,$ts.tileH)
      $dstRect = [Drawing.Rectangle]::new($dx,$dy,$tileW,$tileH)
      $g.DrawImage($ts.bitmap,$dstRect,$srcRect.X,$srcRect.Y,$srcRect.Width,$srcRect.Height,[Drawing.GraphicsUnit]::Pixel)
    }
  }
}

$alphaMinX = $nativeW; $alphaMinY = $nativeH; $alphaMaxX = -1; $alphaMaxY = -1
for ($yy=0; $yy -lt $nativeH; $yy+=4) {
  for ($xx=0; $xx -lt $nativeW; $xx+=4) {
    if ($native.GetPixel($xx,$yy).A -gt 5) {
      $alphaMinX=[math]::Min($alphaMinX,$xx); $alphaMinY=[math]::Min($alphaMinY,$yy)
      $alphaMaxX=[math]::Max($alphaMaxX,$xx); $alphaMaxY=[math]::Max($alphaMaxY,$yy)
    }
  }
}
Write-Output ("Composited alpha bounds: {0},{1} to {2},{3}" -f $alphaMinX,$alphaMinY,$alphaMaxX,$alphaMaxY)

# Preserve pixel-art edges while creating the game's 16:9 canvas. The authored
# infinite map includes transparent staging rows above and below its dense
# playable art. These two values choose the visible source slice; adjust them
# if you want a different section of the supplied Tiled map.
$canvasW = 1280; $canvasH = 720
# The authored 38x25 playable composition sits inside the larger infinite-map
# staging bounds at this offset. This crop removes the empty staging margin.
$mapSourceX = 416
$mapSourceY = 480
$mapSourceW = 608
$mapSourceH = 400
$final = [Drawing.Bitmap]::new($canvasW,$canvasH,[Drawing.Imaging.PixelFormat]::Format32bppArgb)
$fg = [Drawing.Graphics]::FromImage($final)
$fg.Clear([Drawing.Color]::FromArgb(255,12,9,18))
$fg.InterpolationMode = [Drawing.Drawing2D.InterpolationMode]::NearestNeighbor
$fg.PixelOffsetMode = [Drawing.Drawing2D.PixelOffsetMode]::Half
$fg.DrawImage($native,[Drawing.Rectangle]::new(0,0,$canvasW,$canvasH),$mapSourceX,$mapSourceY,$mapSourceW,$mapSourceH,[Drawing.GraphicsUnit]::Pixel)
$final.Save($outputPath,[Drawing.Imaging.ImageFormat]::Png)

$fg.Dispose(); $g.Dispose(); $final.Dispose(); $native.Dispose()
foreach ($ts in $tilesets) { $ts.bitmap.Dispose() }
Write-Output ("Rendered infinite Tiled bounds {0}x{1} tiles to {2} ({3}x{4} PNG)" -f $mapW,$mapH,$outputPath,$canvasW,$canvasH)
