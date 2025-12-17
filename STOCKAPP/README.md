<div align="center">

# 📈 StockTrade

### Real-Time Market Simulation Platform

<p>
<b>StockTrade</b> is a robust, web-based stock market simulation platform.
It features real-time market data integration, a comprehensive portfolio management system,
and secure administrative controls for managing user funds.
</p>

</div>

---

## ✨ Key Features

### 🚀 Core Functionality
* **Real-Time Data Engine:** Utilizes a hybrid strategy combining Alpha Vantage API for live data (top stocks) and a sophisticated "Random Walk" algorithm for simulated market movement to handle rate limits.
* **Responsive Design:** Built with Tailwind CSS, ensuring a seamless experience across desktop and mobile devices.
* **Smart Caching:** Session-based caching mechanism to optimize API usage and page load speeds.

### 🔐 Authentication & Security
* **Secure Registration:** Password hashing and unique email validation.
* **Two-Factor Authentication (2FA):** OTP (One-Time Password) verification system via email (`mail()`) for standard users.
* **Role-Based Access Control:** Distinct capabilities for User and Admin accounts.

### 💼 Portfolio Management
* **Trade Execution:** Buy and sell stocks with instant balance updates.
* **Analytics:** Real-time tracking of Average Cost, Total Gains/Losses, and Percentage Returns.
* **Favorites:** "Star" up to 5 priority stocks to keep them at the top of your market feed.

### 🛠 Administrative Control
* **Balance Request System:** Users can request funds via their dashboard.
* **Admin Panel:** Admins can review, approve, or reject balance requests securely.
* **Admin Bypass:** Special simplified login flow for the admin account (No 2FA required).

---

## ⚙️ Configuration & Setup

### 1. Database Setup
Import the provided SQL schema into your MySQL/MariaDB database. Then, configure the connection:

**File:** `conn.php`
**Action:** Update the connector class instantiation with your server credentials.
```php
$db = new connector("localhost", "db_user", "db_password", "db_name");
```

### 2. API Configuration
Get your free API key from Alpha Vantage.

**File:** `api.php`
**Action:** Replace the demo key constant.
```php
define('ALPHA_VANTAGE_KEY', 'YOUR_REAL_KEY');
```

### 3. Email Headers
Ensure your hosting provider allows the "From" address specified in the mail headers.

**File:** `api.php`
**Action:** Update the `$headers` in the `sendEmailOTP` function.
```php
$headers = "From: no-reply@your-domain.com\r\n";
```

---

## 👥 User Roles

| Role | Username | Features | Login Flow |
| :--- | :--- | :--- | :--- |
| **Standard User** | (Any) | Trading, Portfolio, Favorites, Request Funds | Username + Password + Email 2FA |
| **Admin** | `admin` | Approve Requests, View All, Bypass Restrictions | Username + Password (Instant Login) |

> [!NOTE]
> New users start with a default balance of **$100.00**.

---

## 📂 File Structure

```text
├── 📄 index.php        # Entry point router (Auth Guard)
├── 📄 api.php          # Backend Controller (Login, Trades, Data)
├── 📄 auth.php         # Login/Register UI with 2FA Modal
├── 📄 market.php       # Main Dashboard & Real-time Grid
├── 📄 detail.php       # Stock Charts & Trading Interface
├── 📄 portfolio.php    # User Holdings & Performance
├── 📄 account.php      # User Stats, History & Fund Requests
├── 📄 admin.php        # Admin Panel for Fund Management
├── 📄 header.php       # Global Assets, Modals & Loaders
├── 📄 nav.php          # Responsive Navigation Bar
└── 📄 conn.php         # Database Connection Class
```

## ⚠️ Requirements

* PHP 7.4 or higher
* MySQL / MariaDB
* Apache/Nginx Web Server
* cURL extension enabled (for API calls)
* sendmail configured (for 2FA)

<div align="center">
<br>
<sub>Built for a school project. Not for real financial trading.</sub>
</div>
