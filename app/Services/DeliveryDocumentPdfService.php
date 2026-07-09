<?php

declare(strict_types=1);

class DeliveryDocumentPdfService
{
    private const PAGE_WIDTH = 1240;
    private const PAGE_HEIGHT = 1754;
    private const PDF_WIDTH = 595.28;
    private const PDF_HEIGHT = 841.89;
    private const ITEMS_PER_PAGE = 8;

    private string $fontPath;

    public function __construct()
    {
        $this->fontPath = base_path('public/assets/fonts/NotoSansJP-VF.ttf');
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    public function generate(array $order, array $items): string
    {
        if (!extension_loaded('gd') || !function_exists('imagettftext')) {
            throw new RuntimeException('PDF生成に必要なGD拡張が利用できません。');
        }

        if (!is_file($this->fontPath)) {
            throw new RuntimeException('PDF生成に必要な日本語フォントが見つかりません。');
        }

        $itemPages = array_chunk($items, self::ITEMS_PER_PAGE);

        if ($itemPages === []) {
            $itemPages = [[]];
        }

        $pageCount = count($itemPages);
        $jpegPages = [];

        foreach ($itemPages as $pageIndex => $pageItems) {
            $jpegPages[] = $this->renderPage(
                $order,
                $pageItems,
                $pageIndex + 1,
                $pageCount,
                $pageIndex === $pageCount - 1
            );
        }

        return $this->buildPdf($jpegPages);
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    private function renderPage(array $order, array $items, int $pageNumber, int $pageCount, bool $isLastPage): string
    {
        $image = imagecreatetruecolor(self::PAGE_WIDTH, self::PAGE_HEIGHT);

        if ($image === false) {
            throw new RuntimeException('PDFページの作成に失敗しました。');
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $ink = imagecolorallocate($image, 31, 41, 55);
        $muted = imagecolorallocate($image, 91, 105, 120);
        $line = imagecolorallocate($image, 203, 213, 225);
        $soft = imagecolorallocate($image, 244, 247, 250);
        $accent = imagecolorallocate($image, 230, 58, 34);
        $totalColor = imagecolorallocate($image, 196, 37, 37);
        imagefilledrectangle($image, 0, 0, self::PAGE_WIDTH, self::PAGE_HEIGHT, $white);

        $documentTitle = (string) ($order['payment_method'] ?? '') === 'cod'
            ? '納品書兼請求書'
            : '納品書';
        $margin = 82;

        $this->text($image, 'IPUT EC', 25, $margin, 92, $accent);
        $this->textRight($image, $documentTitle, 38, self::PAGE_WIDTH - $margin, 100, $ink);
        imageline($image, $margin, 125, self::PAGE_WIDTH - $margin, 125, $accent);

        $this->text($image, (string) ($order['customer_name'] ?? '') . ' 様', 26, $margin, 185, $ink);
        $addressY = $this->wrappedText(
            $image,
            (string) ($order['customer_address'] ?? ''),
            17,
            $margin,
            225,
            570,
            31,
            $muted
        );
        imageline($image, $margin, $addressY + 10, 650, $addressY + 10, $line);

        $metaX = 740;
        $this->text($image, '発行日', 16, $metaX, 172, $muted);
        $this->text($image, date('Y年m月d日'), 17, 890, 172, $ink);
        $this->text($image, 'ご注文番号', 16, $metaX, 210, $muted);
        $this->text($image, (string) ($order['order_no'] ?? ''), 17, 890, 210, $ink);
        $this->text($image, 'ご注文日', 16, $metaX, 248, $muted);
        $this->text($image, $this->formatDate((string) ($order['order_date'] ?? '')), 17, 890, 248, $ink);

        $intro = $documentTitle === '納品書兼請求書'
            ? '下記の通り商品を納品し、ご請求申し上げます。'
            : '下記の通り商品を納品いたします。';
        $this->text($image, $intro, 18, $margin, 330, $ink);

        imagefilledrectangle($image, $margin, 365, self::PAGE_WIDTH - $margin, 425, $soft);
        $this->text($image, '合計金額（税込）', 17, $margin + 22, 404, $muted);
        $this->textRight(
            $image,
            '¥' . number_format((int) ($order['total_amount'] ?? 0)),
            28,
            self::PAGE_WIDTH - $margin - 22,
            407,
            $totalColor
        );

        $tableTop = 475;
        $rowHeight = 68;
        $columns = [$margin, 245, 770, 900, 1015, self::PAGE_WIDTH - $margin];
        imagefilledrectangle($image, $margin, $tableTop, self::PAGE_WIDTH - $margin, $tableTop + 58, $accent);
        $headers = ['商品名', '単価', '数量', '小計'];

        foreach ($headers as $index => $header) {
            $this->text($image, $header, 15, $columns[$index] + 12, $tableTop + 38, $white);
        }

        $rowY = $tableTop + 58;

        foreach ($items as $item) {
            imagerectangle($image, $margin, $rowY, self::PAGE_WIDTH - $margin, $rowY + $rowHeight, $line);
            $this->textEllipsis($image, (string) ($item['product_name'] ?? ''), 14, $columns[1] + 12, $rowY + 42, 490, $ink);
            $this->textRight($image, '¥' . number_format((int) ($item['unit_price'] ?? 0)), 14, $columns[3] - 12, $rowY + 42, $ink);
            $this->textRight($image, number_format((int) ($item['quantity'] ?? 0)), 14, $columns[4] - 12, $rowY + 42, $ink);
            $this->textRight($image, '¥' . number_format((int) ($item['line_total'] ?? 0)), 14, $columns[5] - 12, $rowY + 42, $ink);
            $rowY += $rowHeight;
        }

        if ($isLastPage) {
            $summaryTop = max($rowY + 40, 1030);
            $labels = ['商品小計', '手数料', '配送料', '合計金額'];
            $values = [
                (int) ($order['subtotal'] ?? 0),
                (int) ($order['fee'] ?? 0),
                (int) ($order['shipping_fee'] ?? 0),
                (int) ($order['total_amount'] ?? 0),
            ];

            foreach ($labels as $index => $label) {
                $y = $summaryTop + ($index * 46);
                $color = $index === 3 ? $totalColor : $ink;
                $size = $index === 3 ? 20 : 16;
                $this->text($image, $label, $size, 780, $y, $color);
                $this->textRight($image, '¥' . number_format($values[$index]), $size, self::PAGE_WIDTH - $margin, $y, $color);
            }

            $noticeY = $summaryTop + 230;
            $this->text($image, 'お支払い方法: ' . (string) ($order['payment_method_label'] ?? ''), 16, $margin, $noticeY, $ink);

            if ((string) ($order['payment_method'] ?? '') === 'cod') {
                $this->text($image, '合計金額を配送業者にお支払いください。', 16, $margin, $noticeY + 38, $totalColor);
                $this->text($image, 'なお、お支払いの証明となりますので、受領領収書は大切に保管ください。', 16, $margin, $noticeY + 76, $totalColor);
            } else {
                $this->text($image, '本書は商品の納品内容をご確認いただくための書類です。', 15, $margin, $noticeY + 38, $muted);
            }
        }

        imageline($image, $margin, 1610, self::PAGE_WIDTH - $margin, 1610, $line);
        $this->text($image, 'IPUT EC', 17, $margin, 1650, $ink);
        $this->text($image, 'お問い合わせの際は注文番号をお知らせください。', 14, $margin, 1682, $muted);
        $this->textRight($image, $pageNumber . ' / ' . $pageCount, 14, self::PAGE_WIDTH - $margin, 1682, $muted);

        ob_start();
        imagejpeg($image, null, 92);
        $jpeg = (string) ob_get_clean();

        return $jpeg;
    }

    /**
     * @param resource|\GdImage $image
     */
    private function text($image, string $text, int $size, int $x, int $y, int $color): void
    {
        imagettftext($image, $size, 0, $x, $y, $color, $this->fontPath, $text);
    }

    /**
     * @param resource|\GdImage $image
     */
    private function textRight($image, string $text, int $size, int $right, int $y, int $color): void
    {
        $this->text($image, $text, $size, $right - $this->textWidth($text, $size), $y, $color);
    }

    /**
     * @param resource|\GdImage $image
     */
    private function textEllipsis($image, string $text, int $size, int $x, int $y, int $maxWidth, int $color): void
    {
        if ($this->textWidth($text, $size) <= $maxWidth) {
            $this->text($image, $text, $size, $x, $y, $color);
            return;
        }

        while ($text !== '' && $this->textWidth($text . '…', $size) > $maxWidth) {
            $text = mb_substr($text, 0, -1);
        }

        $this->text($image, $text . '…', $size, $x, $y, $color);
    }

    /**
     * @param resource|\GdImage $image
     */
    private function wrappedText($image, string $text, int $size, int $x, int $y, int $maxWidth, int $lineHeight, int $color): int
    {
        $line = '';
        $currentY = $y;

        foreach (mb_str_split($text) as $character) {
            if ($this->textWidth($line . $character, $size) <= $maxWidth) {
                $line .= $character;
                continue;
            }

            $this->text($image, $line, $size, $x, $currentY, $color);
            $line = $character;
            $currentY += $lineHeight;
        }

        if ($line !== '') {
            $this->text($image, $line, $size, $x, $currentY, $color);
        }

        return $currentY;
    }

    private function textWidth(string $text, int $size): int
    {
        $box = imagettfbbox($size, 0, $this->fontPath, $text);

        return is_array($box) ? abs((int) $box[2] - (int) $box[0]) : 0;
    }

    private function formatDate(string $value): string
    {
        $timestamp = strtotime($value);

        return $timestamp === false ? $value : date('Y年m月d日', $timestamp);
    }

    /**
     * @param array<int, string> $jpegPages
     */
    private function buildPdf(array $jpegPages): string
    {
        $objects = [];
        $pageObjectIds = [];
        $nextObjectId = 3;

        foreach ($jpegPages as $jpeg) {
            $pageId = $nextObjectId++;
            $contentId = $nextObjectId++;
            $imageId = $nextObjectId++;
            $pageObjectIds[] = $pageId;
            $content = sprintf(
                "q %.2F 0 0 %.2F 0 0 cm /PageImage Do Q",
                self::PDF_WIDTH,
                self::PDF_HEIGHT
            );

            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /XObject << /PageImage %d 0 R >> >> /Contents %d 0 R >>',
                self::PDF_WIDTH,
                self::PDF_HEIGHT,
                $imageId,
                $contentId
            );
            $objects[$contentId] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "\nendstream";
            $objects[$imageId] = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                strlen($jpeg),
                $jpeg
            );
        }

        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageObjectIds));
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageObjectIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $objectCount . "\n";
        $pdf .= "0000000000 65535 f \n";

        for ($id = 1; $id < $objectCount; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . $objectCount . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }
}
