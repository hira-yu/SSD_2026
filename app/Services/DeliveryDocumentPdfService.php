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
    private bool $forcePortableRenderer;

    /** @var array<int, int> */
    private array $unicodeToCid = [];

    /** @var array<int, int> */
    private array $cidToUnicode = [];

    /** @var array<int, int> */
    private array $cidToGlyph = [];

    /** @var array<int, int> */
    private array $cidWidths = [];

    /** @var array<string, array{offset: int, length: int}> */
    private array $fontTables = [];

    /** @var array<int, array{start: int, end: int, glyph: int}> */
    private array $cmapGroups = [];

    private string $fontBinary = '';
    private int $fontUnitsPerEm = 1000;
    private int $fontAscent = 880;
    private int $fontDescent = -120;
    private int $fontXMin = -1000;
    private int $fontYMin = -1000;
    private int $fontXMax = 3000;
    private int $fontYMax = 3000;
    private int $numberOfHMetrics = 0;

    public function __construct(bool $forcePortableRenderer = false)
    {
        $this->fontPath = base_path('public/assets/fonts/NotoSansJP-VF.ttf');
        $this->forcePortableRenderer = $forcePortableRenderer;
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    public function generate(array $order, array $items): string
    {
        if (!is_file($this->fontPath)) {
            throw new RuntimeException('PDF生成に必要な日本語フォントが見つかりません。');
        }

        if ($this->forcePortableRenderer || !extension_loaded('gd') || !function_exists('imagettftext')) {
            return $this->generatePortablePdf($order, $items);
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
     * GDが導入されていない本番環境でも動作する、埋め込みフォント方式のPDF生成処理。
     *
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    private function generatePortablePdf(array $order, array $items): string
    {
        $this->loadPortableFont();
        $itemPages = array_chunk($items, self::ITEMS_PER_PAGE) ?: [[]];
        $pageStreams = [];
        $pageCount = count($itemPages);

        foreach ($itemPages as $pageIndex => $pageItems) {
            $pageStreams[] = $this->portablePageStream(
                $order,
                $pageItems,
                $pageIndex + 1,
                $pageCount,
                $pageIndex === $pageCount - 1
            );
        }

        return $this->buildPortablePdf($pageStreams);
    }

    private function loadPortableFont(): void
    {
        $font = file_get_contents($this->fontPath);

        if ($font === false || strlen($font) < 12) {
            throw new RuntimeException('PDF生成に必要な日本語フォントを読み込めません。');
        }

        $this->fontBinary = $font;
        $tableCount = $this->fontUInt16(4);

        for ($index = 0; $index < $tableCount; $index++) {
            $position = 12 + ($index * 16);
            $tag = substr($font, $position, 4);
            $this->fontTables[$tag] = [
                'offset' => $this->fontUInt32($position + 8),
                'length' => $this->fontUInt32($position + 12),
            ];
        }

        foreach (['cmap', 'head', 'hhea', 'hmtx'] as $requiredTable) {
            if (!isset($this->fontTables[$requiredTable])) {
                throw new RuntimeException('日本語フォントの形式に対応していません。');
            }
        }

        $head = $this->fontTables['head']['offset'];
        $hhea = $this->fontTables['hhea']['offset'];
        $this->fontUnitsPerEm = max(1, $this->fontUInt16($head + 18));
        $this->fontXMin = $this->fontInt16($head + 36);
        $this->fontYMin = $this->fontInt16($head + 38);
        $this->fontXMax = $this->fontInt16($head + 40);
        $this->fontYMax = $this->fontInt16($head + 42);
        $this->fontAscent = $this->fontInt16($hhea + 4);
        $this->fontDescent = $this->fontInt16($hhea + 6);
        $this->numberOfHMetrics = $this->fontUInt16($hhea + 34);
        $this->loadCmapGroups();
    }

    private function loadCmapGroups(): void
    {
        $cmap = $this->fontTables['cmap']['offset'];
        $subtableCount = $this->fontUInt16($cmap + 2);
        $selectedOffset = null;

        for ($index = 0; $index < $subtableCount; $index++) {
            $record = $cmap + 4 + ($index * 8);
            $platform = $this->fontUInt16($record);
            $encoding = $this->fontUInt16($record + 2);
            $offset = $cmap + $this->fontUInt32($record + 4);
            $format = $this->fontUInt16($offset);

            if ($format === 12 && ($platform === 0 || ($platform === 3 && $encoding === 10))) {
                $selectedOffset = $offset;
                break;
            }
        }

        if ($selectedOffset === null) {
            throw new RuntimeException('日本語フォントのUnicodeマップに対応していません。');
        }

        $groupCount = $this->fontUInt32($selectedOffset + 12);

        for ($index = 0; $index < $groupCount; $index++) {
            $position = $selectedOffset + 16 + ($index * 12);
            $this->cmapGroups[] = [
                'start' => $this->fontUInt32($position),
                'end' => $this->fontUInt32($position + 4),
                'glyph' => $this->fontUInt32($position + 8),
            ];
        }
    }

    /**
     * @param array<string, mixed> $order
     * @param array<int, array<string, mixed>> $items
     */
    private function portablePageStream(array $order, array $items, int $pageNumber, int $pageCount, bool $isLastPage): string
    {
        $commands = [
            '1 1 1 rg 0 0 595.28 841.89 re f',
            '0.12 0.16 0.22 rg',
        ];
        $title = (string) ($order['payment_method'] ?? '') === 'cod' ? '納品書兼請求書' : '納品書';
        $this->portableText($commands, 'IPUT EC', 16, 42, 795, '0.90 0.23 0.13');
        $this->portableTextRight($commands, $title, 23, 553, 790, '0.12 0.16 0.22');
        $commands[] = '0.90 0.23 0.13 RG 1.4 w 42 776 m 553 776 l S';
        $this->portableText($commands, (string) ($order['customer_name'] ?? '') . ' 様', 15, 42, 742);
        $this->portableText($commands, (string) ($order['customer_address'] ?? ''), 10, 42, 720, '0.36 0.42 0.49');
        $this->portableText($commands, '発行日', 9, 360, 744, '0.36 0.42 0.49');
        $this->portableText($commands, date('Y年m月d日'), 10, 430, 744);
        $this->portableText($commands, 'ご注文番号', 9, 360, 725, '0.36 0.42 0.49');
        $this->portableText($commands, (string) ($order['order_no'] ?? ''), 10, 430, 725);
        $this->portableText($commands, 'ご注文日', 9, 360, 706, '0.36 0.42 0.49');
        $this->portableText($commands, $this->formatDate((string) ($order['order_date'] ?? '')), 10, 430, 706);

        $intro = $title === '納品書兼請求書'
            ? '下記の通り商品を納品し、ご請求申し上げます。'
            : '下記の通り商品を納品いたします。';
        $this->portableText($commands, $intro, 11, 42, 665);
        $commands[] = '0.96 0.97 0.98 rg 42 610 511 38 re f';
        $this->portableText($commands, '合計金額（税込）', 10, 54, 625, '0.36 0.42 0.49');
        $this->portableTextRight($commands, '¥' . number_format((int) ($order['total_amount'] ?? 0)), 17, 541, 620, '0.77 0.15 0.15');

        $tableTop = 580.0;
        $rowHeight = 34.0;
        $commands[] = '0.90 0.23 0.13 rg 42 ' . $this->pdfNumber($tableTop) . ' 511 28 re f';
        $this->portableText($commands, '商品名', 9, 54, $tableTop + 9, '1 1 1');
        $this->portableText($commands, '単価', 9, 354, $tableTop + 9, '1 1 1');
        $this->portableText($commands, '数量', 9, 430, $tableTop + 9, '1 1 1');
        $this->portableText($commands, '小計', 9, 491, $tableTop + 9, '1 1 1');
        $rowY = $tableTop - $rowHeight;

        foreach ($items as $item) {
            $commands[] = '0.80 0.84 0.89 RG 0.5 w 42 ' . $this->pdfNumber($rowY) . ' 511 ' . $this->pdfNumber($rowHeight) . ' re S';
            $this->portableText($commands, $this->portableEllipsis((string) ($item['product_name'] ?? ''), 26), 9, 54, $rowY + 12);
            $this->portableTextRight($commands, '¥' . number_format((int) ($item['unit_price'] ?? 0)), 9, 413, $rowY + 12);
            $this->portableTextRight($commands, number_format((int) ($item['quantity'] ?? 0)), 9, 472, $rowY + 12);
            $this->portableTextRight($commands, '¥' . number_format((int) ($item['line_total'] ?? 0)), 9, 541, $rowY + 12);
            $rowY -= $rowHeight;
        }

        if ($isLastPage) {
            $summaryY = min($rowY - 26, 248.0);
            $labels = ['商品小計', '手数料', '配送料', '合計金額'];
            $values = [
                (int) ($order['subtotal'] ?? 0),
                (int) ($order['fee'] ?? 0),
                (int) ($order['shipping_fee'] ?? 0),
                (int) ($order['total_amount'] ?? 0),
            ];

            foreach ($labels as $index => $label) {
                $y = $summaryY - ($index * 22);
                $color = $index === 3 ? '0.77 0.15 0.15' : '0.12 0.16 0.22';
                $size = $index === 3 ? 12 : 10;
                $this->portableText($commands, $label, $size, 392, $y, $color);
                $this->portableTextRight($commands, '¥' . number_format($values[$index]), $size, 553, $y, $color);
            }

            $noticeY = $summaryY - 116;
            $this->portableText($commands, 'お支払い方法: ' . (string) ($order['payment_method_label'] ?? ''), 10, 42, $noticeY);
            $notice = (string) ($order['payment_method'] ?? '') === 'cod'
                ? '合計金額を配送業者にお支払いください。'
                : '本書は商品の納品内容をご確認いただくための書類です。';
            $this->portableText($commands, $notice, 9, 42, $noticeY - 20, '0.36 0.42 0.49');
        }

        $commands[] = '0.80 0.84 0.89 RG 0.5 w 42 52 m 553 52 l S';
        $this->portableText($commands, 'IPUT EC', 10, 42, 32);
        $this->portableTextRight($commands, $pageNumber . ' / ' . $pageCount, 8, 553, 32, '0.36 0.42 0.49');

        return implode("\n", $commands);
    }

    /** @param array<int, string> $commands */
    private function portableText(array &$commands, string $text, float $size, float $x, float $y, string $color = '0.12 0.16 0.22'): void
    {
        $encoded = $this->portableEncode($text);
        $commands[] = sprintf(
            '%s rg BT /F1 %s Tf 1 0 0 1 %s %s Tm <%s> Tj ET',
            $color,
            $this->pdfNumber($size),
            $this->pdfNumber($x),
            $this->pdfNumber($y),
            strtoupper(bin2hex($encoded))
        );
    }

    /** @param array<int, string> $commands */
    private function portableTextRight(array &$commands, string $text, float $size, float $right, float $y, string $color = '0.12 0.16 0.22'): void
    {
        $width = $this->portableTextWidth($text, $size);
        $this->portableText($commands, $text, $size, $right - $width, $y, $color);
    }

    private function portableEncode(string $text): string
    {
        $encoded = '';

        foreach (mb_str_split($text) as $character) {
            $unicode = mb_ord($character, 'UTF-8');

            if (!isset($this->unicodeToCid[$unicode])) {
                $cid = count($this->unicodeToCid) + 1;

                if ($cid > 65535) {
                    throw new RuntimeException('PDFへ埋め込む文字数が上限を超えました。');
                }

                $glyph = $this->glyphForUnicode($unicode);
                $this->unicodeToCid[$unicode] = $cid;
                $this->cidToUnicode[$cid] = $unicode;
                $this->cidToGlyph[$cid] = $glyph;
                $this->cidWidths[$cid] = $this->glyphWidth($glyph);
            }

            $encoded .= pack('n', $this->unicodeToCid[$unicode]);
        }

        return $encoded;
    }

    private function portableTextWidth(string $text, float $size): float
    {
        $this->portableEncode($text);
        $width = 0;

        foreach (mb_str_split($text) as $character) {
            $cid = $this->unicodeToCid[mb_ord($character, 'UTF-8')];
            $width += $this->cidWidths[$cid];
        }

        return ($width / 1000) * $size;
    }

    private function portableEllipsis(string $text, int $maxCharacters): string
    {
        return mb_strlen($text) <= $maxCharacters
            ? $text
            : mb_substr($text, 0, max(1, $maxCharacters - 1)) . '…';
    }

    private function glyphForUnicode(int $unicode): int
    {
        $low = 0;
        $high = count($this->cmapGroups) - 1;

        while ($low <= $high) {
            $middle = intdiv($low + $high, 2);
            $group = $this->cmapGroups[$middle];

            if ($unicode < $group['start']) {
                $high = $middle - 1;
            } elseif ($unicode > $group['end']) {
                $low = $middle + 1;
            } else {
                return $group['glyph'] + ($unicode - $group['start']);
            }
        }

        return 0;
    }

    private function glyphWidth(int $glyph): int
    {
        $hmtx = $this->fontTables['hmtx']['offset'];
        $metricIndex = min(max(0, $glyph), max(0, $this->numberOfHMetrics - 1));
        $advanceWidth = $this->fontUInt16($hmtx + ($metricIndex * 4));

        return max(1, (int) round(($advanceWidth / $this->fontUnitsPerEm) * 1000));
    }

    /** @param array<int, string> $pageStreams */
    private function buildPortablePdf(array $pageStreams): string
    {
        $objects = [];
        $pageIds = [];
        $nextId = 3;

        foreach ($pageStreams as $stream) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageIds[] = $pageId;
            $objects[$contentId] = $this->pdfStream($stream);
        }

        $type0Id = $nextId++;
        $cidFontId = $nextId++;
        $descriptorId = $nextId++;
        $fontFileId = $nextId++;
        $cidMapId = $nextId++;
        $toUnicodeId = $nextId++;

        foreach ($pageStreams as $index => $stream) {
            $pageId = $pageIds[$index];
            $contentId = $pageId + 1;
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 %d 0 R >> >> /Contents %d 0 R >>',
                self::PDF_WIDTH,
                self::PDF_HEIGHT,
                $type0Id,
                $contentId
            );
        }

        $kids = implode(' ', array_map(static fn (int $id): string => $id . ' 0 R', $pageIds));
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageIds) . ' >>';
        $objects[$type0Id] = sprintf(
            '<< /Type /Font /Subtype /Type0 /BaseFont /NotoSansJP /Encoding /Identity-H /DescendantFonts [%d 0 R] /ToUnicode %d 0 R >>',
            $cidFontId,
            $toUnicodeId
        );
        $widths = implode(' ', array_map('strval', $this->cidWidths));
        $objects[$cidFontId] = sprintf(
            '<< /Type /Font /Subtype /CIDFontType2 /BaseFont /NotoSansJP /CIDSystemInfo << /Registry (Adobe) /Ordering (Identity) /Supplement 0 >> /FontDescriptor %d 0 R /CIDToGIDMap %d 0 R /DW 1000 /W [1 [%s]] >>',
            $descriptorId,
            $cidMapId,
            $widths
        );
        $objects[$descriptorId] = sprintf(
            '<< /Type /FontDescriptor /FontName /NotoSansJP /Flags 4 /FontBBox [%d %d %d %d] /ItalicAngle 0 /Ascent %d /Descent %d /CapHeight %d /StemV 80 /FontFile2 %d 0 R >>',
            $this->fontMetric($this->fontXMin),
            $this->fontMetric($this->fontYMin),
            $this->fontMetric($this->fontXMax),
            $this->fontMetric($this->fontYMax),
            $this->fontMetric($this->fontAscent),
            $this->fontMetric($this->fontDescent),
            $this->fontMetric($this->fontAscent),
            $fontFileId
        );
        $fontStream = $this->fontBinary;
        $fontFilter = '';

        if (function_exists('gzcompress')) {
            $compressedFont = gzcompress($this->fontBinary, 6);

            if ($compressedFont !== false) {
                $fontStream = $compressedFont;
                $fontFilter = ' /Filter /FlateDecode';
            }
        }

        $objects[$fontFileId] = '<< /Length ' . strlen($fontStream) . ' /Length1 ' . strlen($this->fontBinary) . $fontFilter . ">>\nstream\n" . $fontStream . "\nendstream";
        $objects[$cidMapId] = $this->pdfStream($this->cidToGidMap());
        $objects[$toUnicodeId] = $this->pdfStream($this->toUnicodeCmap());
        ksort($objects);

        return $this->assemblePdfObjects($objects);
    }

    private function cidToGidMap(): string
    {
        $map = pack('n', 0);

        foreach ($this->cidToGlyph as $glyph) {
            $map .= pack('n', min(65535, $glyph));
        }

        return $map;
    }

    private function toUnicodeCmap(): string
    {
        $lines = [
            '/CIDInit /ProcSet findresource begin',
            '12 dict begin',
            'begincmap',
            '/CIDSystemInfo << /Registry (Adobe) /Ordering (UCS) /Supplement 0 >> def',
            '/CMapName /NotoSansJP-UCS def',
            '/CMapType 2 def',
            '1 begincodespacerange',
            '<0000> <FFFF>',
            'endcodespacerange',
        ];

        foreach (array_chunk($this->cidToUnicode, 100, true) as $chunk) {
            $lines[] = count($chunk) . ' beginbfchar';

            foreach ($chunk as $cid => $unicode) {
                $lines[] = sprintf('<%04X> <%s>', $cid, $this->unicodeUtf16Hex($unicode));
            }

            $lines[] = 'endbfchar';
        }

        $lines[] = 'endcmap';
        $lines[] = 'CMapName currentdict /CMap defineresource pop';
        $lines[] = 'end';
        $lines[] = 'end';

        return implode("\n", $lines);
    }

    private function unicodeUtf16Hex(int $unicode): string
    {
        if ($unicode <= 0xFFFF) {
            return sprintf('%04X', $unicode);
        }

        $unicode -= 0x10000;
        return sprintf('%04X%04X', 0xD800 + ($unicode >> 10), 0xDC00 + ($unicode & 0x3FF));
    }

    /** @param array<int, string> $objects */
    private function assemblePdfObjects(array $objects): string
    {
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $id => $object) {
            $offsets[$id] = strlen($pdf);
            $pdf .= $id . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $objectCount = max(array_keys($objects)) + 1;
        $pdf .= "xref\n0 " . $objectCount . "\n0000000000 65535 f \n";

        for ($id = 1; $id < $objectCount; $id++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$id] ?? 0);
        }

        $pdf .= "trailer\n<< /Size " . $objectCount . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function pdfStream(string $stream): string
    {
        return '<< /Length ' . strlen($stream) . ">>\nstream\n" . $stream . "\nendstream";
    }

    private function fontMetric(int $value): int
    {
        return (int) round(($value / $this->fontUnitsPerEm) * 1000);
    }

    private function fontUInt16(int $offset): int
    {
        $value = unpack('nvalue', substr($this->fontBinary, $offset, 2));

        return (int) ($value['value'] ?? 0);
    }

    private function fontInt16(int $offset): int
    {
        $value = $this->fontUInt16($offset);

        return $value >= 0x8000 ? $value - 0x10000 : $value;
    }

    private function fontUInt32(int $offset): int
    {
        $value = unpack('Nvalue', substr($this->fontBinary, $offset, 4));

        return (int) ($value['value'] ?? 0);
    }

    private function pdfNumber(float $value): string
    {
        return rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
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
