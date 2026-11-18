# Zeyara â€“ E-Commerce Web Application

Zeyara is a PHP and MySQL-based e-commerce web application. It provides customer-facing shopping features alongside administrative tools for managing products, stock, users, and reports.

## Features

### Customer
- Browse and view products
- Search and explore product information
- Manage a shopping cart
- Use the checkout and payment workflow
- View order history and invoices
- Manage account and profile information
- Request password recovery by email

### Administration
- Administrator sign-in
- Product registration and management
- Stock management
- User management
- Sales, product, stock, and user reports
- Dashboard and chart reporting

### Integrations
- PayHere payment gateway
- SMTP email functionality using PHPMailer
- MySQL database

## Technology Stack

- PHP 8.x (developed and checked with PHP 8.4)
- MySQL 8.0
- HTML, CSS, and JavaScript
- PayHere
- PHPMailer

## Requirements

- PHP 8.x
- MySQL 8.0 or a compatible release
- PHP extensions: `mysqli`, `pdo_mysql`, `fileinfo`, `gd`, `curl`, `openssl`, and `mbstring`
- A web server or PHP development server

## Project Structure

The repository contains the PHP application pages and request handlers, frontend assets, database connectivity code, and application resources.

The database schema is provided in:

`database/schema.sql`

## Installation

### 1. Clone the repository

After creating the GitHub repository, replace the placeholder URL below with its clone URL:

```bash
git clone https://github.com/Sasindi-41/Zeyara
cd zeyara
```

### 2. Create the database

Create a MySQL database for the application. For example, use the database name `zeyara`.

Import the schema from the project root:

```bash
mysql -u YOUR_DATABASE_USER -p zeyara < database/schema.sql
```

The schema file contains table definitions and foreign-key relationships. It does not include live customer, order, or other application records.

### 3. Configure database access

The application reads its database connection settings from environment variables:

| Variable | Purpose |
|---|---|
| `ZEYARA_DB_HOST` | MySQL server hostname |
| `ZEYARA_DB_PORT` | MySQL server port |
| `ZEYARA_DB_NAME` | Database name |
| `ZEYARA_DB_USER` | Database username |
| `ZEYARA_DB_PASS` | Database password |

The local `privateConfig.php` file is intentionally excluded from version control. Configure your own local environment and private configuration without committing credentials or secret values.

### 4. Configure payment and email services

Configure your own PayHere merchant settings and SMTP account before testing payment and email functionality. Use PayHere sandbox mode for testing. Production payment processing requires valid merchant configuration and a publicly accessible HTTPS callback endpoint.

### 5. Start the development server

From the project directory, run:

```bash
php -S localhost:8000
```

Then open:

`http://localhost:8000`

Use a properly configured web server for deployment rather than the PHP development server.

## Database

The repository includes a structure-only export of the application's 13 database tables and their foreign-key relationships.

No live customer records, order records, or database credentials are included in the schema export.

## Security Notes

- Never commit database passwords, payment secrets, SMTP credentials, or private configuration files.
- Keep runtime uploads and user-uploaded files out of version control.
- Use HTTPS in production.
- Use a database account with only the permissions the application requires.
- Review web-server configuration and upload-directory protections before deployment.
- Rotate any credentials that may have been exposed during development.

## Project Status

Zeyara is undergoing development review and testing. Payment, email, and deployment integrations require environment-specific configuration and end-to-end testing before production use.

## License

No open-source license has been specified. All rights remain with the project author unless a license is added.

