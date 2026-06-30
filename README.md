# 通信販売システム

授業内プロジェクト向けの通信販売システムです。  
このリポジトリでは、機能実装に入る前段として、素の PHP 8.3 系で動かす軽量 MVC の土台を作成しています。

## プロジェクト概要

- ネット購入者、注文受付係、会計係、商品発送係を想定した通信販売システム
- Web フレームワークは使わず、`public` 配下を公開ディレクトリとする構成
- ローカル開発では SQLite、本番相当の発表デモ環境では MariaDB を切り替えて利用

## 採用技術

- PHP 8.3 系
- PDO
- HTML / CSS / JavaScript
- セッション管理: `$_SESSION`
- ローカル DB: SQLite
- 発表デモ DB: MariaDB 10.11 系

## ディレクトリ構成

```text
tsuhan-system/
├── public/
│   ├── .htaccess
│   ├── index.php
│   └── assets/
├── app/
│   ├── config/
│   ├── core/
│   ├── Controllers/
│   ├── Services/
│   ├── Repositories/
│   ├── Views/
│   ├── bootstrap.php
│   ├── helpers.php
│   └── db.php
├── database/
│   ├── schema.sqlite.sql
│   ├── schema.mysql.sql
│   ├── seed.sqlite.sql
│   ├── seed.mysql.sql
│   └── local.sqlite
├── docs/
│   └── quickwbs-api-notes.md
├── logs/
├── scripts/
│   ├── quickwbs_docs.php
│   ├── quickwbs_check.php
│   ├── quickwbs_create_task.php
│   └── quickwbs_update_task.php
├── storage/
├── .env.example
├── .gitignore
├── README.md
└── router.php
```

## ローカル環境での起動方法

一発で起動する場合は、次のコマンドを使います。

```bash
sh scripts/dev.sh
```

このスクリプトは次を自動で行います。

- `.env` がなければ `.env.example` から作成
- `DB_DRIVER=sqlite` の場合、`users` テーブルがなければ SQLite を初期化
- PHP 内蔵サーバを `http://localhost:8000` で起動

ポートやホストを変えたい場合は、環境変数で上書きできます。

```bash
APP_HOST=0.0.0.0 APP_PORT=8080 sh scripts/dev.sh
```

## .env の設定方法

```env
APP_NAME=通信販売システム
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Tokyo

DB_DRIVER=sqlite
DB_SQLITE_PATH=database/local.sqlite

DB_HOST=localhost
DB_PORT=3306
DB_NAME=tsuhan_system
DB_USER=root
DB_PASSWORD=
DB_CHARSET=utf8mb4

SESSION_NAME=TSUHAN_SESSION

QUICKWBS_API_BASE=https://quickwbs.hirayu.jp/api
QUICKWBS_AGENT_DOCS_URL=https://quickwbs.hirayu.jp/api/agent/docs
QUICKWBS_API_TOKEN=your_ai_token_here
```

`QUICKWBS_API_TOKEN` は Git 管理せず、画面やログにも表示しない前提です。

## SQLite での初期化方法

```bash
cp .env.example .env
php -r '
$pdo = new PDO("sqlite:database/local.sqlite");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec(file_get_contents("database/schema.sqlite.sql"));
$pdo->exec(file_get_contents("database/seed.sqlite.sql"));
'
```

## MariaDB での初期化方法

1. `.env` の `DB_DRIVER=mysql` に切り替えます。
2. `DB_HOST`、`DB_PORT`、`DB_NAME`、`DB_USER`、`DB_PASSWORD` を実環境に合わせて設定します。
3. MariaDB 側でデータベースを作成してから SQL を流します。

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS tsuhan_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root -p tsuhan_system < database/schema.mysql.sql
mysql -u root -p tsuhan_system < database/seed.mysql.sql
```

## DB 接続確認方法

- トップページ: `http://localhost:8000/`
- 商品一覧・商品検索: `http://localhost:8000/products`
- DB 接続確認: `http://localhost:8000/system/db-check`

`/system/db-check` では以下を表示します。

