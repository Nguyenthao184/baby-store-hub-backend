<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GhnService
{
    public function __construct() {
        $this->baseUrl = rtrim(
            config('services.ghn.base_url', 'https://dev-online-gateway.ghn.vn/shiip/public-api'),
            '/'
        );
    }

    protected function client()
    {
        return Http::withHeaders([
            'Token'  => config('services.ghn.token'),
            'ShopId' => config('services.ghn.shop_id'),
            'Content-Type' => 'application/json',
        ])->acceptJson();
    }

    private function endpoint(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        return $this->baseUrl . $this->apiPrefix . $path;
    }

    /** Tạo đơn GHN (COD) */
    public function createOrder(array $payload): array
    {
        // /v2/shipping-order/create
        $url = "{$this->baseUrl}/v2/shipping-order/create";
        return $this->client()->post($url, $payload)->json(); // trả về mảng json
    }

    /** Tra cứu đơn theo order_code */
    public function getOrderDetail(string $orderCode): array
    {
        $url = "{$this->baseUrl}/v2/shipping-order/detail";
        return $this->client()->post($url, ['order_code' => $orderCode])->json();
    }

    /**
     * Gọi API GHN lấy danh sách dịch vụ khả dụng giữa 2 quận/huyện
     * Docs: /v2/shipping-order/available-services
     *
     * @throws \RuntimeException|\Illuminate\Http\Client\RequestException
     */
    public function getAvailableServices(int $fromDistrictId, int $toDistrictId, ?int $shopId = null): array
    {
        $url = "{$this->baseUrl}/v2/shipping-order/available-services";

        $payload = [
            'shop_id'       => (int) ($shopId ?? config('services.ghn.shop_id')),
            'from_district' => $fromDistrictId,
            'to_district'   => $toDistrictId,
        ];

        $res = $this->client()->post($url, $payload)->json();

        if (($res['code'] ?? 0) !== 200) {
            throw new \RuntimeException('GHN available-services error: ' . ($res['message'] ?? 'Unknown'));
        }
        return (array) ($res['data'] ?? []);
    }

    /**
     * Lấy service_id tốt nhất cho tuyến from_district → to_district
     * - Có cache theo (shopId, from, to, serviceTypeId)
     * - Nếu truyền $serviceTypeId (ví dụ 2 = Chuẩn), ưu tiên chọn theo loại này
     * - Nếu không truyền, sẽ chọn bản ghi đầu tiên (thường GHN đã sắp xếp hợp lý)
     *
     * @param int      $fromDistrictId  DistrictID nơi lấy hàng (kho)
     * @param int      $toDistrictId    DistrictID nơi giao
     * @param int|null $serviceTypeId   (tuỳ chọn) 1/2/...; ví dụ 2 = Chuẩn
     * @param int|null $shopId          (tuỳ chọn) nếu nhiều shop
     * @param int      $ttlSeconds      TTL cache (mặc định 86400s = 1 ngày)
     *
     * @return int service_id
     * @throws \RuntimeException|\Illuminate\Http\Client\RequestException
     */
    public function getServiceId(
        int $fromDistrictId,
        int $toDistrictId,
        ?int $serviceTypeId = null,
        ?int $shopId = null,
        int $ttlSeconds = 86400
    ): int {
        $cacheKey = implode(':', [
            'ghn', 'service_id',
            (int) ($shopId ?? config('services.ghn.shop_id')),
            $fromDistrictId,
            $toDistrictId,
            (string) ($serviceTypeId ?? 'any'),
        ]);

        return Cache::remember($cacheKey, $ttlSeconds, function () use ($fromDistrictId, $toDistrictId, $serviceTypeId, $shopId) {
            $services = $this->getAvailableServices($fromDistrictId, $toDistrictId, $shopId);

            if (empty($services)) {
                throw new \RuntimeException("GHN: Không có dịch vụ khả dụng từ {$fromDistrictId} → {$toDistrictId}");
            }

            // Mẫu item GHN thường có: service_id, service_type_id, short_name, expected_delivery_time, ...
            if ($serviceTypeId !== null) {
                $match = collect($services)->first(function ($svc) use ($serviceTypeId) {
                    return (int) ($svc['service_type_id'] ?? 0) === (int) $serviceTypeId;
                });

                if ($match && isset($match['service_id'])) {
                    return (int) $match['service_id'];
                }
            }

            // Nếu không match theo type → chọn service có thời gian dự kiến nhanh nhất (nếu có),
            // nếu không có field thì lấy phần tử đầu tiên.
            $sorted = collect($services)->sortBy(function ($svc) {
                // GHN đôi khi trả expected_delivery_time hoặc leadtime, tuỳ môi trường;
                // fallback = 999999 để đẩy item thiếu thông tin xuống cuối.
                return $svc['expected_delivery_time'] ?? $svc['leadtime'] ?? 999999;
            })->values();

            $first = $sorted->first();
            if (!isset($first['service_id'])) {
                throw new \RuntimeException('GHN: Không tìm thấy service_id hợp lệ trong danh sách trả về.');
            }

            return (int) $first['service_id'];
        });
    }

    public function resolveFullAddress(string $address): array
    {
        // Chuẩn hóa chuỗi
        $addr = mb_strtolower($address, 'UTF-8');

        // --- Lấy danh sách tỉnh/thành ---
        $provinces = Cache::remember("ghn.provinces", 86400, function () {
            $url = "{$this->baseUrl}/master-data/province";
            $res = $this->client()->get($url)->throw()->json();
            return $res['data'] ?? [];
        });

        $province = collect($provinces)->first(function ($p) use ($addr) {
            return str_contains($addr, mb_strtolower($p['ProvinceName'], 'UTF-8'));
        });

        if (!$province) {
            throw new \RuntimeException('Không tìm thấy tỉnh/thành trong địa chỉ.');
        }

        $provinceId = $province['ProvinceID'];

        // --- Lấy quận/huyện ---
        $districts = Cache::remember("ghn.districts.$provinceId", 86400, function () use ($provinceId) {
            $url = "{$this->baseUrl}/master-data/district";
            $res = $this->client()->get($url, ['province_id' => $provinceId])->throw()->json();
            return $res['data'] ?? [];
        });

        $district = collect($districts)->first(function ($d) use ($addr) {
            return str_contains($addr, mb_strtolower($d['DistrictName'], 'UTF-8'));
        });

        if (!$district) {
            throw new \RuntimeException('Không tìm thấy quận/huyện trong địa chỉ.');
        }

        $districtId = $district['DistrictID'];

        // --- Lấy phường/xã ---
        $wards = Cache::remember("ghn.wards.$districtId", 86400, function () use ($districtId) {
            $url = "{$this->baseUrl}/master-data/ward";
            $res = $this->client()->get($url, ['district_id' => $districtId])->throw()->json();
            return $res['data'] ?? [];
        });

        $ward = collect($wards)->first(function ($w) use ($addr) {
            return str_contains($addr, mb_strtolower($w['WardName'], 'UTF-8'));
        });

        if (!$ward) {
            throw new \RuntimeException('Không tìm thấy phường/xã trong địa chỉ.');
        }

        return [
            'province_id'    => $provinceId,
            'to_district_id' => $districtId,
            'to_ward_code'   => $ward['WardCode'],
        ];
    }


}