# InvoX Test Suite - Manual Test Cases Documentation

## Project Overview
InvoX is a comprehensive Invoice Management System built with raw PHP (no frameworks), MySQL database, and Bootstrap frontend. This document contains structured manual test cases for all system modules.

---

## Module 1: Authentication

### TC-001: Valid Login
**Test Sequence ID:** 1  
**Test Description:** Verify user can login with valid credentials  
**Test Case ID:** TC-001  
**Test Case Description:** User logs in with correct username and password  

**Pre-condition:** User account exists in the database with username "admin" and password "admin123"

**Test Steps:**
1. Navigate to login page (index.php)
2. Enter username: "admin"
3. Enter password: "admin123"
4. Click "Login" button

**Test Data:**
- Username: admin
- Password: admin123

**Post-condition:** User is authenticated and session is created

**Expected Result:**
- User is redirected to dashboard.php
- Session variables are set: user_id, role, name
- User role is "Admin"

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-002: Invalid Username Login
**Test Sequence ID:** 2  
**Test Description:** Verify login fails with non-existent username  
**Test Case ID:** TC-002  
**Test Case Description:** Attempt login with invalid username  

**Pre-condition:** None

**Test Steps:**
1. Navigate to login page
2. Enter username: "invaliduser"
3. Enter password: "admin123"
4. Click "Login" button

**Test Data:**
- Username: invaliduser
- Password: admin123

**Post-condition:** User remains on login page

**Expected Result:**
- Error message "Invalid credentials" is displayed
- No session is created
- User remains on login page

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-003: Invalid Password Login
**Test Sequence ID:** 3  
**Test Description:** Verify login fails with wrong password  
**Test Case ID:** TC-003  
**Test Case Description:** Attempt login with valid username but wrong password  

**Pre-condition:** User "admin" exists in database

**Test Steps:**
1. Navigate to login page
2. Enter username: "admin"
3. Enter password: "wrongpass"
4. Click "Login" button

**Test Data:**
- Username: admin
- Password: wrongpass

**Post-condition:** User remains on login page

**Expected Result:**
- Error message "Invalid credentials" is displayed
- No session is created
- Password verification fails securely

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-004: Empty Credentials Login
**Test Sequence ID:** 4  
**Test Description:** Verify form validation for empty fields  
**Test Case ID:** TC-004  
**Test Case Description:** Attempt to submit login form with empty username and password  

**Pre-condition:** None

**Test Steps:**
1. Navigate to login page
2. Leave username field empty
3. Leave password field empty
4. Attempt to click "Login" button

**Test Data:**
- Username: [empty]
- Password: [empty]

**Post-condition:** Form is not submitted

**Expected Result:**
- HTML5 required field validation triggers
- "This field is required" message shown
- Form is not submitted to server

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-005: SQL Injection Login Attempt
**Test Sequence ID:** 5  
**Test Description:** Verify system is protected against SQL injection attacks  
**Test Case ID:** TC-005  
**Test Case Description:** Attempt SQL injection in login form  

**Pre-condition:** None

**Test Steps:**
1. Navigate to login page
2. Enter username: admin' OR '1'='1
3. Enter password: ' OR '1'='1
4. Click "Login" button

**Test Data:**
- Username: admin' OR '1'='1
- Password: ' OR '1'='1

**Post-condition:** Attack is neutralized

**Expected Result:**
- Login fails
- "Invalid credentials" error shown
- Prepared statements prevent SQL injection
- No unauthorized access granted

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-006: Logout Functionality
**Test Sequence ID:** 6  
**Test Description:** Verify user can logout successfully  
**Test Case ID:** TC-006  
**Test Case Description:** Logged-in user performs logout  

**Pre-condition:** User is logged in with active session

**Test Steps:**
1. User is logged into the system
2. Click "Logout" link in navigation
3. Verify redirection
4. Attempt to access protected page

**Test Data:**
- Session user_id: 1

**Post-condition:** Session is destroyed

**Expected Result:**
- User is redirected to login page (index.php)
- Session is destroyed completely
- All session variables are cleared
- Cannot access protected pages using back button

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

## Module 2: Authorization

### TC-007: Access Dashboard Without Login
**Test Sequence ID:** 7  
**Test Description:** Verify unauthorized access is prevented  
**Test Case ID:** TC-007  
**Test Case Description:** Unauthenticated user attempts to access dashboard  