- DB接続成功 / 失敗
- 使用中の `DB_DRIVER`
- DB種別
- 現在時刻
- `SELECT 1` の結果

失敗時の詳細は `logs/app.log` に記録し、画面には簡潔なメッセージのみ表示します。

## 担当者認証

- 担当者ログインURL: `http://localhost:8000/login`
- 注文受付係向け商品検索URL: `http://localhost:8000/staff/receptionist/products`
- ログアウト方法: `POST /logout`
- 認証方式: `users` テーブルの `login_id` と `password_hash` を使い、`password_verify()` で照合
- アクセス制御: 未ログイン時は `/login` へリダイレクトし、異なるロールの担当者画面は `403 Forbidden` を表示

### 初期ログインIDとパスワード

- 注文受付係: `reception01` / `reception123`
- 会計係: `account01` / `account123`
- 商品発送係: `shipper01` / `shipper123`

### ロールごとの遷移先

- `receptionist` → `/staff/receptionist`
- `accountant` → `/staff/accountant`
- `shipper` → `/staff/shipper`

### 動作確認手順

```bash
sh scripts/dev.sh
```

1. `http://localhost:8000/login` を開きます。
2. 上記の初期ログイン情報でログインします。
3. ロールごとの担当者トップへ自動遷移することを確認します。
4. 別ロールの `/staff/...` URL へ直接アクセスし、`403 Forbidden` になることを確認します。
5. ログアウトボタンを押し、再度担当者画面へアクセスすると `/login` へ戻ることを確認します。

## 商品一覧・商品検索

- 購入者向け商品一覧URL: `http://localhost:8000/products`
- 注文受付係向け商品検索URL: `http://localhost:8000/staff/receptionist/products`

### 検索条件

- 購入者向け: `name` を使った商品名の部分一致検索
- 注文受付係向け: `product_no` の部分一致検索、`name` の部分一致検索、両方指定時は AND 条件
- いずれも未指定時は全商品を表示

### 表示項目

- 購入者向け: 商品番号、商品名、単価、商品カテゴリ、メーカー名、在庫数量2、注文可能状態
- 注文受付係向け: 商品番号、商品名、単価、商品カテゴリ、メーカー名、在庫数量1、在庫数量2、注文可能状態

### 動作確認手順

1. `http://localhost:8000/products` を開き、商品一覧が表示されることを確認します。
2. `http://localhost:8000/products?name=マウス` のように検索し、商品名の部分一致で絞り込まれることを確認します。
3. `reception01 / reception123` でログインし、`http://localhost:8000/staff/receptionist/products` を開きます。
4. `product_no`、`name`、両方指定の各パターンで検索結果が変わることを確認します。
5. `account01` または `shipper01` で同URLへアクセスすると `403 Forbidden` になることを確認します。

## 会計処理

- 会計係向け注文検索URL: `http://localhost:8000/staff/accountant/orders`
- 利用可能ロール: `accountant`

### 検索条件

- `order_no`: 注文番号の部分一致検索
- `order_date`: 注文日の一致検索
- `customer_name`: 購入者氏名の部分一致検索
- `payment_status`: `unpaid` / `paid`
- 複数条件指定時は AND 条件

### 支払い状態更新方法

- 注文詳細または検索一覧から、未払い注文のみ `支払済へ更新` ボタンを押します
- 更新対象は `bank` / `convenience` / `cod`
- `paid` の注文は再更新しません

### 支払い方法ごとの扱い

- `bank`: 会計係が入金確認後に `paid` へ更新
- `convenience`: 会計係が入金確認後に `paid` へ更新
- `cod`: 会計係が入金確認後に `paid` へ更新
- `credit`: 表示はしますが、今回の会計更新対象外

### 動作確認手順

1. `account01 / account123` でログインし、`http://localhost:8000/staff/accountant/orders` を開きます。
2. 注文番号、注文日、購入者氏名、支払い状態の各条件で検索できることを確認します。
3. 未払い注文の詳細を開き、商品明細と金額内訳が表示されることを確認します。
4. `支払済へ更新` を実行し、支払い状態が `paid` になることを確認します。
5. 既に支払済の注文では更新ボタンが表示されないことを確認します。

