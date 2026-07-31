<?php
// Trang hiển thị lỗi (403, 404, 500) template từ internet
$code = isset($_GET['code']) ? (int)$_GET['code'] : 500;
$base = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

$configs = [
    403 => [
        'gradient' => '#E8937A, #F4C2A1',
        'btn_bg'   => '#C97B5A',
        'title'    => '403 - Truy cập bị từ chối',
        'heading'  => 'Bạn không có quyền truy cập trang này!',
    ],
    404 => [
        'gradient' => '#87CEEB, #B0E0E6',
        'btn_bg'   => '#5BA3D9',
        'title'    => '404 - Không tìm thấy trang',
        'heading'  => 'Oops, trang không tồn tại!',
    ],
    500 => [
        'gradient' => '#9B8EA8, #C4B7CC',
        'btn_bg'   => '#7A6E86',
        'title'    => '500 - Lỗi máy chủ',
        'heading'  => 'Máy chủ gặp sự cố, vui lòng thử lại sau!',
    ],
];

$c = $configs[$code] ?? $configs[500];
http_response_code($code);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $c['title'] ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'Inter', sans-serif; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale; }
a { text-decoration: none; color: inherit; }
.icon { width: 1rem; height: 1rem; }
.page { width: 100%; height: 100vh; overflow: hidden; display: flex; flex-direction: column; background: linear-gradient(to bottom, <?= $c['gradient'] ?>); position: relative; }
.bg-layer { position: absolute; inset: 0; pointer-events: none; opacity: 0.8; -webkit-mask-image: linear-gradient(to bottom, black 40%, transparent 95%); mask-image: linear-gradient(to bottom, black 40%, transparent 95%); }
.bg-center { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; }
.bg-text { position: relative; color: #FFFFFF; font-weight: 900; line-height: 1; letter-spacing: -0.05em; white-space: nowrap; font-size: clamp(200px, 48vw, 800px); transform: scale(1.15, 1.4); }
.bg-oval { position: absolute; border-radius: 9999px; background: #FFFFFF; height: 22vh; width: clamp(120px, 20vw, 400px); transform-origin: center; transform: scaleY(1.4); }
@media (min-width: 640px) { .bg-oval { height: 26vh; } }
@media (min-width: 768px) { .bg-oval { height: 50vh; } }
.video-wrap { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; margin-top: calc(-6vh - 40px); }
.video-box { width: 120vw; height: 85vh; }
@media (min-width: 640px) { .video-box { width: 70vw; height: 70vh; } }
@media (min-width: 768px) { .video-box { width: 62vw; height: 78vh; } }
.hero-video { width: 100%; height: 100%; object-fit: contain; pointer-events: none; mix-blend-mode: darken; }
.bottom-content { position: relative; z-index: 30; margin-top: auto; padding-bottom: 2rem; display: flex; flex-direction: column; align-items: center; text-align: center; padding-left: 1rem; padding-right: 1rem; }
@media (min-width: 640px) { .bottom-content { padding-bottom: 4rem; } }
.oops-heading { color: #FFFFFF; font-size: 1.125rem; font-weight: 500; margin-bottom: 0.75rem; }
@media (min-width: 640px) { .oops-heading { font-size: 1.25rem; margin-bottom: 1rem; } }
@media (min-width: 768px) { .oops-heading { font-size: 1.5rem; } }
.home-btn { display: inline-flex; align-items: center; gap: 0.5rem; padding: 0.75rem 1.5rem; border-radius: 9999px; color: #FFFFFF; font-weight: 600; font-size: 0.875rem; background: <?= $c['btn_bg'] ?>; transition: transform 0.2s ease, box-shadow 0.2s ease; }
@media (min-width: 640px) { .home-btn { padding: 1rem 2rem; font-size: 1rem; } .home-btn .icon { width: 1.25rem; height: 1.25rem; } }
.home-btn:hover { transform: scale(1.05); box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15); }
</style>
</head>
<body>
<div class="page">
  <div class="bg-layer" aria-hidden="true">
    <div class="bg-center">
      <div id="bgText" class="bg-text"><?= $code ?></div>
      <div id="ovalShape" class="bg-oval"></div>
    </div>
  </div>
  <div class="video-wrap" aria-hidden="true">
    <div class="video-box">
      <video autoplay loop muted playsinline class="hero-video"
        src="https://d8j0ntlcm91z4.cloudfront.net/user_38xzZboKViGWJOttwIXH07lWA1P/hf_20260713_234424_b1332b69-2e69-4302-8dbc-40f86846afbd.mp4">
      </video>
    </div>
  </div>
  <div class="bottom-content">
    <h1 class="oops-heading"><?= $c['heading'] ?></h1>
    <a href="<?= $base ?>/" class="home-btn">
      <svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="19" y1="12" x2="5" y2="12"></line><polyline points="12 19 5 12 12 5"></polyline></svg>
      Quay lại
    </a>
  </div>
</div>
<script>
(function () {
  'use strict';
  var bgText = document.getElementById('bgText');
  var ovalShape = document.getElementById('ovalShape');
  function updateScale() {
    if (!bgText || !ovalShape) return;
    var textHeight = bgText.offsetHeight;
    if (!textHeight) return;
    var scaleY = (window.innerHeight / textHeight) * 1.4;
    bgText.style.transform = 'scale(1.15, ' + scaleY + ')';
    ovalShape.style.transform = 'scaleY(' + scaleY + ')';
  }
  window.addEventListener('load', updateScale);
  window.addEventListener('resize', updateScale);
  updateScale();
})();
</script>
</body>
</html>
