# Quick Start Guide - Invoice Reminders & Recurring Invoices

## ✅ Installation Complete!

All features have been successfully installed and tested.

---

## 🚀 Quick Access

### New Pages:
- **Recurring Invoices:** [http://localhost/Invox1/recurring_invoices.php](http://localhost/Invox1/recurring_invoices.php)
- **Invoice Reminders:** [http://localhost/Invox1/invoice_reminders.php](http://localhost/Invox1/invoice_reminders.php)

### Menu Navigation:
Look for the new menu items in the sidebar:
- 🔄 **Recurring** - Manage recurring invoices
- 🔔 **Reminders** - Configure and view reminders

---

## 📝 How to Use

### Create Your First Recurring Invoice:

1. Go to **Recurring Invoices** page
2. Click **"Create Recurring Invoice"** button
3. Fill in the form:
   - Select a customer
   - Choose frequency (Monthly/Quarterly/Yearly)
   - Set start date
   - Add products/services
   - Set tax and discount
4. Click **"Create Recurring Invoice"**
5. Done! Invoices will auto-generate on schedule

### Configure Invoice Reminders:

1. Go to **Invoice Reminders** page
2. Click **"Reminder Settings"** button
3. Enable/disable reminder types:
   - ☑️ 7 days before due date
   - ☑️ 3 days before due date
   - ☑️ Overdue reminders
4. Customize email template (optional)
5. Click **"Save Settings"**

---

## ⚙️ Automation Setup (Important!)

For automatic operation, set up these cron jobs:

### Windows (Task Scheduler):

**Task 1: Generate Recurring Invoices**
```
Program: C:\laragon\bin\php\php-8.x\php.exe
Arguments: C:\laragon\www\Invox1\cron_generate_recurring.php
Schedule: Daily at 12:00 AM
```

**Task 2: Send Reminders**
```
Program: C:\laragon\bin\php\php-8.x\php.exe
Arguments: C:\laragon\www\Invox1\cron_send_reminders.php
Schedule: Daily at 9:00 AM
```

### Linux/Mac (Crontab):

```bash
# Edit crontab
crontab -e

# Add these lines:
0 0 * * * /usr/bin/php /path/to/Invox1/cron_generate_recurring.php
0 9 * * * /usr/bin/php /path/to/Invox1/cron_send_reminders.php
```

### Manual Testing:

```bash
# Generate invoices manually
php cron_generate_recurring.php

# Send reminders manually
php cron_send_reminders.php
```

---

## 📊 Features Overview

### Recurring Invoices:
- ✅ Auto-generate invoices (monthly/quarterly/yearly)
- ✅ Set start and end dates
- ✅ Pause/resume anytime
- ✅ Track generated invoices
- ✅ Copy all items and pricing

### Invoice Reminders:
- ✅ Automatic reminders before due date
- ✅ Overdue invoice notifications
- ✅ Customizable email templates
- ✅ Track reminder history
- ✅ Enable/disable per invoice

---

## 📁 Files Created

1. ✅ `update_reminders_recurring.sql` - Database setup
2. ✅ `recurring_invoices.php` - Recurring invoices page
3. ✅ `invoice_reminders.php` - Reminders page
4. ✅ `cron_generate_recurring.php` - Auto-generation script
5. ✅ `cron_send_reminders.php` - Reminder sending script
6. ✅ `REMINDERS_RECURRING_SETUP.md` - Full documentation
7. ✅ `QUICK_START.md` - This guide
8. ✅ `test_setup.php` - Test script
9. ✅ `logs/` - Log directory

---

## 🧪 Testing

### Test Results:
```
✓ All database tables created
✓ Invoice table updated with new columns
✓ Reminder settings configured
✓ Cron scripts ready
✓ Page files accessible
✓ Logs directory created
```

### Test the Features:

1. **Create a test recurring invoice:**
   - Use a monthly frequency
   - Set start date to today
   - Add a product
   - Save it

2. **Run the generator manually:**
   ```bash
   php cron_generate_recurring.php
   ```
   - Check if invoice was created in Invoices page

3. **Create an invoice with near due date:**
   - Set due date to 3 days from now
   - Save it

4. **Test reminder sending:**
   ```bash
   php cron_send_reminders.php
   ```
   - Check Reminder History tab

---

## 📧 Email Setup (Optional but Recommended)

By default, reminders use PHP `mail()` function. For better delivery:

### Use SMTP (PHPMailer):

Edit `cron_send_reminders.php` and replace the mail() section with:

```php
use PHPMailer\PHPMailer\PHPMailer;
require 'vendor/autoload.php';

$mail = new PHPMailer(true);
$mail->isSMTP();
$mail->Host = 'smtp.gmail.com';
$mail->SMTPAuth = true;
$mail->Username = 'your-email@gmail.com';
$mail->Password = 'your-app-password';
$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
$mail->Port = 587;

$mail->setFrom('noreply@yourdomain.com', 'InvoX');
$mail->addAddress($invoice['customer_email']);
$mail->Subject = $subject;
$mail->Body = $body;
$mail->send();
```

---

## 🔍 Logs & Monitoring

Check logs for activity:

```bash
# View recurring invoice logs
cat logs/recurring_invoices.log

# View reminder logs
cat logs/invoice_reminders.log
```

---

## 💡 Tips

1. **Start with test data** - Create test customers and products
2. **Use short intervals** - Test with monthly instead of yearly
3. **Check logs regularly** - Monitor for errors
4. **Backup database** - Before major changes
5. **Test email delivery** - Ensure customers receive reminders
6. **Set realistic schedules** - Don't spam customers
7. **Monitor failed reminders** - Check and resend if needed

---

## 🎯 Common Use Cases

### Subscription Services:
- Create recurring invoice for each subscriber
- Set to monthly frequency
- Automatic billing every month

### Quarterly Reports:
- Set frequency to quarterly
- Auto-generate every 3 months
- Track payment status

### Annual Renewals:
- Use yearly frequency
- Send reminders 7 days before
- Track renewal rates

---

## ❓ Troubleshooting

### Invoices not generating?
- Check if start date has passed
- Verify cron job is running
- Check logs: `logs/recurring_invoices.log`
- Run manually: `php cron_generate_recurring.php`

### Reminders not sending?
- Verify reminder settings are enabled
- Check email configuration
- Review logs: `logs/invoice_reminders.log`
- Test manually: `php cron_send_reminders.php`

### Can't access new pages?
- Clear browser cache
- Check if logged in
- Verify you have Admin/Accountant role
- Check file permissions

---

## 🎉 You're Ready!

Your InvoX system now has powerful automation features:
- 🔄 Auto-generate recurring invoices
- 🔔 Smart payment reminders
- 📧 Professional email notifications
- 📊 Comprehensive tracking

**Start creating your first recurring invoice now!**

For detailed documentation, see: `REMINDERS_RECURRING_SETUP.md`
