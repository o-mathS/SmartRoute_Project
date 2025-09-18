<?php
// Handler simples para upload de foto de perfil
function handle_profile_upload($file, $usuarioId) {
    $allowed = ['image/jpeg','image/png','image/gif'];
    if ($file['error'] !== UPLOAD_ERR_OK) return ['ok'=>false,'message'=>'Erro no upload'];
    if (!in_array($file['type'], $allowed)) return ['ok'=>false,'message'=>'Tipo de arquivo não permitido'];
    $ext = '.png';
    $destDir = __DIR__ . '/../assets/img/users/';
    if (!is_dir($destDir)) mkdir($destDir, 0755, true);
    $dest = $destDir . $usuarioId . $ext;
    // converte imagens para PNG simples
    $imgData = file_get_contents($file['tmp_name']);
    $im = @imagecreatefromstring($imgData);
    if (!$im) return ['ok'=>false,'message'=>'Arquivo inválido'];
    // reescala para 400x400 mantendo proporção
    $w = imagesx($im); $h = imagesy($im);
    $size = 400;
    $new = imagecreatetruecolor($size, $size);
    // preencher com branco
    $white = imagecolorallocate($new, 255,255,255);
    imagefill($new,0,0,$white);
    // calcular fit
    $ratio = min($size/$w, $size/$h);
    $nw = intval($w * $ratio); $nh = intval($h * $ratio);
    $dstX = intval(($size - $nw)/2); $dstY = intval(($size - $nh)/2);
    imagecopyresampled($new, $im, $dstX, $dstY, 0,0, $nw, $nh, $w, $h);
    imagepng($new, $dest);
    imagedestroy($im); imagedestroy($new);
    return ['ok'=>true,'message'=>'Upload realizado com sucesso'];
}
