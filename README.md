# InvoX - Automated Invoicing & Billing Management System

**InvoX** is a modern, full-featured web-based invoice and financial management platform built with PHP and MySQL. Designed for freelancers, SMEs, and service providers, InvoX automates the entire billing lifecycle with subscription scheduling, multi-tier payment reminders, role-based access control, interactive analytics, and an integrated Google Gemini AI assistant.

---

## 🚀 Key Features

* **Invoice & Product Lifecycle Management**:
  * Dynamic line items with real-time tax (8%), discounts, and subtotal/grand total calculations.
  * Unique invoice numbering (`INV-<timestamp>`).
  * Issue date and due date scheduling with status tracking (`Open`, `Pending`, `Paid`, `Overdue`).
  * Instant print-to-PDF invoice generation with clean, professional layouts.
* **Recurring Invoices & Subscriptions**:
  * Automated recurring invoice generation engine supporting Monthly, Quarterly, and Yearly frequencies.
  * Pause, activate, and complete statuses with automatic next generation date updates.
  * Atomic transactions preserving item lists, pricing, and customer records.
* **Smart Payment Reminders**:
  * Automated email notifications for upcoming dues (7-day advance, 3-day advance) and daily overdue reminders.
  * Customizable email subject and body templates with dynamic placeholders (`{customer_name}`, `{invoice_number}`, `{due_date}`, `{total}`).
  * Full dispatch logs and delivery status auditing.
* **Role-Based Access Control (RBAC)**:
  * **Admin**: Complete system oversight, user management, financial reports, and full CRUD.
  * **Accountant**: Financial operations, customer and product catalog, and invoice processing.
  * **Customer**: Self-service portal to view personal invoices, track payments, and download PDFs.
* **🤖 Google Gemini AI Assistant**:
  * Conversational AI chatbot powered by the `gemini-2.5-flash` model.
  * Context-aware responses answering questions regarding invoice creation, tax calculations, and navigation.
  * Built-in fallback responses for offline/unconfigured environments.
* **Executive Analytics & Reports**:
  * Dynamic executive dashboard with 6 KPI metrics and Chart.js visualizations (6-month revenue trends and payment settlement breakdowns).
  * Financial reporting module supporting custom date intervals, settlement rate analysis, and printable reports.

---

## 🛠️ Technology Stack

* **Backend**: PHP 8.x (OOP, Procedural, PDO abstraction, Session Security)
* **Database**: MySQL / MariaDB (Foreign Keys, Cascades, Indexes, Transactions)
* **Frontend**: Bootstrap 5.3, JavaScript (ES6+), Font Awesome 6.0, HTML5/CSS3
* **Visualization**: Chart.js 4.4
* **AI Engine**: Google Gemini API (`gemini-2.5-flash`)
* **Mailing**: PHPMailer 7.0, SMTP (TLS/SSL Gmail support)
* **Automation**: PHP CLI Background Workers (Linux Cron / Windows Task Scheduler)
* **Testing & QA**: PHPUnit 10.x, Unit & Integration Tests, OWASP Security Test Suite

---

## 📋 Installation & Setup

### 1. Prerequisites
* PHP 8.1 or higher with PDO and cURL extensions enabled.
* MySQL / MariaDB server.
* Composer.

### 2. Clone the Repository
```bash
git clone https://github.com/tafhimul-islam-github/InvoX.git
cd InvoX
```

### 3. Install Dependencies
```bash
composer install
```

### 4. Database Setup
1. Create a MySQL database named `invox_db`.
2. Import the schema and seed data:
```bash
mysql -u root -p invox_db < invox_db.sql
```

### 5. Configuration
* **Email Configuration**: Copy `email_config.example.php` to `email_config.php` and set your SMTP credentials:
  ```bash
  cp email_config.example.php email_config.php
  ```
* **Gemini AI Configuration**: Copy `chatbot_config.example.php` to `chatbot_config.php` and insert your Gemini API Key:
  ```bash
  cp chatbot_config.example.php chatbot_config.php
  ```
  *(Or set the `GEMINI_API_KEY` system environment variable).*

---

## ⚙️ Background Automation (Cron)

Set up cron jobs to process recurring invoices and deliver payment notifications automatically:

```bash
# 1. Generate recurring invoices daily at midnight
0 0 * * * /usr/bin/php /path/to/InvoX/cron_generate_recurring.php

# 2. Dispatch due and overdue reminders daily at 9:00 AM
0 9 * * * /usr/bin/php /path/to/InvoX/cron_send_reminders.php
```

---

## 🧪 Testing & Quality Assurance

InvoX comes equipped with a comprehensive test suite (60+ test cases) covering authentication, authorization, CRUD operations, and OWASP security controls (SQL injection, XSS, session hijacking).

Run the PHPUnit test suite:
```bash
vendor/bin/phpunit
```

---

## 👥 Default Demo Credentials

| Role | Username | Password | Access Level |
| :--- | :--- | :--- | :--- |
| **Admin** | `admin` | `password123` | Full administrative control |
| **Accountant** | `accountant` | `password123` | Financial & invoicing operations |
| **Customer** | `customer` | `password123` | Self-service invoice viewer & PDF download |

---

## 📄 License
This project is open-source and available under the [MIT License](LICENSE).
