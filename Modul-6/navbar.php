<header class="border-b border-slate-200 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-8 py-3 text-[10px] font-bold tracking-widest uppercase text-slate-600">
    Praktikum Pengembangan Aplikasi Web <span class="float-right text-slate-400">Modul 6 / Master Detail</span>
  </div>
</header>
<nav class="border-b border-slate-200 bg-white">
  <div class="max-w-7xl mx-auto px-4 sm:px-8 py-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div class="font-serif text-2xl font-extrabold text-slate-950">PENJUALAN <span class="font-sans text-xs bg-slate-950 text-white px-2 py-1 align-middle">MASTER–DETAIL</span></div>
    <div class="flex gap-2 text-xs font-bold uppercase">
      <a class="px-4 py-2 <?= $page === 'transaksi' || $page === 'detail' ? 'bg-slate-900 text-white' : 'border border-slate-200 text-slate-700' ?>" href="index.php?page=transaksi">Transaksi</a>
      <a class="px-4 py-2 <?= $page === 'barang' ? 'bg-slate-900 text-white' : 'border border-slate-200 text-slate-700' ?>" href="index.php?page=barang">Barang</a>
    </div>
  </div>
</nav>
