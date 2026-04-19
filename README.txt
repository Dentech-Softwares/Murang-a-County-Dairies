Muranga County Dairy Management System
---------------------------------------

Setup Instructions:
1. Ensure you have XAMPP or any PHP/MySQL environment running.
2. Create a database named `muranga_dairy`.
3. Import the `sql/database.sql` file into your database.
4. Place all files in a folder (e.g., `kim`) within your `htdocs` directory.
5. Access the system via `http://localhost/kim/`.
6. Run `http://localhost/kim/setup.php` for automatic database initialization.

Project Structure:
- `admin/`: Head Office portal pages (Dashboard, Dairies, Farmers, etc.)
- `attendant/`: Cooling Dairy portal pages (Collection, Sales, etc.)
- `includes/`: Common components (DB connection, Headers, Footers)
- `assets/`: Static files (CSS, Icons)
- `sql/`: Database schema files

Initial Access:
- For Head Office: Go to the "Head Office Portal" -> Register.
- For Attendants: Admins add them via the Head Office dashboard.
- Attendants login with phone + "123456" (must change on first login).

Features:
- Head Office: Dashboard stats, Manage Dairies/Attendants, View Farmers, Milk Records, Payments, Sales, Downloadable Reports (CSV), and Price Settings.
- Dairy Attendants: Dashboard (Daily stats & profit), Manage Farmers, Record Milk Collections, Record Milk Sales, and View History.

Tech Stack:
- PHP (PDO)
- MySQL
- HTML5 / CSS3
- FontAwesome (for icons)
