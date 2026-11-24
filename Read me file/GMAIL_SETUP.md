# 📧 Gmail SMTP Setup for InvoX - Complete Guide



## 📋 Step-by-Step Setup

### **Step 1: Install PHPMailer**

Open terminal/command prompt in your Invox1 folder and run:

```bash
cd d:\laragon\www\Invox1
composer require phpmailer/phpmailer
```

### **Step 2: Enable 2-Step Verification in Gmail**

1. Go to: https://myaccount.google.com/security
2. Find **"2-Step Verification"**
3. Click **"Get Started"** and follow the prompts
4. Verify your phone number
5. Complete the setup

![2FA Setup]

---

### **Step 3: Generate Gmail App Password**

1. Go to: https://myaccount.google.com/apppasswords
   - If you don't see this option, make sure 2-Step Verification is enabled
   
2. **Select app:** Choose **"Mail"**

3. **Select device:** Choose **"Other (Custom name)"**
   - Type: **"InvoX System"**
   
4. Click **"Generate"**

5. **Copy the 16-character password**
   - It looks like: `xxxx xxxx xxxx xxxx`
   - You can ignore the spaces
   - You'll need this in the next step

6. Click **"Done"**

⚠️ **Important:** This password is shown only once. Copy it now!

---

### **Step 4: Configure email_config.php**

1. Open: `d:\laragon\www\Invox1\email_config.php`

2. Replace these values:

```php
return [
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_secure' => 'tls',
    'smtp_username' => 'your-email@gmail.com',     // ← Your Gmail address
    'smtp_password' => 'xxxx xxxx xxxx xxxx',       // ← App Password from Step 3
    
    'from_email' => 'your-email@gmail.com',         // ← Your Gmail address
    'from_name' => 'InvoX Invoice System',          // ← Your company name
    'reply_to' => 'your-email@gmail.com',           // ← Your Gmail address
    
    'smtp_debug' => 0,
];
```

3. **Save the file**

---

### **Step 5: Test Your Configuration**

1. Open your browser
2. Go to: `http://localhost/Invox1/test_email.php`
3. Enter your email address
4. Click **"Send Test Email"**
5. Check your inbox (and spam folder)

**✅ Success?** You should see a confirmation message!

**❌ Error?** See troubleshooting section below.

---



## ⚠️ Troubleshooting

### Error: "SMTP connect() failed"

**Solution:**
- Check your internet connection
- Make sure Gmail isn't blocked by firewall
- Try port 465 with SSL:
  ```php
  'smtp_port' => 465,
  'smtp_secure' => 'ssl',
  ```

---

### Error: "Invalid credentials"

**Solution:**
- ✅ Use **App Password**, NOT your regular Gmail password
- ✅ Make sure 2-Step Verification is enabled
- ✅ Generate a new App Password if needed
- ✅ Copy password exactly (no extra spaces)
- ✅ Check `smtp_username` matches your Gmail

---

### Error: "Could not authenticate"

**Solution:**
- Verify you're using the App Password from Step 3
- Check that your Gmail account doesn't have any security alerts
- Visit: https://myaccount.google.com/notifications
- Clear any "Suspicious sign-in prevented" notifications





## 📱 Alternative SMTP Services

If Gmail doesn't work for you:

### SendGrid (Free Tier: 100 emails/day)
```php
'smtp_host' => 'smtp.sendgrid.net',
'smtp_port' => 587,
'smtp_username' => 'apikey',
'smtp_password' => 'YOUR_SENDGRID_API_KEY',
```

### Outlook/Office365
```php
'smtp_host' => 'smtp.office365.com',
'smtp_port' => 587,
'smtp_username' => 'your-email@outlook.com',
'smtp_password' => 'your-password',
```