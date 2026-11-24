# Quick Start Guide - Role-Based Access Control Testing

## Test Users Created

| Username   | Password    | Role       | Description                           |
|------------|-------------|------------|---------------------------------------|
| admin      | password123 | Admin      | Full system access                    |
| accountant | password123 | Accountant | Financial management access           |
| customer   | password123 | Customer   | Linked to "Acme Corp" customer record |
| khan       | (existing)  | Customer   | Existing user converted to Customer   |

## Quick Test Steps

### Test 1: Admin Role
1. Login: `admin` / `password123`
2. ✅ See all menu items (Dashboard, Create Invoice, Invoices, Customers, Products, Reports, System Users)
3. ✅ Red "Admin" badge in top navigation
4. ✅ Can access all pages
5. ✅ Can create/edit/delete all records
6. ✅ Dashboard shows all data statistics

### Test 2: Accountant Role
1. Login: `accountant` / `password123`
2. ✅ See most menu items except "System Users"
3. ✅ Green "Accountant" badge in top navigation
4. ✅ Can manage invoices, customers, products, reports
5. ❌ Cannot access system_users.php
6. ✅ Dashboard shows all data statistics

### Test 3: Customer Role
1. Login: `customer` / `password123`
2. ✅ Only see "Dashboard" and "Invoices" menu items
3. ✅ Blue "Customer" badge in top navigation
4. ✅ Dashboard shows only their data
5. ✅ Invoices page shows only their invoices (for Acme Corp)
6. ❌ Cannot create, edit, or delete invoices
7. ✅ Can download their invoice PDFs
8. ❌ Cannot access customers.php, products.php, reports.php, system_users.php

## Expected Behavior by Page

### Dashboard
- **Admin/Accountant**: See all invoices, customers, products stats
- **Customer**: See only their invoices and personal revenue

### Invoices
- **Admin/Accountant**: See all invoices, Edit/Delete/Share buttons visible
- **Customer**: See only their invoices, only Download button visible

### Customers, Products, Reports
- **Admin/Accountant**: Full access
- **Customer**: Redirected to dashboard with error message

### System Users
- **Admin**: Full access
- **Accountant/Customer**: Redirected to dashboard with error message

## Creating Customer User Accounts

### ✨ NEW: Automated Method (Recommended)

The easiest way to create customer user accounts:

1. Login as **Admin** or **Accountant**
2. Navigate to **Customers** page
3. In the "Add Customer" form:
   - Fill in customer details (name, email, phone, address)
   - ✅ **Check "Create User Account" checkbox**
   - Enter username (min 3 chars) and password (min 6 chars)
4. Click **"Save Customer"**
5. Done! The system automatically links the account

### Manual SQL Method (For Existing Customers)

If you need to give login access to existing customers:

```sql
-- Create user account for customer
INSERT INTO system_users (name, username, email, password, role) 
VALUES ('Customer Name', 'username', 'email@example.com', 
        '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
        'Customer');

-- Link to customer record (replace IDs as needed)
UPDATE customers SET user_id = LAST_INSERT_ID() WHERE id = [customer_id];
```

## Testing Unauthorized Access

Try these direct URL accesses to test security:

### As Customer User:
- http://localhost/Invox1/customers.php → Should redirect with error
- http://localhost/Invox1/products.php → Should redirect with error
- http://localhost/Invox1/reports.php → Should redirect with error
- http://localhost/Invox1/system_users.php → Should redirect with error
- http://localhost/Invox1/create_invoice.php → Should redirect with error

### As Accountant User:
- http://localhost/Invox1/system_users.php → Should redirect with error

## Troubleshooting

### Customer sees no invoices
- Create an invoice for "Acme Corp" customer first
- Verify customer user is linked: `SELECT * FROM customers WHERE user_id = 6;`

### "Not linked to customer profile" error
- Customer user exists but not linked to customer record
- Run: `UPDATE customers SET user_id = 6 WHERE id = 1;` (adjust IDs as needed)

### Role not showing correctly
- Verify role in database: `SELECT username, role FROM system_users;`
- Clear browser cache and login again

## Password Hash for New Users

Use this hash for password: `password123`
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

---

**Ready to Test!** Start by logging in with different user accounts and verify the access controls work as expected.
