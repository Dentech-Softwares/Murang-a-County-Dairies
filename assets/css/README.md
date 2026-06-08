# Murang’a Dairy Management System

A comprehensive ERP solution for managing dairy cooling plants, farmer milk collections, and external sales.

## Technical Requirements
- **Environment:** XAMPP / LAMP Stack
- **Language:** PHP 7.4+ or 8.x
- **Database:** MySQL / MariaDB
- **Assets:** FontAwesome 7.2 (Included in `/assets/css/all.min.css`)

## Setup Instructions
1. **Database:** Import the SQL file located at `/sql/database.sql` into your MySQL server.
2. **Configuration:** Update `/includes/db_connect.php` with your database credentials.
3. **SMS Gateway:** Configure your API keys in `SmsGateway.php` to enable farmer alerts.
4. **First Login:** Create a Super Admin account directly in the `admins` table to begin managing dairies.

## User Roles
- **Super Admin:** Manage dairies, admins, and global settings.
- **Admin:** View reports and manage specific dairy regions.
- **Attendant:** Process daily milk collections and sales at the plant level.

## Key Features
- Multi-tenant dairy management.
- Real-time dashboard synchronization via AJAX/Fetch API.
- Automated SMS notifications for farmer deliveries.
- PDF and CSV export for financial reporting.

## Security
- CSRF Protection on all POST requests.
- Role-Based Access Control (RBAC).