## 電話/FAX注文登録

- 電話/FAX注文登録URL: `http://localhost:8000/staff/receptionist/orders/new`
- 利用可能ロール: `receptionist`

### 支払い方法

- `bank`: 銀行振込
- `convenience`: コンビニ決済
- `cod`: 代金引換

### 手数料・送料

- 配送料: `660円`
- コンビニ決済手数料: `220円`
- 代金引換手数料: `330円`
- 銀行振込手数料: `0円`

### 注文登録時の在庫更新仕様

- 電話/FAX注文は `orders.order_type = phone_fax` で登録
- `payment_status` は `unpaid`、`shipping_status` は `unshipped` で初期登録
- 注文登録と `products.stock_quantity_2` の減算は同一トランザクションで処理
- 在庫数量2を超える数量は登録不可

### 動作確認手順

1. `reception01 / reception123` でログインし、`http://localhost:8000/staff/receptionist/orders/new` を開きます。
2. 購入者情報、支払い方法、商品、数量を入力して確認画面へ進みます。
3. 単一商品と複数商品の両方で合計金額が正しく計算されることを確認します。
4. `bank` / `convenience` / `cod` の各支払い方法で、手数料と案内文が変わることを確認します。
5. 注文確定後、注文番号が採番され、`products.stock_quantity_2` が注文数量分だけ減算されることを確認します。

## 発送処理

- 商品発送係向け未発送注文一覧URL: `http://localhost:8000/staff/shipper/orders`
- 利用可能ロール: `shipper`

### 発送対象条件

- `bank`: `payment_status = paid` かつ `shipping_status = unshipped`
- `convenience`: `payment_status = paid` かつ `shipping_status = unshipped`
- `cod`: `shipping_status = unshipped`
- `credit`: `payment_status = paid` かつ `shipping_status = unshipped`
- `shipping_status = shipped` の注文は再発送不可

### 納品書・請求書表示仕様

- 銀行振込、コンビニ決済、クレジットカードは納品書情報のみ表示します
- 代金引換は納品書情報に加えて請求書情報も表示します
- PDF出力は行わず、HTML上に印刷しやすい帳票風ブロックを表示します

### 発送済更新時の在庫数量1更新仕様

- 発送済更新では `orders.shipping_status` を `shipped` に更新します
- 同一トランザクション内で `order_items` 数量分だけ `products.stock_quantity_1` を減算します
- 更新後、対象商品の `stock_quantity_1` と `stock_quantity_2` が一致しない場合はロールバックします
- 例: 注文登録後に `stock_quantity_1 = 12`, `stock_quantity_2 = 10` の商品は、発送済更新後に両方 `10` になります

### 在庫整合性確認CLI

```bash
php scripts/check_inventory_consistency.php
```

- `products` テーブルを走査して、負数在庫や `stock_quantity_1 < stock_quantity_2` を検出します
- 未発送注文の引当数量を集計し、`stock_quantity_1 - stock_quantity_2 = 未発送引当数量` を検証します
- 問題がなければ `在庫整合性OK` を表示します
- 問題がある場合は、商品番号ごとに不整合内容を表示します

### 動作確認手順

1. `shipper01 / shipper123` でログインし、`http://localhost:8000/staff/shipper/orders` を開きます。
2. 銀行振込・コンビニ決済の `paid` 注文、代金引換注文が発送可能として表示されることを確認します。
3. `unpaid` の銀行振込・コンビニ決済注文が支払い待ちとして別枠表示されることを確認します。
4. 注文詳細を開き、商品明細、納品書情報、代金引換では請求書情報も表示されることを確認します。
5. 発送可能な注文で `発送済へ更新` を実行し、`shipping_status` が `shipped` になり、対象商品の `stock_quantity_1` が減算されて `stock_quantity_2` と一致することを確認します。

## QuickWBS 連携

- QuickWBS API Base URL: `https://quickwbs.hirayu.jp/api`
- Agent Docs URL: `https://quickwbs.hirayu.jp/api/agent/docs`
- 必要な `.env` 項目
  - `QUICKWBS_API_BASE`
  - `QUICKWBS_AGENT_DOCS_URL`
  - `QUICKWBS_API_TOKEN`

