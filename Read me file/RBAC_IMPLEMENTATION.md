# Role-Based Access Control (RBAC) Implementation Guide

## Overview
This document describes the role-based access control system implemented in the InvoX invoice management system. The system supports three user roles: Admin, Accountant, and Customer.

---

## Roles and Permissions

### 1. Admin Role
**Full System Access**
- ✅ View and manage all invoices
- ✅ Create, edit, and delete invoices
- ✅ View and manage all customers
- ✅ View and manage all products
- ✅ Generate and view reports
- ✅ Manage system users (create, edit, delete users)
- ✅ Access all dashboard statistics

### 2. Accountant Role
**Financial Management Access**
- ✅ View and manage all invoices
- ✅ Create, edit, and delete invoices
- ✅ View and manage all customers
- ✅ View and manage all products
- ✅ Generate and view reports
- ❌ Cannot manage system users
- ✅ Access all dashboard statistics

### 3. Customer Role
**Limited Self-Service Access**
- ✅ View only their own invoices
- ✅ Download their own invoices (PDF)
- ❌ Cannot create, edit, or delete invoices
- ❌ Cannot view other customers' data
- ❌ Cannot access customer management
- ❌ Cannot access product management
- ❌ Cannot generate reports
- ❌ Cannot manage system users
- ✅ View personalized dashboard (only their data)

---

## Database Setup

### Step 1: Run the RBAC Update Script
Execute the SQL script to update your database schema:

```bash
mysql -u root -p invox_db < update_rbac.sql
```

This script will:
- Update the `system_users` table to use role enum ('Admin', 'Accountant', 'Customer')
- Add `user_id` column to `customers` table to link customers with user accounts
- Create test users for each role (password: password123)
  - Username: `admin` (Admin role)
  - Username: `accountant` (Accountant role)
  - Username: `customer` (Customer role)

### Step 2: Link Existing Customers to User Accounts
For customers who need login access, you must link their customer record to a system user:

```sql
-- Example: Link customer ID 1 to user ID 5
UPDATE customers SET user_id = 5 WHERE id = 1;
```

---

## Technical Implementation

### Core Functions (in db.php)

#### Authentication Check
```php
checkLogin() - Ensures user is logged in
```

#### Role Checking Functions
```php
hasRole($role) - Check if user has specific role
hasAnyRole($roles) - Check if user has any of the specified roles
isAdmin() - Check if user is Admin
isAccountant() - Check if user is Accountant
isCustomer() - Check if user is Customer
```

#### Authorization Functions
```php
requireRole($role) - Require specific role or redirect
requireAnyRole($roles) - Require any of specified roles or redirect
getCustomerIdForUser($pdo, $user_id) - Get customer ID for logged-in customer user
```

### Page-Level Protection

#### Admin Only Pages
```php
system_users.php - User management
```

#### Admin + Accountant Pages
```php
customers.php - Customer management
products.php - Product management
reports.php - Reports generation
create_invoice.php - Invoice creation
edit_invoice.php - Invoice editing
```

#### All Authenticated Users
```php
dashboard.php - Dashboard (filtered by role)
invoices.php - Invoices list (filtered by role)
profile.php - User profile
download_invoice.php - PDF download (filtered by role)
```

---

## UI Changes by Role

### Sidebar Navigation
- **Admin**: All menu items visible
- **Accountant**: All except "System Users"
- **Customer**: Only "Dashboard" and "Invoices"

### Dashboard
- **Admin/Accountant**: Full statistics (all invoices, customers, products, revenue)
- **Customer**: Personal statistics (only their invoices and revenue)

### Invoices Page
- **Admin/Accountant**: 
  - View all invoices
  - Edit, delete, share buttons visible
  - "Create Invoice" button visible
  
- **Customer**: 
  - View only their invoices
  - Only "Download PDF" button visible
  - No edit/delete/share capabilities

### Role Badge
A colored badge showing the current user's role is displayed in the top navigation:
- **Admin**: Red badge
- **Accountant**: Green badge
- **Customer**: Blue badge

---

## Security Features

### Data Filtering
- Customer users can only query their own data through SQL WHERE clauses
- Direct URL access to unauthorized resources is blocked
- Invoice downloads are validated against customer ownership

### Access Control Flow
1. User logs in → Role stored in session
2. User accesses page → `checkLogin()` validates session
3. Page checks role → `requireRole()` or `requireAnyRole()` enforces access
4. Data queries filtered → Customers see only their data
5. UI elements hidden → Role-based conditional rendering

### Error Handling
- Unauthorized access attempts redirect to dashboard with error message
- Customers without linked customer records see informative error message

