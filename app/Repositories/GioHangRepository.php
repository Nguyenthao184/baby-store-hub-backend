<?php

namespace App\Repositories;

use Illuminate\Support\Facades\Redis;

class GioHangRepository
{
    private string $prefix = 'giohang:';
    private int $ttlGiay = 60 * 60 * 24 * 7; // 7 ngày

    private function key(int|string $nguoiDungId): string { return $this->prefix.$nguoiDungId; }

    public function tatCa(int|string $nguoiDungId): array
    {
        $raw = Redis::hgetall($this->key($nguoiDungId));
        return array_map(fn($v) => json_decode($v, true), $raw);
    }

    public function lay(int|string $nguoiDungId, int|string $sanPhamId): ?array
    {
        $v = Redis::hget($this->key($nguoiDungId), (string)$sanPhamId);
        return $v ? json_decode($v, true) : null;
    }

    public function luu(int|string $nguoiDungId, int|string $sanPhamId, array $item): void
    {
        $k = $this->key($nguoiDungId);
        Redis::hset($k, (string)$sanPhamId, json_encode($item, JSON_UNESCAPED_UNICODE));
        Redis::expire($k, $this->ttlGiay);
    }

    public function xoa(int|string $nguoiDungId, int|string $sanPhamId): void
    {
        Redis::hdel($this->key($nguoiDungId), (string)$sanPhamId);
    }

    public function xoaHet(int|string $nguoiDungId): void
    {
        Redis::del($this->key($nguoiDungId));
    }
}
