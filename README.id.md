<p align="center">
  <img src="https://raw.githubusercontent.com/sendkuy/sendkuy-laravel/main/art/banner.png" alt="SendKuy for Laravel" width="100%">
</p>

# SendKuy for Laravel

Notifikasi WhatsApp, SMS, dan email untuk Laravel, facade untuk seluruh API SendKuy, dan webhook bertanda tangan yang sampai sebagai event Laravel.

[Read in English](README.md)

## Yang bisa kamu kirim

- **WhatsApp** dari nomor yang tersambung lewat kode QR, [ekstensi Chrome SendKuy WA Gateway](https://chromewebstore.google.com/detail/sendkuy-wa-gateway/kdopgnbocfeoehhjbolfmibnmoicdofc), atau WhatsApp Cloud API ([API WhatsApp](https://sendkuy.com/channels/whatsapp?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel)).
- **SMS** lewat HP Android dengan aplikasi SendKuy Gateway, atau provider SMS ([SMS gateway](https://sendkuy.com/channels/sms?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel)).
- **Email** dari domain kamu sendiri ([API email](https://sendkuy.com/channels/email?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel)).
- **Kode OTP** lewat `SendKuy::verifications()`, dengan cadangan dari WhatsApp ke SMS.
- **Notifikasi Laravel** lewat channel `sendkuy`, tidak pernah terkirim dobel saat job antrean diulang.
- **Percakapan dua arah** untuk helpdesk: pesan masuk tiba sebagai event `WebhookReceived`.

## Instalasi

```bash
composer require sendkuy/laravel
```

Butuh Laravel 12 atau 13 dan PHP 8.2 ke atas. Tambahkan API key ke `.env`:

```env
SENDKUY_API_KEY=sk_live_...
```

## Kirim dari mana saja

```php
use SendKuy\Laravel\Facades\SendKuy;

SendKuy::messages()->send([
    'channel' => 'whatsapp',
    'to' => ['+6281234567890'],
    'body' => 'Pesanan kamu sedang dikirim.',
]);
```

## Notifikasi

```php
public function via(object $notifiable): array
{
    return ['sendkuy'];
}

public function toSendKuy(object $notifiable): SendKuyMessage
{
    return SendKuyMessage::whatsapp('Pesanan #1042 sedang dikirim.');
}
```

Di model, tentukan tujuannya:

```php
public function routeNotificationForSendKuy(Notification $notification): string
{
    return $this->phone;
}
```

Notifikasi yang memakai `ShouldQueue` lalu diulang (worker mati atau job timeout) **tidak pernah terkirim dobel**: idempotency key-nya diturunkan dari ID notifikasi Laravel, yang sama di setiap pengulangan job. Syaratnya, `toSendKuy()` harus deterministik: susun pesan dari data notifikasi itu sendiri, jangan dari `now()`. Kalau pengulangan menghasilkan isi yang berbeda dengan key yang sama, API menolaknya dengan `409 idempotency_key_reused` dan job gagal, bukan mengirim dua kali. Untuk menjadwalkan, simpan waktunya di notifikasi saat dibuat lalu berikan ke `scheduleAt()`.

## Webhook

Paket ini mendaftarkan `POST /sendkuy/webhook`. Simpan secret-nya di `.env`:

```env
SENDKUY_WEBHOOK_SECRET=whsec_...
```

Setiap kiriman dengan signature valid menjadi event `SendKuy\Laravel\Events\WebhookReceived`. Kalau `SENDKUY_WEBHOOK_SECRET` belum diisi, rute menjawab `500` dan mencatat error, sehingga masalahnya terlihat di riwayat pengiriman webhook dasbor SendKuy. Pasang listener `ShouldQueue` supaya rute menjawab dalam 10 detik, dan pakai `$event->id` untuk melewati kiriman ganda.

Event `message.received` membawa pengirim (`from`), channel, nomor yang menerimanya, dan media bila ada. Kalau akunmu meneruskan pesan masuk ke webhook saja, pesannya tidak disimpan di SendKuy, jadi `id` dan `conversation_id` bernilai `null`: balas dengan `SendKuy::messages()->send()` ke `$event->data['from']` di `$event->data['channel']`.

Panduan lengkap ada di [README bahasa Inggris](README.md) dan [docs.sendkuy.com/laravel](https://docs.sendkuy.com/laravel?utm_source=readme&utm_medium=sdk&utm_campaign=sendkuy-laravel).

## Lisensi

MIT.
