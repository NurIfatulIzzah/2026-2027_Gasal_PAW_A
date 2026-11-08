<?php
$page_title = $page_title ?? 'Praktikum Modul 6 - Penjualan';
$body_class = $body_class ?? 'bg-white';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($page_title, ENT_QUOTES, 'UTF-8') ?></title>
  <!-- Mengikuti pola header.php contoh asprak: Tailwind lewat CDN, tanpa style.css -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = { theme: { extend: { fontFamily: {
      sans: ['"Plus Jakarta Sans"','sans-serif'],
      serif: ['"Playfair Display"','serif']
    } } } };
  </script>
</head>
<body class="<?= htmlspecialchars($body_class, ENT_QUOTES, 'UTF-8') ?> text-slate-800 font-sans min-h-screen flex flex-col">