---

## Testing the Implementation

### Test Accounts
After running the update script, you can test with these accounts:

| Username   | Password     | Role       |
|------------|--------------|------------|
| admin      | password123  | Admin      |
| accountant | password123  | Accountant |
| customer   | password123  | Customer   |

### Test Cases

#### Test 1: Admin Access
1. Login as `admin`
2. Verify access to all pages including "System Users"
3. Verify ability to create/edit/delete invoices, customers, products

#### Test 2: Accountant Access
1. Login as `accountant`
2. Verify access to invoices, customers, products, reports
3. Verify "System Users" is not visible in menu
4. Try accessing `system_users.php` directly - should redirect with error

#### Test 3: Customer Access
1. Login as `customer`
2. Verify only "Dashboard" and "Invoices" visible in menu
3. Verify dashboard shows only customer's data
4. Verify invoices page shows only customer's invoices
5. Verify only "Download" button is visible (no edit/delete)
6. Try accessing `customers.php` directly - should redirect with error

---

## Creating New Users

### Creating an Admin or Accountant User
```sql
INSERT INTO system_users (name, username, email, password, role) 
VALUES ('John Doe', 'johndoe', 'john@example.com', '$2y$10$...', 'Admin');
```

### Creating a Customer User (AUTOMATED - Recommended Method)
**✨ NEW: Automatic User Account Creation**

1. Login as Admin or Accountant
2. Go to "Customers" page
3. Click "Add Customer" in the form
4. Fill in customer details:
   - Name
   - Email
   - Phone
   - Address
5. **Check the "Create User Account" checkbox**
6. Enter login credentials:
   - Username (min 3 characters)
   - Password (min 6 characters)
7. Click "Save Customer"

The system will automatically:
- Create the customer record
- Create a user account with "Customer" role
- Link the user account to the customer
- Display success message

### Creating a Customer User (Manual Method - SQL)
If you need to create customer users manually via database:

1. First, create the customer record:
```sql
INSERT INTO customers (name, email, phone, address) 
VALUES ('Jane Smith', 'jane@example.com', '555-1234', '123 Main St');
```

2. Then create the user account and link it:
```sql
INSERT INTO system_users (name, username, email, password, role) 
VALUES ('Jane Smith', 'janesmith', 'jane@example.com', '$2y$10$...', 'Customer');

UPDATE customers SET user_id = LAST_INSERT_ID() WHERE email = 'jane@example.com';
```

---

## Password Hashing
To generate a password hash for new users, use this PHP code:

```php
<?php
echo password_hash('your_password_here', PASSWORD_DEFAULT);
?>
```

Or use the existing hash from test users (password123):
```
$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
```

---

## Troubleshooting

### Customer Can't Login
- Verify the user exists in `system_users` with role 'Customer'
- Verify the user is linked to a customer record: `SELECT * FROM customers WHERE user_id = X`

### "Not Linked to Customer Profile" Error
- Customer user exists but not linked to customer record
- Run: `UPDATE customers SET user_id = [user_id] WHERE id = [customer_id]`

### User Sees "Unauthorized" Error
- User role doesn't have permission for that page
- Check user's role: `SELECT role FROM system_users WHERE id = X`
- Verify role matches page requirements

### Menu Items Not Showing/Hiding
- Clear browser cache
- Check session has 'role' set: `var_dump($_SESSION);`
- Verify header.php is using role checking functions

---

## Future Enhancements

Potential improvements to the RBAC system:
- Add "Manager" role with subset of Admin permissions
- Implement permission-based access instead of role-based
- Add audit logging for sensitive actions
- Implement two-factor authentication for Admin users
- Add ability to assign multiple roles to a user
- Create role-based email notifications
- Add "View Only" permission level for Accountants

---

## Files Modified

### Core Files
- `db.php` - Added role checking functions
- `header.php` - Role-based menu visibility and role badge
- `update_rbac.sql` - Database schema updates

### Page Files
- `dashboard.php` - Role-based data filtering
- `invoices.php` - Role-based invoice filtering and UI
- `customers.php` - Admin/Accountant only access
- `products.php` - Admin/Accountant only access
- `reports.php` - Admin/Accountant only access
- `system_users.php` - Admin only access
- `create_invoice.php` - Admin/Accountant only access
- `edit_invoice.php` - Admin/Accountant only access
- `download_invoice.php` - Customer ownership validation

---

## Support

For issues or questions about the RBAC implementation, please refer to:
- This documentation file
- Code comments in `db.php` for function usage
- SQL script comments in `update_rbac.sql`

**Implementation Date**: January 2026  
**Version**: 1.0
