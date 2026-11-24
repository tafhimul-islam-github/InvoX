# Invoice Reminders & Recurring Invoices Setup Guide

## 🎯 Features Added

### 1. **Invoice Reminders**
- Automated reminder system for unpaid invoices
- Configurable reminder schedules (3 days, 7 days before due, overdue)
- Email notifications to customers
- Reminder history tracking
- Customizable email templates

### 2. **Recurring Invoices**
- Auto-generate invoices monthly, quarterly, or yearly
- Set start and end dates for recurring cycles
- Pause/resume/complete recurring invoices
- Track generated invoice count
- Automatic invoice creation via cron job

---

## 📦 Installation Steps

### Step 1: Update Database

Run the SQL update script to create new tables:

```bash
# Option 1: Via MySQL command line
mysql -u root -p invox_db < update_reminders_recurring.sql

# Option 2: Via phpMyAdmin
# Import the file: update_reminders_recurring.sql
```

**Or run this SQL directly:**

```sql
-- Copy and paste the contents of update_reminders_recurring.sql
-- into your MySQL client or phpMyAdmin SQL tab
```

This will create 4 new tables:
- `recurring_invoices` - Stores recurring invoice templates
- `recurring_invoice_items` - Stores items for recurring invoices
- `invoice_reminders` - Tracks sent reminders
- `reminder_settings` - Stores reminder configuration

---

### Step 2: Set Up Cron Jobs

For automated invoice generation and reminders, set up these cron jobs:

#### **Linux/Mac Cron Jobs:**

```bash
# Edit crontab
crontab -e

# Add these lines:

# Generate recurring invoices daily at midnight
0 0 * * * /usr/bin/php /path/to/Invox1/cron_generate_recurring.php

# Send invoice reminders daily at 9 AM
0 9 * * * /usr/bin/php /path/to/Invox1/cron_send_reminders.php
```

#### **Windows Task Scheduler:**

1. Open Task Scheduler
2. Create New Task:
   - **Name:** Generate Recurring Invoices
   - **Trigger:** Daily at 12:00 AM
   - **Action:** Start a program
   - **Program:** `C:\laragon\bin\php\php-8.x\php.exe`
   - **Arguments:** `C:\laragon\www\Invox1\cron_generate_recurring.php`

3. Create another task for reminders:
   - **Name:** Send Invoice Reminders
   - **Trigger:** Daily at 9:00 AM
   - **Program:** `C:\laragon\bin\php\php-8.x\php.exe`
   - **Arguments:** `C:\laragon\www\Invox1\cron_send_reminders.php`

#### **Manual Testing:**

```bash
# Test recurring invoice generation
php cron_generate_recurring.php

# Test reminder sending
php cron_send_reminders.php
```

---

## 🎨 Usage Guide

### **Recurring Invoices Page** (`recurring_invoices.php`)

#### Create a Recurring Invoice:
1. Click "Create Recurring Invoice"
2. Select customer
3. Choose frequency (Monthly/Quarterly/Yearly)
4. Set start date (and optional end date)
5. Add invoice items
6. Enter tax and discount
7. Click "Create"

#### Manage Recurring Invoices:
- **View Details** - See invoice information
- **Pause** - Temporarily stop generation
- **Activate** - Resume generation
- **Delete** - Remove recurring invoice

### **Invoice Reminders Page** (`invoice_reminders.php`)

#### Configure Reminder Settings:
1. Click "Reminder Settings"
2. Enable/disable reminder types:
   - ✅ 7 days before due date
   - ✅ 3 days before due date
   - ✅ Overdue reminders
3. Customize email subject and body
4. Use placeholders: `{customer_name}`, `{invoice_number}`, `{due_date}`, `{total}`
5. Save settings

#### Monitor Reminders:
- **Upcoming Reminders** - View invoices needing reminders
- **Overdue Invoices** - See overdue invoices
- **Reminder History** - Track sent reminders

---

## 📧 Email Configuration

### Configure Email Sending:

Edit the cron scripts to use your email settings:

**Option 1: PHP mail() function (default)**
- Works with most hosting providers
- No configuration needed
- May end up in spam folder

**Option 2: SMTP (Recommended)**

Install PHPMailer (already included):
```bash
composer require phpmailer/phpmailer
```

Update `cron_send_reminders.php` to use SMTP:
```php
// Replace the mail() function with PHPMailer
use PHPMailer\PHPMailer\PHPMailer;

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;
// ... set recipients and send
```

---

## 🔍 Testing the Features

### Test Recurring Invoice Generation:

1. Create a recurring invoice with start date = today
2. Run: `php cron_generate_recurring.php`
3. Check the `invoices` table for new invoice
4. Check logs: `logs/recurring_invoices.log`

### Test Invoice Reminders:

1. Create an invoice with due date = 3 days from today
2. Configure reminder settings (enable 3-day reminder)
3. Run: `php cron_send_reminders.php`
4. Check `invoice_reminders` table
5. Check logs: `logs/invoice_reminders.log`

---

## 📊 Database Structure

### New Tables:

**recurring_invoices:**
- id, customer_id, frequency, start_date, end_date
- next_generation_date, subtotal, tax, discount, total
- status, last_generated_date, created_at

**recurring_invoice_items:**
- id, recurring_invoice_id, product_id, quantity, price

**invoice_reminders:**
- id, invoice_id, reminder_type, scheduled_date
- sent_status, sent_at, email_sent_to, created_at

**reminder_settings:**
- id, days_before_3, days_before_7, overdue_reminder
- reminder_email_subject, reminder_email_body

**Updated Tables:**
- `invoices` - Added: `reminder_enabled`, `recurring_invoice_id`

---

## 🚀 Features Overview

### Recurring Invoices Features:
✅ Monthly, Quarterly, Yearly frequencies
✅ Auto-generate invoices on schedule
✅ Pause/Resume functionality
✅ Set end dates (optional)
✅ Track generated invoice count
✅ Email notifications (optional)
✅ Detailed logging

### Invoice Reminders Features:
✅ 7-day advance reminders
✅ 3-day advance reminders
✅ Overdue reminders (daily)
✅ Customizable email templates
✅ Reminder history tracking
✅ Enable/disable per invoice
✅ Automatic status updates
✅ Statistics dashboard

---

## 🛠 Troubleshooting

### Cron Jobs Not Running:
- Check PHP path: `which php`
- Verify file permissions: `chmod +x cron_*.php`
- Check cron logs: `/var/log/cron` or `journalctl -u cron`
- Test manually: `php cron_generate_recurring.php`

### Emails Not Sending:
- Check email configuration in cron scripts
- Verify SMTP settings if using PHPMailer
- Check spam folder
- Review logs: `logs/invoice_reminders.log`
- Test with simple PHP mail script

### Database Errors:
- Ensure update script ran successfully
- Check database user permissions
- Verify table creation: `SHOW TABLES;`
- Check for foreign key constraints

---

## 📁 New Files Created

1. `update_reminders_recurring.sql` - Database update script
2. `recurring_invoices.php` - Recurring invoices management
3. `invoice_reminders.php` - Reminders management
4. `cron_generate_recurring.php` - Auto-generation cron job
5. `cron_send_reminders.php` - Reminder sending cron job
6. `REMINDERS_RECURRING_SETUP.md` - This guide

---



