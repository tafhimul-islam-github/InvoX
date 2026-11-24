# 🎯 How to Access New Features

## Quick Navigation Guide

### 📍 Where to Find the New Features

After logging in, look at the **left sidebar menu**:

```
📝 InvoX
├── 🏠 Dashboard
├── ➕ Create Invoice
├── 📄 Invoices
├── 👥 Customers
├── 📦 Products
├── 🔄 Recurring         ← NEW FEATURE #1
├── 🔔 Reminders         ← NEW FEATURE #2
├── 📊 Reports
├── ⚙️ System Users
└── 🚪 Logout
```

---

## 🔄 Feature #1: Recurring Invoices

### How to Access:
1. Login to InvoX
2. Click **"Recurring"** in the sidebar (🔄 icon)
3. You'll see the Recurring Invoices dashboard

### What You'll See:
```
┌─────────────────────────────────────────────────────┐
│  🔄 Recurring Invoices                              │
│  Auto-generate invoices monthly, quarterly, yearly  │
│                                                      │
│  [+ Create Recurring Invoice]                       │
├─────────────────────────────────────────────────────┤
│  Statistics Cards:                                  │
│  ┌───────┐ ┌───────┐ ┌───────┐ ┌───────┐          │
│  │Total  │ │Active │ │Paused │ │Generate│          │
│  │   0   │ │   0   │ │   0   │ │   0    │          │
│  └───────┘ └───────┘ └───────┘ └───────┘          │
├─────────────────────────────────────────────────────┤
│  Table with all recurring invoices                  │
│  Columns: ID, Customer, Frequency, Dates, Actions   │
└─────────────────────────────────────────────────────┘
```

### Quick Actions:
- **Create** - Add new recurring invoice
- **View** - See invoice details
- **Pause** - Temporarily stop generation
- **Activate** - Resume generation
- **Delete** - Remove recurring invoice

---

## 🔔 Feature #2: Invoice Reminders

### How to Access:
1. Login to InvoX
2. Click **"Reminders"** in the sidebar (🔔 icon)
3. You'll see the Invoice Reminders dashboard

### What You'll See:
```
┌─────────────────────────────────────────────────────┐
│  🔔 Invoice Reminders                               │
│  Automated payment reminders for unpaid invoices    │
│                                                      │
│  [⚙️ Reminder Settings]                             │
├─────────────────────────────────────────────────────┤
│  Statistics Cards:                                  │
│  ┌─────────┐ ┌────────┐ ┌────────┐ ┌────────┐    │
│  │Pending  │ │Overdue │ │Sent    │ │Pending │    │
│  │Invoices │ │        │ │Today   │ │Queue   │    │
│  └─────────┘ └────────┘ └────────┘ └────────┘    │
├─────────────────────────────────────────────────────┤
│  Tabs:                                              │
│  [Upcoming] [Overdue] [History]                     │
│                                                      │
│  Content changes based on selected tab              │
└─────────────────────────────────────────────────────┘
```

### Tabs Available:
1. **Upcoming Reminders** - Invoices that will need reminders
2. **Overdue Invoices** - Past due invoices requiring action
3. **Reminder History** - All sent reminders

---

## 📱 Step-by-Step: Create Recurring Invoice

### Step 1: Open the Form
```
Click: Recurring → [+ Create Recurring Invoice]
```

### Step 2: Fill Basic Info
```
┌─────────────────────────────────────┐
│ Customer: [Select...] ▼             │
│ Frequency: [Monthly] ▼              │
│                                      │
│ Start Date: [2026-01-11]            │
│ End Date: [Optional]                │
└─────────────────────────────────────┘
```

### Step 3: Add Items
```
┌──────────────────────────────────────────────┐
│ Product         Qty   Price    Total         │
├──────────────────────────────────────────────┤
│ [Select...] ▼   [1]   [0.00]   [0.00]      │
│ [+ Add Item]                                 │
└──────────────────────────────────────────────┘
```

### Step 4: Set Totals
```
┌─────────────────────┐
│ Subtotal:  [0.00]   │
│ Tax (%):   [0]      │
│ Discount:  [0.00]   │
│ Total:     [0.00]   │
└─────────────────────┘
```

### Step 5: Submit
```
[Cancel]  [Create Recurring Invoice]
```

---

## ⚙️ Step-by-Step: Configure Reminders

