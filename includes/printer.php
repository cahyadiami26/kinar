<?php
/**
 * Kinar Resto - Printer Thermal LAN (ESC/POS over TCP)
 * Mode: 'semua' -> semua pesanan ke semua printer aktif
 *       'makanan_ke_dapur_minuman_ke_bar' -> makanan ke dapur, minuman ke bar
 */

function esc_text(string $s): string {
    return mb_convert_encoding($s, 'UTF-8', 'UTF-8');
}

/** Kirim payload ke satu printer via socket. Return true jika terkirim. */
function printer_send(int $printer_id, string $payload): bool {
    $st = db()->prepare('SELECT * FROM printers WHERE id = ? AND is_active = 1');
    $st->execute([$printer_id]);
    $p = $st->fetch();
    if (!$p) return false;
    $fp = @fsockopen($p['ip_address'], (int)$p['port'], $errno, $errstr, 3);
    if (!$fp) return false;
    fwrite($fp, $payload);
    fclose($fp);
    return true;
}

/** Buat payload ESC/POS untuk satu pesanan pada satu lokasi printer */
function build_ticket(int $order_id, string $location): string {
    $o = db()->prepare('SELECT o.*, t.label AS table_label FROM orders o
        LEFT JOIN resto_tables t ON t.id = o.table_id WHERE o.id = ?');
    $o->execute([$order_id]);
    $order = $o->fetch();
    if (!$order) return '';
    $items = db()->prepare('SELECT oi.*, m.name, m.category_id, c.slug AS cat_slug FROM order_items oi
        JOIN menu_items m ON m.id = oi.menu_id JOIN categories c ON c.id = m.category_id WHERE oi.order_id = ?');
    $items->execute([$order_id]);
    $rows = $items->fetchAll();
    $isDrink = fn($r) => in_array($r['cat_slug'], ['minuman-dingin', 'minuman-hangat']);
    if ($location === 'dapur')  $rows = array_values(array_filter($rows, fn($r) => !$isDrink($r)));
    if ($location === 'bar')    $rows = array_values(array_filter($rows, $isDrink));
    if (!$rows) return '';

    $esc = "\x1B@\x1Ba\x01";                       // init, center
    $esc .= "KINAR RESTO\n";
    $esc .= strtoupper($location) . " - TIKET PESANAN\n";
    $esc .= "------------------------------\n";
    $esc .= "No: {$order['order_code']}\n";
    $esc .= "Meja: " . ($order['table_label'] ?: 'Takeaway') . "\n";
    $esc .= "Tamu: {$order['customer_name']}\n";
    $esc .= "Jam: " . date('d/m/Y H:i', strtotime($order['created_at'])) . "\n";
    $esc .= "------------------------------\n\x1Ba\x00"; // left
    foreach ($rows as $r) {
        $esc .= $r['qty'] . 'x ' . $r['name'] . "\n";
        if ($r['note']) $esc .= "   catatan: {$r['note']}\n";
    }
    if ($order['note']) $esc .= "\nMemo: {$order['note']}\n";
    $esc .= "------------------------------\n\x1DV\x01"; // cut
    return $esc;
}

/** Antrian cetak otomatis saat pesanan dikonfirmasi kasir */
function queue_order_print(int $order_id): void {
    $printers = db()->query('SELECT * FROM printers WHERE is_active = 1')->fetchAll();
    $mode = 'semua';
    foreach ($printers as $p) if ($p['mode'] === 'makanan_ke_dapur_minuman_ke_bar') $mode = 'makanan_ke_dapur_minuman_ke_bar';
    foreach ($printers as $p) {
        $payload = build_ticket($order_id, $p['location']);
        if ($payload === '') continue;
        if ($mode === 'makanan_ke_dapur_minuman_ke_bar' && $p['location'] === 'kasir') continue;
        $st = db()->prepare('INSERT INTO print_jobs (order_id, printer_id, payload) VALUES (?,?,?)');
        $st->execute([$order_id, $p['id'], $payload]);
        if (printer_send($p['id'], $payload)) {
            db()->prepare('UPDATE print_jobs SET status="tercetak", printed_at=NOW() WHERE id=?')
                ->execute([db()->lastInsertId()]);
        }
    }
}

/** Cetak struk pembayaran kasir */
function print_receipt(int $order_id): void {
    $o = db()->prepare('SELECT o.*, t.label AS table_label, p.payment_code, p.method, p.amount_paid, p.change_amount, p.paid_at
        FROM orders o LEFT JOIN resto_tables t ON t.id=o.table_id
        JOIN payments p ON p.order_id = o.id WHERE o.id = ? ORDER BY p.id DESC LIMIT 1');
    $o->execute([$order_id]);
    $r = $o->fetch();
    if (!$r) return;
    $items = db()->query("SELECT oi.qty, m.name, oi.price FROM order_items oi JOIN menu_items m ON m.id=oi.menu_id WHERE oi.order_id=$order_id")->fetchAll();
    $esc = "\x1B@\x1Ba\x01KINAR RESTO\nSTRUK PEMBAYARAN\n------------------------------\n";
    $esc .= "No: {$r['payment_code']}\nMeja: " . ($r['table_label'] ?: 'Takeaway') . "\nTamu: {$r['customer_name']}\n";
    $esc .= "Jam: " . date('d/m/Y H:i', strtotime($r['paid_at'])) . "\n------------------------------\n\x1Ba\x00";
    foreach ($items as $it) $esc .= $it['qty'] . 'x ' . $it['name'] . '  ' . number_format($it['price'] * $it['qty'], 0, ',', '.') . "\n";
    $esc .= "------------------------------\nTOTAL: Rp " . number_format($r['total'], 0, ',', '.') . "\n";
    $esc .= strtoupper($r['method']) . " dibayar Rp " . number_format($r['amount_paid'], 0, ',', '.') . "\n";
    $esc .= "Kembali Rp " . number_format($r['change_amount'], 0, ',', '.') . "\n\nTerima kasih.\n\x1DV\x01";
    foreach (db()->query("SELECT * FROM printers WHERE location='kasir' AND is_active=1")->fetchAll() as $p) {
        db()->prepare('INSERT INTO print_jobs (order_id, printer_id, payload) VALUES (?,?,?)')->execute([$order_id, $p['id'], $esc]);
        printer_send($p['id'], $esc);
    }
}
