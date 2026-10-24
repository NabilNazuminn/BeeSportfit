<?php
/** First-draft texts for the Warranty and Terms pages. The shop owner / a lawyer MUST review them (Admin > Halaman). */

function default_page_html(string $slug): string
{
    if ($slug === 'warranty') return <<<'HTML'
<p><em>Draf awal - mohon disesuaikan dengan kebijakan resmi perusahaan melalui Admin &rarr; Halaman.</em></p>
<h2>1. Cakupan Garansi</h2>
<p>Setiap produk yang dijual melalui website ini dilengkapi garansi sesuai masa garansi yang tercantum pada halaman produk (dihitung sejak tanggal barang diterima). Garansi mencakup kerusakan yang disebabkan oleh cacat bahan atau cacat produksi.</p>
<h2>2. Hal yang Tidak Ditanggung</h2>
<ul>
<li>Kerusakan akibat pemakaian yang tidak sesuai petunjuk, beban melebihi kapasitas, atau kelalaian pengguna.</li>
<li>Kerusakan akibat benturan, jatuh, terkena air/cairan (untuk komponen elektronik), atau bencana alam.</li>
<li>Perbaikan atau modifikasi oleh pihak lain yang tidak ditunjuk oleh kami.</li>
<li>Bagian habis pakai seperti karet/grip, kabel, bantalan, dan lapisan permukaan yang aus karena pemakaian wajar.</li>
</ul>
<h2>3. Cara Klaim Garansi</h2>
<ol>
<li>Hubungi kami melalui live chat, WhatsApp, atau email dengan menyebutkan kode pesanan.</li>
<li>Sertakan foto/video kerusakan dan penjelasan singkat.</li>
<li>Tim kami akan memeriksa dan memberi tahu solusinya (perbaikan, penggantian suku cadang, atau penggantian unit).</li>
</ol>
<h2>4. Layanan Purna Jual</h2>
<p>Kami membantu klaim garansi dan penyediaan suku cadang. Biaya di luar masa garansi atau untuk kerusakan yang tidak ditanggung akan diinformasikan terlebih dahulu.</p>
HTML;

    if ($slug === 'terms') return <<<'HTML'
<p><em>Draf awal - mohon disesuaikan dan ditinjau oleh pemilik usaha / penasihat hukum melalui Admin &rarr; Halaman.</em></p>
<h2>1. Pesanan dan Penawaran</h2>
<p>Pesanan atau permintaan penawaran yang dikirim melalui website diselesaikan lewat percakapan WhatsApp dengan admin kami, tempat ketersediaan barang, ongkos kirim, dan total pembayaran disepakati. Pesanan dianggap sah setelah disepakati bersama.</p>
<h2>2. Harga</h2>
<p>Harga ditampilkan dalam Rupiah (IDR) dan dapat berubah sewaktu-waktu tanpa pemberitahuan. Harga yang berlaku adalah harga yang disepakati pada saat pesanan. Ongkos kirim tidak termasuk dalam harga produk dan disepakati di WhatsApp.</p>
<h2>3. Pembayaran</h2>
<p>Metode dan tahapan pembayaran disepakati saat chat WhatsApp dengan admin. Barang diproses dan dikirim setelah pembayaran sesuai kesepakatan diterima.</p>
<h2>4. Pengiriman</h2>
<p>Estimasi waktu pengiriman diinformasikan oleh admin saat kesepakatan dan dapat dipengaruhi oleh lokasi dan ketersediaan barang. Pembeli wajib memeriksa barang saat diterima dan melaporkan kerusakan pengiriman secepatnya.</p>
<h2>5. Pembatalan dan Pengembalian</h2>
<p>Pesanan dapat dibatalkan sebelum barang diproses. Pembatalan atau pengembalian setelah barang diproses atau dikirim tunduk pada kesepakatan dengan admin dan dapat dikenakan biaya.</p>
<h2>6. Garansi</h2>
<p>Garansi mengikuti <a href="page.php?slug=warranty">Kebijakan Garansi</a> kami.</p>
<h2>7. Data Pribadi</h2>
<p>Data yang Anda berikan (nama, kontak, alamat) digunakan untuk memproses pesanan, pengiriman, dan layanan purna jual, serta tidak dijual kepada pihak lain.</p>
<h2>8. Perubahan Ketentuan</h2>
<p>Kami dapat memperbarui syarat dan ketentuan ini sewaktu-waktu. Versi terbaru selalu tersedia di halaman ini.</p>
HTML;

    return '';
}
