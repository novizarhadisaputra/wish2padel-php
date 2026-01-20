# Analysis of Non-Compliant Pages (Legacy Structure)

The following files are identified as "Legacy" or "Non-Compliant" with the new MVC structure. They typically contain mixed PHP logic and HTML, bypass the `routes/web.php` router, and are located in root subdirectories instead of `app/Controllers` and `resources/views`.

## Admin Directory (`admin/`)
These files should be migrated to `AdminController` and `resources/views/admin/`.
- division.php
- document.php
- gallery.php
- match.php
- news.php
- pair.php
- payment_settings.php
- players.php
- playoff.php
- presentasion.php
- registrations.php
- result.php
- sponsors.php
- team.php
- tournament.php
- windows.php
- dashboard.php (Already has `DashboardController`, safe to delete after verification)

## Club Directory (`club/`)
These files should be migrated to `ClubDashboardController` and `resources/views/club/`.
- dashboard.php
- team.php
- update_center.php
- navbar.php (Should be a partial view)

## Root Files
- logout.php (Redundant, handled by `AuthController`)
- webhook.php (Should be `PaymentController@webhook`)
- team.php (Likely `TeamController`)
- windows.php (Likely `AdminController` duplicate or specific public page)
- auth/* (Legacy auth scripts)
- login/* (Legacy login scripts)
- register/* (Legacy register scripts)