### Step 1: Open Settings
```
Click: Reminders → [⚙️ Reminder Settings]
```

### Step 2: Enable Schedules
```
┌─────────────────────────────────────────────┐
│ Reminder Schedule                            │
├─────────────────────────────────────────────┤
│ ☑ 7 Days Before Due Date                   │
│   Send a reminder 7 days before due         │
│                                              │
│ ☑ 3 Days Before Due Date                   │
│   Send a reminder 3 days before due         │
│                                              │
│ ☑ Overdue Reminder                          │
│   Send reminders for overdue invoices       │
└─────────────────────────────────────────────┘
```

### Step 3: Customize Email
```
┌─────────────────────────────────────────────┐
│ Email Subject:                               │
│ [Payment Reminder for Invoice #{number}]    │
│                                              │
│ Email Body:                                  │
│ ┌─────────────────────────────────────────┐│
│ │Dear {customer_name},                    ││
│ │                                          ││
│ │This is a reminder that invoice          ││
│ │#{invoice_number} is due on {due_date}.  ││
│ │                                          ││
│ │Amount: ${total}                         ││
│ │Status: {status}                         ││
│ │                                          ││
│ │Please make payment at your earliest     ││
│ │convenience.                              ││
│ │                                          ││
│ │Thank you!                                ││
│ └─────────────────────────────────────────┘│
└─────────────────────────────────────────────┘

Available placeholders:
{customer_name}, {invoice_number}, {due_date}, 
{total}, {status}, {days_until_due}
```

### Step 4: Save
```
[Cancel]  [Save Settings]
```

---

## 🎮 Interactive Features

### Recurring Invoices Actions:

**For Active Recurring:**
- 👁️ **View** - See details
- ⏸️ **Pause** - Stop temporarily
- 🗑️ **Delete** - Remove

**For Paused Recurring:**
- 👁️ **View** - See details
- ▶️ **Activate** - Resume
- 🗑️ **Delete** - Remove

### Reminder Actions:

**On Upcoming Tab:**
- View all invoices needing reminders soon
- See countdown: "3 days", "7 days"

**On Overdue Tab:**
- See all overdue invoices
- Click "Send Now" to send immediate reminder

**On History Tab:**
- View all sent reminders
- Filter by status: Sent, Pending, Failed

---

## 🎨 Visual Indicators

### Status Badges:

**Recurring Invoices:**
- 🟢 **Active** - Green badge
- 🟡 **Paused** - Yellow badge
- ⚫ **Completed** - Gray badge

**Reminders:**
- 🔵 **7-Day** - Blue badge
- 🟡 **3-Day** - Yellow badge
- 🔴 **Overdue** - Red badge

### Statistics Colors:
- 🔵 **Primary** - Total counts
- 🟢 **Success** - Active/Sent
- 🟡 **Warning** - Pending/Paused
- 🔴 **Danger** - Overdue
- ℹ️ **Info** - Generated/Queue

---

## 🖱️ Tips for Navigation

### Sidebar:
- Click menu items to navigate
- Active page is highlighted
- Icons help identify sections

### Dashboards:
- Statistics at top show key metrics
- Tables below list all items
- Action buttons on right side

### Modals:
- Click buttons to open forms
- Fill required fields (marked with *)
- Cancel or Submit

### Tabs:
- Click tab headers to switch views
- Each tab shows different data
- Active tab is highlighted

---

## 📞 Need Help?

### Documentation Available:
- 📘 **QUICK_START.md** - Quick start guide
- 📗 **REMINDERS_RECURRING_SETUP.md** - Full setup
- 📙 **IMPLEMENTATION_SUMMARY.md** - Technical details
- 📕 **ACCESS_GUIDE.md** - This file

### Troubleshooting:
1. Check if logged in
2. Verify Admin/Accountant role
3. Clear browser cache
4. Check logs in `logs/` folder
5. Run `test_setup.php` to verify

---

## ✅ Quick Checklist

Before using the features:
- [ ] Logged in as Admin or Accountant
- [ ] Database updates applied
- [ ] Can see new menu items
- [ ] Pages load without errors
- [ ] Cron jobs configured (for automation)
- [ ] Email settings configured (optional)

---

## 🎉 You're Ready!

The features are now accessible and ready to use!

**Start by:**
1. Creating your first recurring invoice
2. Configuring reminder settings
3. Monitoring the dashboards

**Enjoy your automated invoicing system!** 🚀
