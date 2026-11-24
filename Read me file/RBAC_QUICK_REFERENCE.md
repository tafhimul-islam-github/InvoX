# 🔐 RBAC Quick Reference Card

## Test Accounts
```
┌──────────────┬─────────────┬────────────┐
│ Username     │ Password    │ Role       │
├──────────────┼─────────────┼────────────┤
│ admin        │ password123 │ Admin      │
│ accountant   │ password123 │ Accountant │
│ customer     │ password123 │ Customer   │
└──────────────┴─────────────┴────────────┘
```

## Role Badges
- 🔴 Admin (Red)
- 🟢 Accountant (Green)  
- 🔵 Customer (Blue)

## Access Matrix
```
┌─────────────────┬───────┬────────────┬──────────┐
│ Feature         │ Admin │ Accountant │ Customer │
├─────────────────┼───────┼────────────┼──────────┤
│ Dashboard       │  All  │    All     │   Own    │
│ Create Invoice  │   ✅  │     ✅     │    ❌    │
│ Edit Invoice    │   ✅  │     ✅     │    ❌    │
│ Delete Invoice  │   ✅  │     ✅     │    ❌    │
│ View Invoices   │  All  │    All     │   Own    │
│ Download PDF    │   ✅  │     ✅     │  Own ✅  │
│ Share Email     │   ✅  │     ✅     │    ❌    │
│ Customers       │   ✅  │     ✅     │    ❌    │
│ Products        │   ✅  │     ✅     │    ❌    │
│ Reports         │   ✅  │     ✅     │    ❌    │
│ System Users    │   ✅  │     ❌     │    ❌    │
│ Profile         │   ✅  │     ✅     │    ✅    │
└─────────────────┴───────┴────────────┴──────────┘
```

## Common Tasks

### Link Customer to User
```sql
UPDATE customers 
SET user_id = [user_id] 
WHERE id = [customer_id];
```

### Create New Customer User
```sql
-- 1. Create user
INSERT INTO system_users (name, username, email, password, role) 
VALUES ('Name', 'username', 'email@example.com', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
'Customer');

-- 2. Link to customer
UPDATE customers 
SET user_id = LAST_INSERT_ID() 
WHERE email = 'email@example.com';
```

### Check User Role
```sql
SELECT username, role FROM system_users;
```

### Check Customer Linkage
```sql
SELECT c.id, c.name, c.user_id, u.username 
FROM customers c 
LEFT JOIN system_users u ON c.user_id = u.id;
```

## Security Checklist
- ✅ Session-based auth
- ✅ Role validation on every page
- ✅ SQL queries filtered by role
- ✅ Direct URL access blocked
- ✅ Customer data isolated
- ✅ UI elements role-aware

## Key Functions (in db.php)
```php
checkLogin()                    // Verify logged in
isAdmin()                       // Check admin
isAccountant()                  // Check accountant
isCustomer()                    // Check customer
hasAnyRole(['Admin', 'Acc...']) // Check multiple
requireRole('Admin')            // Enforce role
getCustomerIdForUser($pdo, $id) // Get customer ID
```

## File Locations
- 📁 Database: `update_rbac.sql`
- 📁 Docs: `RBAC_IMPLEMENTATION.md`
- 📁 Test: `TESTING_GUIDE.md`
- 📁 Visual: `RBAC_VISUAL_GUIDE.md`

## Need Help?
1. Check [RBAC_IMPLEMENTATION.md](RBAC_IMPLEMENTATION.md) for detailed guide
2. See [TESTING_GUIDE.md](TESTING_GUIDE.md) for test steps
3. Review [RBAC_CHECKLIST.md](RBAC_CHECKLIST.md) for verification

---
**Print this card for quick reference!**
