StockTrade App - Developer Documentation

1. Overview

StockTrade is a web-based stock market simulator built with PHP (Vanilla), MySQL, and JavaScript. It features real-time data fetching (Alpha Vantage), portfolio tracking, and an admin system for managing user funds.

2. Authentication & Roles

Standard Users

Registration: Requires Username, Email, and Password.

2FA (Two-Factor Auth): A 6-digit PIN is sent to the registered email via PHP's mail() function. The user cannot log in without verifying this PIN.

Starting Balance: $100.00.

Admin Account

Username: admin (The system specifically checks for this username).

Special Privileges:

2FA Bypass: The admin account does NOT require email verification or OTP codes. Login is instant.

Admin Panel: Access to a special dashboard (admin.php) to manage user fund requests.

How to Create: Simply register a new account with the username admin. The system will automatically assign the Admin role and verify it.

3. Core Features

Market Data Engine (api.php)

To handle API rate limits (Alpha Vantage Free Tier = 5 calls/min), the app uses a Hybrid Strategy:

Real Data: The first 5 stocks (e.g., AAPL, MSFT) fetch real live prices via cURL.

Simulated Data: The remaining 15 stocks use a realistic "Random Walk" simulation based on real market base prices.

Caching: All data is cached in the user $_SESSION for 60 seconds to ensure the app remains fast and responsive.

Balance Request System

User Side: Users request funds via the Account page.

Database: Request is stored in balance_requests table with status 'pending'.

Admin Side: Admin sees the request in Admin Panel. Clicking "Approve" runs a transaction that credits the user's users.cash column and marks the request as 'approved'.

Favorites System

Users can "Star" up to 5 stocks.

These are stored in the favorites table.

The Market page automatically sorts favorited stocks to the top of the list.

4. Configuration

Database Connection

Located in conn.php.

Class: connector (Custom MySQLi wrapper).

Error Handling: Configured to fail silently (JSON error) for the API, but shows plain text errors if the file is accessed directly in the browser for debugging.

API Settings

Located in api.php.

API Key: Update ALPHA_VANTAGE_KEY constant with a real key.

Email Settings: Update the $headers in sendEmailOTP() to change the "From" address (currently no-reply@devbartos.cz).

5. File Structure

index.php: Entry point router (redirects to Auth or Market).

api.php: The backend logic. Handles ALL data requests (Login, Trade, Chart Data).

header.php: Contains global assets:

CSS/JS libraries (Tailwind, Chart.js).

Global Modals: Alert, Confirm, and Loading Screen.

Startup Loader: Logic to show the black loading screen only once per session.

nav.php: Responsive navigation bar (Desktop + Mobile Hamburger menu).