**Pre-condition:** User is not logged in (no active session)

**Test Steps:**
1. Clear browser cookies/session
2. Navigate directly to dashboard.php URL
3. Observe behavior

**Test Data:**
- Session user_id: null

**Post-condition:** Access denied

**Expected Result:**
- checkLogin() function detects no session
- User is redirected to index.php (login page)
- No dashboard data is exposed

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

## Module 3: Customer Management

### TC-011: Add New Customer Valid Data
**Test Sequence ID:** 11  
**Test Description:** Verify customer can be added with valid data  
**Test Case ID:** TC-011  
**Test Case Description:** Create new customer with complete information  

**Pre-condition:** User is logged in

**Test Steps:**
1. Login to system
2. Navigate to Customers page
3. Enter customer name: "John Doe"
4. Enter email: "john@example.com"
5. Enter phone: "1234567890"
6. Enter address: "123 Main St"
7. Click "Save Customer" button

**Test Data:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "phone": "1234567890",
  "address": "123 Main St"
}
```

**Post-condition:** Customer is added to database

**Expected Result:**
- Customer is inserted into customers table
- Success message or redirect to customers list
- New customer appears in the customer list
- All data is stored correctly

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-018: Add Customer with XSS Script
**Test Sequence ID:** 18  
**Test Description:** Verify XSS attack is prevented  
**Test Case ID:** TC-018  
**Test Case Description:** Attempt to inject JavaScript via customer name field  

**Pre-condition:** User is logged in

**Test Steps:**
1. Login to system
2. Navigate to Customers page
3. Enter customer name: <script>alert('XSS')</script>
4. Enter email: "test@test.com"
5. Fill other fields
6. Click "Save Customer"
7. View customer list

**Test Data:**
```json
{
  "name": "<script>alert('XSS')</script>",
  "email": "test@test.com",
  "phone": "123",
  "address": "Test"
}
```

**Post-condition:** XSS is neutralized

**Expected Result:**
- Customer is saved
- Script is stored in database as plain text
- When displayed, htmlspecialchars() escapes the script
- Script does not execute in browser
- Display shows: &lt;script&gt;alert('XSS')&lt;/script&gt;

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

## Module 4: Invoice Management

### TC-026: Create Invoice Valid Data
**Test Sequence ID:** 26  
**Test Description:** Verify invoice can be created with valid data  
**Test Case ID:** TC-026  
**Test Case Description:** Create complete invoice with customer, products, and dates  

**Pre-condition:** User is logged in, customers and products exist in database

**Test Steps:**
1. Login to system
2. Navigate to "Create Invoice" page
3. Select customer from dropdown
4. Set issue date: 2025-12-14
5. Set due date: 2026-01-13
6. Add product (Service 1, Quantity: 2)
7. Verify calculations (Subtotal, Tax 10%, Total)
8. Click "Generate Invoice"

**Test Data:**
```json
{
  "customer": "1",
  "issue_date": "2025-12-14",
  "due_date": "2026-01-13",
  "product_id": ["1"],
  "qty": ["2"],
  "subtotal": "3000.00",
  "tax": "300.00",
  "discount": "0",
  "total": "3300.00"
}
```

**Post-condition:** Invoice and invoice items are saved

**Expected Result:**
- Invoice record inserted into invoices table
- Invoice number generated (INV-timestamp format)
- Invoice items inserted into invoice_items table
- Status set to "Open"
- Tax calculated at 10% of subtotal
- User redirected to invoices list

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-030: Invoice Tax Calculation
**Test Sequence ID:** 30  
**Test Description:** Verify tax is calculated correctly at 10%  
**Test Case ID:** TC-030  
**Test Case Description:** Test automatic tax calculation during invoice creation  

**Pre-condition:** User is creating an invoice

**Test Steps:**
1. Create new invoice
2. Add items with subtotal = 1000.00
3. Observe tax calculation
4. Verify total = subtotal + tax - discount

**Test Data:**
```json
{
  "subtotal": "1000.00",
  "tax_rate": "0.10"
}
```

**Post-condition:** Correct calculations applied

**Expected Result:**
- Tax = 100.00 (10% of 1000.00)
- Total = 1100.00 (1000 + 100 - 0)
- JavaScript calcTotals() function works correctly
- Tax rate is hardcoded to 10%

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

## Module 5: Reports

### TC-044: Generate Monthly Report
**Test Sequence ID:** 44  
**Test Description:** Verify monthly report can be generated  
**Test Case ID:** TC-044  
**Test Case Description:** Generate report for specific month with invoice aggregations  

**Pre-condition:** User is logged in, invoices exist in date range

**Test Steps:**
1. Login to system
2. Navigate to Reports page
3. Select report type: "Monthly"
4. Set start date: 2025-12-01
5. Set end date: 2025-12-31
6. Click "Generate Report"

**Test Data:**
```json
{
  "report_type": "Monthly",
  "start_date": "2025-12-01",
  "end_date": "2025-12-31"
}
```

**Post-condition:** Report is generated and saved

**Expected Result:**
- Report record inserted into reports table
- Total invoices counted correctly for date range
- Total amount summed (SUM of invoice totals)
- Paid invoices counted (status = 'Paid')
- Pending invoices counted (status != 'Paid')
- Report appears in generated reports list

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

## Module 6: Security Tests

### TC-056: SQL Injection in Search
**Test Sequence ID:** 56  
**Test Description:** Verify search functionality is protected against SQL injection  
**Test Case ID:** TC-056  
**Test Case Description:** Attempt SQL injection via customer search field  

**Pre-condition:** User is logged in, customers exist in database

**Test Steps:**
1. Login to system
2. Navigate to Customers page
3. Enter search term: ' OR '1'='1
4. Click "Search" button
5. Observe results

**Test Data:**
```json
{
  "search": "' OR '1'='1"
}
```

**Post-condition:** SQL injection neutralized

**Expected Result:**
- Prepared statement with parameterized query is used
- SQL injection string is treated as literal search text
- No customers are returned (no match for literal string)
- All customer records remain safe
- Database integrity maintained

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

### TC-058: Password Hash Verification
**Test Sequence ID:** 58  
**Test Description:** Verify passwords are hashed and verified securely  
**Test Case ID:** TC-058  
**Test Case Description:** Confirm password_hash() and password_verify() are used  

**Pre-condition:** User account exists

**Test Steps:**
1. Check database users table
2. Verify password column contains hash, not plain text
3. Attempt login with correct password
4. Verify password_verify() function is used
5. Check hash algorithm (bcrypt/argon2)

**Test Data:**
```json
{
  "username": "admin",
  "password": "admin123"
}
```

**Post-condition:** Secure password handling confirmed

**Expected Result:**
- Password in database is hashed (60+ character string)
- Password is NOT stored as plain text
- password_verify() function is used in login logic
- Hash comparison is secure
- Bcrypt or Argon2 algorithm used

**Actual Result:** _[To be filled during execution]_

**Status:** _[Pass/Fail]_

**Remarks:** _[Any observations]_

---

## Test Execution Summary

| Module | Total Cases | Passed | Failed | Blocked | Not Executed |
|--------|-------------|--------|--------|---------|--------------|
| Authentication | 6 | - | - | - | - |
| Authorization | 4 | - | - | - | - |
| Customer Management | 8 | - | - | - | - |
| Product Management | 7 | - | - | - | - |
| Invoice Management | 11 | - | - | - | - |
| System Users | 7 | - | - | - | - |
| Reports | 7 | - | - | - | - |
| Dashboard | 2 | - | - | - | - |
| Security | 8 | - | - | - | - |
| **TOTAL** | **60** | **-** | **-** | **-** | **-** |

---

## Test Environment

- **OS:** Windows
- **Web Server:** Laragon (Apache + MySQL)
- **PHP Version:** 7.4+
- **Database:** MySQL (invox_db)
- **Browser:** Chrome/Firefox (latest versions)
- **Testing Tool:** PHPUnit 9.5+

---

## Notes for Test Execution

1. **Pre-requisites:**
   - Laragon must be running
   - Database invox_db must be created with schema
   - Default admin user must exist
   - Test data should be reset before each test cycle

2. **Test Data Management:**
   - Use dedicated test database or backup production data
   - Reset database to known state before testing
   - Document any test data created

3. **Defect Reporting:**
   - Screenshot evidence for failures
   - Browser console errors
   - PHP error logs
   - Database state verification

4. **Automation:**
   - Manual tests documented above
   - Automated tests available in /tests directory
   - Run PHPUnit: `vendor/bin/phpunit`

---

**Document Version:** 1.0  
**Date:** December 14, 2025  
**Prepared By:** QA Automation Engineer  
**Project:** InvoX Invoice Management System
