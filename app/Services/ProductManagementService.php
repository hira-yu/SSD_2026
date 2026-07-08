<?php

declare(strict_types=1);

class ProductManagementService
{
    private ProductRepository $products;

    public function __construct()
    {
        $this->products = new ProductRepository();
    }

    /**
     * @return array<string, mixed>
     */
    public function indexData(?string $productNo, ?string $name): array
    {
        $normalizedProductNo = trim((string) $productNo);
        $normalizedName = trim((string) $name);

        return [
            'filters' => [
                'product_no' => $normalizedProductNo,
                'name' => $normalizedName,
            ],
            'products' => $this->products->searchForReceptionist($normalizedProductNo, $normalizedName),
        ];
    }

    public function findProduct(int $productId): ?array
    {
        return $this->products->findById($productId);
    }

    /**
     * @return array<string, mixed>
     */
    public function blankForm(): array
    {
        return [
            'product_no' => '',
            'name' => '',
            'price' => '',
            'category' => '',
            'maker' => '',
            'image_path' => '',
            'stock_quantity_1' => '0',
            'stock_quantity_2' => '0',
            'sale_price' => '',
            'sale_starts_at' => '',
            'sale_ends_at' => '',
            'available_from' => '',
            'available_until' => '',
        ];
    }

    /**
     * @param array<string, mixed> $product
     * @return array<string, mixed>
     */
    public function formFromProduct(array $product): array
    {
        return [
            'product_no' => (string) ($product['product_no'] ?? ''),
            'name' => (string) ($product['name'] ?? ''),
            'price' => (string) ($product['price'] ?? ''),
            'category' => (string) ($product['category'] ?? ''),
            'maker' => (string) ($product['maker'] ?? ''),
            'image_path' => (string) ($product['image_path'] ?? ''),
            'stock_quantity_1' => (string) ($product['stock_quantity_1'] ?? '0'),
            'stock_quantity_2' => (string) ($product['stock_quantity_2'] ?? '0'),
            'sale_price' => (string) ($product['sale_price'] ?? ''),
            'sale_starts_at' => $this->datetimeForInput((string) ($product['sale_starts_at'] ?? '')),
            'sale_ends_at' => $this->datetimeForInput((string) ($product['sale_ends_at'] ?? '')),
            'available_from' => $this->datetimeForInput((string) ($product['available_from'] ?? '')),
            'available_until' => $this->datetimeForInput((string) ($product['available_until'] ?? '')),
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, errors: array<int, string>, product_id?: int}
     */
    public function createProduct(array $input): array
    {
        [$data, $errors] = $this->normalizeProductInput($input);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $productId = $this->products->create($data);
        } catch (Throwable $exception) {
            app_log('Product create failed', ['message' => $exception->getMessage()]);
            return ['ok' => false, 'errors' => ['商品の登録に失敗しました。商品番号の重複などをご確認ください。']];
        }

        return ['ok' => true, 'errors' => [], 'product_id' => $productId];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, errors: array<int, string>}
     */
    public function updateProduct(int $productId, array $input): array
    {
        [$data, $errors] = $this->normalizeProductInput($input);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        try {
            $this->products->update($productId, $data);
        } catch (Throwable $exception) {
            app_log('Product update failed', ['product_id' => $productId, 'message' => $exception->getMessage()]);
            return ['ok' => false, 'errors' => ['商品の更新に失敗しました。商品番号の重複などをご確認ください。']];
        }

        return ['ok' => true, 'errors' => []];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{ok: bool, errors: array<int, string>, quantity: int}
     */
    public function receiveStock(int $productId, array $input): array
    {
        $quantity = (int) preg_replace('/[^\d]/', '', (string) ($input['quantity'] ?? ''));

        if ($quantity < 1) {
            return ['ok' => false, 'errors' => ['入庫数量は1以上で入力してください。'], 'quantity' => 0];
        }

        if (!$this->products->receiveStock($productId, $quantity)) {
            return ['ok' => false, 'errors' => ['在庫の更新に失敗しました。'], 'quantity' => $quantity];
        }

        return ['ok' => true, 'errors' => [], 'quantity' => $quantity];
    }

    /**
     * @param array<string, mixed> $input
     * @return array{0: array<string, mixed>, 1: array<int, string>}
     */
    public function normalizeProductInput(array $input): array
    {
        $data = [
            'product_no' => trim((string) ($input['product_no'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
            'price' => $this->integerInput($input['price'] ?? ''),
            'category' => trim((string) ($input['category'] ?? '')),
            'maker' => trim((string) ($input['maker'] ?? '')),
            'image_path' => trim((string) ($input['image_path'] ?? '')),
            'stock_quantity_1' => $this->integerInput($input['stock_quantity_1'] ?? ''),
            'stock_quantity_2' => $this->integerInput($input['stock_quantity_2'] ?? ''),
            'sale_price' => $this->nullableIntegerInput($input['sale_price'] ?? ''),
            'sale_starts_at' => $this->normalizeDateTime($input['sale_starts_at'] ?? ''),
            'sale_ends_at' => $this->normalizeDateTime($input['sale_ends_at'] ?? ''),
            'available_from' => $this->normalizeDateTime($input['available_from'] ?? ''),
            'available_until' => $this->normalizeDateTime($input['available_until'] ?? ''),
        ];
        $errors = [];

        foreach (['product_no' => '商品番号', 'name' => '商品名', 'category' => 'カテゴリ', 'maker' => 'メーカー'] as $field => $label) {
            if ($data[$field] === '') {
                $errors[] = $label . 'を入力してください。';
            }
        }

        if ($data['price'] < 0) {
            $errors[] = '価格は0以上で入力してください。';
        }

        if ($data['stock_quantity_1'] < 0 || $data['stock_quantity_2'] < 0) {
            $errors[] = '在庫数は0以上で入力してください。';
        }

        if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
            $errors[] = 'セール価格は通常価格より低い金額を入力してください。';
        }

        if (!$this->validDateRange($data['sale_starts_at'], $data['sale_ends_at'])) {
            $errors[] = 'セール期間の開始日時は終了日時以前にしてください。';
        }

        if (!$this->validDateRange($data['available_from'], $data['available_until'])) {
            $errors[] = '販売期間の開始日時は終了日時以前にしてください。';
        }

        return [$data, array_values(array_unique($errors))];
    }

    private function integerInput(mixed $value): int
    {
        $value = preg_replace('/[^\d]/', '', (string) $value) ?? '';

        return $value === '' ? 0 : (int) $value;
    }

    private function nullableIntegerInput(mixed $value): ?int
    {
        $value = preg_replace('/[^\d]/', '', (string) $value) ?? '';

        return $value === '' ? null : (int) $value;
    }

    private function normalizeDateTime(mixed $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        $value = str_replace('T', ' ', $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value) === 1) {
            return $value . ':00';
        }

        return $value;
    }

    private function datetimeForInput(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        return str_replace(' ', 'T', substr($value, 0, 16));
    }

    private function validDateRange(?string $startsAt, ?string $endsAt): bool
    {
        if ($startsAt === null || $endsAt === null) {
            return true;
        }

        return new DateTimeImmutable($startsAt) <= new DateTimeImmutable($endsAt);
    }
}
