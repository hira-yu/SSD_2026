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
        [$imagePath, $imageErrors] = $this->storeUploadedImage($_FILES['product_image'] ?? null);
        $errors = array_merge($errors, $imageErrors);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        if ($imagePath !== null) {
            $data['image_path'] = $imagePath;
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
    public function updateProduct(int $productId, array $input, array $currentProduct): array
    {
        [$data, $errors] = $this->normalizeProductInput($input, $currentProduct);
        [$imagePath, $imageErrors] = $this->storeUploadedImage($_FILES['product_image'] ?? null);
        $errors = array_merge($errors, $imageErrors);

        if ($errors !== []) {
            return ['ok' => false, 'errors' => $errors];
        }

        if ($imagePath !== null) {
            $data['image_path'] = $imagePath;
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
    public function normalizeProductInput(array $input, ?array $currentProduct = null): array
    {
        $data = [
            'product_no' => trim((string) ($input['product_no'] ?? '')),
            'name' => trim((string) ($input['name'] ?? '')),
            'price' => $this->integerInput($input['price'] ?? ''),
            'category' => trim((string) ($input['category'] ?? '')),
            'maker' => trim((string) ($input['maker'] ?? '')),
            'image_path' => trim((string) ($currentProduct['image_path'] ?? '')),
            'stock_quantity_1' => (int) ($currentProduct['stock_quantity_1'] ?? 0),
            'stock_quantity_2' => (int) ($currentProduct['stock_quantity_2'] ?? 0),
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

        if ($data['sale_price'] !== null && $data['sale_price'] >= $data['price']) {
            $errors[] = 'セール価格は通常価格より低い金額を入力してください。';
        }

        if (!$this->validDateRange($data['sale_starts_at'], $data['sale_ends_at'])) {
            $errors[] = 'セール期間の開始日時は終了日時以前にしてください。';
        }

        if (!$this->validDateRange($data['available_from'], $data['available_until'])) {
            $errors[] = '販売期間の開始日時は終了日時以前にしてください。';
        }

        foreach (['sale_starts_at' => 'セール開始', 'sale_ends_at' => 'セール終了', 'available_from' => '販売開始', 'available_until' => '販売終了'] as $field => $label) {
            if ($data[$field] !== null && !$this->isValidDateTime((string) $data[$field])) {
                $errors[] = $label . 'は 2026/07/08 09:00 の形式で入力してください。';
            }
        }

        return [$data, array_values(array_unique($errors))];
    }

    /**
     * @param array<string, mixed>|null $file
     * @return array{0: string|null, 1: array<int, string>}
     */
    private function storeUploadedImage(?array $file): array
    {
        if ($file === null || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return [null, []];
        }

        if ((int) ($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            return [null, ['画像アップロードに失敗しました。ファイルサイズや形式をご確認ください。']];
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');

        if ($tmpName === '' || !is_uploaded_file($tmpName)) {
            return [null, ['アップロード画像を確認できませんでした。']];
        }

        $binary = file_get_contents($tmpName);

        if ($binary === false) {
            return [null, ['アップロード画像を読み込めませんでした。']];
        }

        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return [null, ['このPHP環境では商品画像のWebP変換に対応していません。']];
        }

        $image = @imagecreatefromstring($binary);

        if ($image === false) {
            return [null, ['商品画像はPNG、JPEG、WebPなどの画像ファイルを選択してください。']];
        }

        $directory = base_path('public/assets/img/products/generated');

        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            return [null, ['商品画像の保存先を作成できませんでした。']];
        }

        $fileName = hash('sha256', $binary . random_bytes(16)) . '.webp';
        $path = $directory . DIRECTORY_SEPARATOR . $fileName;

        if (!imagewebp($image, $path, 82)) {
            return [null, ['商品画像をWebP形式で保存できませんでした。']];
        }

        return ['assets/img/products/generated/' . $fileName, []];
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

        $value = str_replace(['T', '/'], [' ', '-'], $value);

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1) {
            return $value . ' 00:00:00';
        }

        if (preg_match('/^(\d{4}-\d{2}-\d{2}) (\d{1,2}):(\d{2})$/', $value, $matches) === 1) {
            return sprintf('%s %02d:%s:00', $matches[1], (int) $matches[2], $matches[3]);
        }

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

        $value = substr($value, 0, 16);

        return str_replace('-', '/', $value);
    }

    private function validDateRange(?string $startsAt, ?string $endsAt): bool
    {
        if ($startsAt === null || $endsAt === null) {
            return true;
        }

        if (!$this->isValidDateTime($startsAt) || !$this->isValidDateTime($endsAt)) {
            return true;
        }

        return new DateTimeImmutable($startsAt) <= new DateTimeImmutable($endsAt);
    }

    private function isValidDateTime(string $value): bool
    {
        $dateTime = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $value);
        $errors = DateTimeImmutable::getLastErrors();

        return $dateTime instanceof DateTimeImmutable
            && ($errors === false || ((int) $errors['warning_count'] === 0 && (int) $errors['error_count'] === 0))
            && $dateTime->format('Y-m-d H:i:s') === $value;
    }
}
