📈 StockTrade App

A web-based stock market simulator built with Vanilla PHP, MySQL, and JavaScript.
StockTrade allows users to simulate trading with real-time market data, manage portfolios, and request funds through an admin-controlled system.

🚀 Overview

StockTrade is designed as a lightweight trading simulator featuring:

Real-time and simulated stock data

Secure authentication with 2FA

Portfolio and balance management

Admin dashboard for approving user fund requests

Market data is powered by Alpha Vantage, with smart handling of API rate limits.

🔐 Authentication & Roles
👤 Standard Users

Registration Requirements

Username

Email

Password

Two-Factor Authentication (2FA)

A 6-digit OTP is sent to the registered email

Implemented using PHP’s mail() function

Login is blocked until the OTP is verified

Starting Balance

$100.00

🛡️ Admin Account

Username

admin (hardcoded role check)

Special Privileges

2FA Bypass
Admin login does not require email verification or OTP.

Admin Panel Access
Dedicated dashboard (admin.php) for managing balance requests.

How to Create an Admin Account

Register normally using the username admin

The system automatically assigns admin privileges and verifies the account

⚙️ Core Features
📊 Market Data Engine (api.php)

To work around Alpha Vantage free-tier limits (5 calls/minute), the app uses a hybrid data strategy:

Real Data

First 5 stocks (e.g., AAPL, MSFT)

Fetched live via cURL

Simulated Data

Remaining 15 stocks

Generated using a realistic random walk algorithm based on real base prices

Caching

Market data is cached in $_SESSION

Cache duration: 60 seconds

Ensures fast performance and reduced API usage

💰 Balance Request System
User Side

Users submit balance requests from the Account page

Database

Requests are stored in the balance_requests table

Default status: pending

Admin Side

Admin views requests in the Admin Panel

Clicking Approve:

Credits the user’s users.cash balance

Updates request status to approved

Executed as a secure database transaction

⭐ Favorites System

Users can favorite (star) up to 5 stocks

Favorites are stored in the favorites table

Favorited stocks are automatically:

Sorted to the top of the Market page

🔧 Configuration
🗄️ Database Connection

File: conn.php

Class: connector

Custom MySQLi wrapper

Error Handling

API requests fail silently with JSON errors

Direct browser access shows plaintext errors for debugging

🌐 API & Email Settings

File: api.php

Alpha Vantage
define('ALPHA_VANTAGE_KEY', 'YOUR_API_KEY_HERE');

Email (2FA OTP)

Modify the $headers variable in sendEmailOTP()

Default sender:

no-reply@devbartos.cz

📁 File Structure
/
├── index.php        # Entry point router (Auth → Market)
├── api.php          # Backend logic (Auth, Trades, Charts, API)
├── conn.php         # Database connector
├── header.php       # Global assets and UI components
│   ├── Tailwind CSS
│   ├── Chart.js
│   ├── Global Modals (Alert, Confirm, Loader)
│   └── One-time session loading screen logic
├── nav.php          # Responsive navigation (Desktop + Mobile)
└── admin.php        # Admin dashboard

🧪 Tech Stack

Backend: PHP (Vanilla)

Frontend: JavaScript, Tailwind CSS

Database: MySQL

Charts: Chart.js

Market Data: Alpha Vantage API

📄 License

This project is for educational and development purposes.
Feel free to fork, modify, and experiment.

If you want, I can also:

Add installation instructions

Write a .env example

Create API endpoint documentation

Make it more open-source–ready (badges, license, screenshots)
