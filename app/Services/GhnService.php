<?php
namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class GhnService
{
    public function __construct() {
        $this->baseUrl = rtrim(config('services.ghn.base_url',
                          'https://online-gateway.ghn.vn/shiip/public-api'), '/');
    }

    protected function client()
    {
        return Http::withHeaders([
            'Token'  => config('services.ghn.token'),
            'ShopId' => config('services.ghn.shop_id'),
            'Content-Type' => 'application/json',
        ])->acceptJson();
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

        $res = $this->client()->post($url, $payload);

        if ($res->failed()) {
            // Ném lỗi chi tiết để caller biết lý do
            throw new RequestException($res);
        }

        $json = $res->json();
        if (($json['code'] ?? 0) !== 200) {
            throw new \RuntimeException('GHN available-services error: ' . ($json['message'] ?? 'Unknown'));
        }

        // Theo GHN, danh sách dịch vụ nằm ở data (mảng)
        return (array) ($json['data'] ?? []);
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
}