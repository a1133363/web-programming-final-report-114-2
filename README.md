# NOCTURNE 暗標局

以 PHP 8.2+ 與 MySQL 8.0+（亦相容常見 XAMPP MariaDB 10.4）製作的虛構地下拍賣平台。所有商品、人物與交易情境皆為虛構，專案重點是完整展示會員權限、拍賣、代理出價、交易、審核、風險與報表流程。

## 已實作功能

- 訪客：首頁、分類、搜尋、風險篩選、拍賣詳情、倒數、出價紀錄、通緝名冊
- 買家：註冊登入、收藏、手動出價、代理出價、得標與交付狀態
- 賣家：商品刊登、圖片安全驗證、AI 描述 Mock、AI 風險建議、銷售概況
- 管理員：審核商品、設定最終風險、通緝名單、全站報表、操作稽核
- 系統：PDO Prepared Statements、`password_hash()`、Session 角色權限、CSRF、Cron 截標與郵件通知
- 介面：響應式暗色拍賣目錄、鍵盤焦點、44px 觸控目標、reduced-motion、Chart.js 報表

## 快速啟動

1. 複製環境設定：

   ```powershell
   Copy-Item .env.example .env
   ```

2. 使用 phpMyAdmin 或 MySQL CLI，依序匯入：

   ```text
   database/schema.sql
   database/seed.sql
   ```

3. 從專案根目錄啟動 PHP 內建伺服器：

   ```powershell
   C:\xampp\php\php.exe -S 127.0.0.1:8000 -t public
   ```

4. 開啟 `http://127.0.0.1:8000`。

若尚未匯入 MySQL，網站會自動使用唯讀示範資料，仍可檢視所有主要版面。

## 示範帳號

所有帳號密碼皆為 `demo1234`。

| 角色 | 電子信箱 |
|---|---|
| 買家 | `buyer@example.com` |
| 賣家 | `seller@example.com` |
| 管理員 | `admin@example.com` |

## Cron

建議每分鐘執行截標，每五分鐘處理通知：

```powershell
C:\xampp\php\php.exe cron\close_auctions.php
C:\xampp\php\php.exe cron\send_notifications.php
```

正式環境請將 `MailService` 改接 SMTP 套件，並把 Web Root 指向 `public/`，避免設定與儲存目錄被直接存取。

## 專案結構

```text
app/          Controllers、Models、Services、Middleware、Core
config/       應用程式與 PDO 設定
cron/         截標與通知排程
database/     schema.sql、seed.sql
public/       唯一 Web Root、CSS、JavaScript、AI 圖片資產
storage/      上傳檔案
views/        前台、會員、賣家、管理員樣板
```

## 圖片素材

首頁主視覺與四張拍賣品影像由 OpenAI 內建圖片生成功能建立，實際檔案位於 `public/assets/images/`。生成提示均指定虛構物件、無真實非法物品、無文字、無 Logo 與無浮水印。