### Agent Docs 取得方法

```bash
php scripts/quickwbs_docs.php
```

- `Authorization: Bearer <AIトークン>` 付きで `GET /api/agent/docs` を実行します
- 成功時は Agent 情報と主要エンドポイントを表示し、要約を `docs/quickwbs-api-notes.md` に保存します
- 生の Docs JSON を見たい場合は `php scripts/quickwbs_docs.php --raw` を使います

### 接続確認方法

```bash
php scripts/quickwbs_check.php
```

- 接続成功 / 失敗
- 使用中の API Base URL
- HTTP ステータスコード
- Agent の表示名
- 利用可能タスク数
- 実際に使う Agent API エンドポイント一覧

### タスク作成スクリプト

```bash
php scripts/quickwbs_create_task.php <parent_task_id> "タスク名" "説明"
php scripts/quickwbs_create_task.php <parent_task_id> "タスク名" "説明" --priority=medium --estimate-hours=4 --acceptance-criteria="..." --execute
```

- 実API仕様では `POST /api/agent/tasks/{task_id}/children` を使い、親タスク配下に子タスクを作成します
- `project_id` / `board_id` / `list_id` の直接指定は不要ですが、`parent_task_id` は必須です
- デフォルトは dry-run で、送信予定のエンドポイントと JSON を表示するだけです
- 実際に登録する場合だけ `--execute` を付けてください

### タスク更新スクリプト

```bash
php scripts/quickwbs_update_task.php <task_id> complete "完了メモ"
php scripts/quickwbs_update_task.php <task_id> report "進捗報告" --progress=60 --summary="..." --artifacts=a,b
php scripts/quickwbs_update_task.php <task_id> complete "完了メモ" --summary="..." --execute
```

- 実API仕様では以下の action を `POST /api/agent/tasks/{task_id}/{action}` で送ります
  - `claim`
  - `start`
  - `block`
  - `complete`
  - `report`
- `ready` / `in_progress` / `blocked` / `done` などの別名もスクリプト内で action に変換します
- こちらもデフォルトは dry-run で、`--execute` を付けたときだけ実更新します

### セキュリティ上の注意

- `.env` は Git 管理しません
- `QUICKWBS_API_TOKEN` を画面・ログ・例外メッセージに出さない実装にしています
- `Authorization` ヘッダー全体はログ出力しません
- QuickWBS Agent API で確認できたのは AI 向けエンドポイントです。トップレベルタスクの新規作成はこの Bearer Token API では未確認です

### 初期タスク一括登録

```bash
php scripts/quickwbs_seed_tasks.php "<ここに親タスクIDを入れる>"
php scripts/quickwbs_seed_tasks.php <parent_task_id>
php scripts/quickwbs_seed_tasks.php <parent_task_id> --execute
```

- `scripts/quickwbs_seed_tasks.php` は通信販売システム向けの初期タスク10件を、親タスク配下の子タスクとして登録する準備用CLIです
- デフォルトは dry-run で、10件分の作成予定と、1・2を完了扱いにする update 予定を表示します
- 親タスクIDがプレースホルダのまま、または未指定扱いの場合は `--execute` を拒否します
- 実登録時は 1. 開発環境・プロジェクト土台作成 と 2. QuickWBS API連携土台作成 を `complete` で完了扱いにします
- 3 以降は作成のみ行い、未着手の作業候補として残します

## スターレンタルサーバへ配置する際の注意

- Web 公開対象は `public` 配下のみにしてください
- ルート直下にある `.env` は公開領域の外に置くか、直接アクセスできないようにしてください
- MariaDB 接続情報はサーバ発行の情報に差し替えてください
- `logs` と `storage` に PHP が書き込みできる権限を付与してください
- `.htaccess` によるルーティングが有効か事前に確認してください
- クレジットカード情報は今後も DB 保存しない前提で進めてください

## 今後実装予定の機能

- ネット注文
- 発表デモ調整
