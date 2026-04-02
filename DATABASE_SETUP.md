# VoteFlow - MySQL Database Setup Guide

## 📊 Database Overview

VoteFlow now includes a complete MySQL database schema with:
- **7 main tables** for data storage
- **3 views** for simplified querying
- **4 stored procedures** for secure operations
- **3 triggers** for data integrity
- **Comprehensive indexes** for performance
- **Sample data** for testing

---

## 🚀 Quick Start - Setup Instructions

### Step 1: Create Database

Copy the entire content from `sql/schema.sql` and execute it in MySQL Workbench or phpMyAdmin:

```sql
-- In MySQL Workbench or phpMyAdmin:
-- 1. Create new query
-- 2. Paste content from sql/schema.sql
-- 3. Execute (Ctrl+Enter)
```

**OR** Use command line:

```bash
mysql -u root -p < C:\xampp\htdocs\scm\sql\schema.sql
```

### Step 2: Verify Setup

Check that everything was created:

```sql
USE voteflow;
SHOW TABLES;
SELECT * FROM voters;
SELECT * FROM candidates;
SELECT * FROM admin_users;
```

### Step 3: Test Database Connection

The PHP files will automatically connect using:
- **Host**: localhost
- **User**: voteflow_app
- **Password**: app_pass_123
- **Database**: voteflow

---

## 📋 Database Schema

### 1. **VOTERS TABLE**
Store voter information and voting status.

```sql
Fields:
- voter_id (VARCHAR 50) - Primary Key, e.g., "V001"
- voter_name (VARCHAR 100) - Full name
- password_hash (VARCHAR 255) - SHA256 hashed password
- email (VARCHAR 100) - Email address
- phone (VARCHAR 20) - Phone number
- has_voted (BOOLEAN) - Voting status
- voted_for (INT) - Foreign Key to candidate_id
- created_at (TIMESTAMP) - Account creation time
- last_login (TIMESTAMP) - Last login time
```

**Default Voters (Test Credentials):**
- V001, V002, V003 - Password: `voter123`

---

### 2. **CANDIDATES TABLE**
Store candidate information.

```sql
Fields:
- candidate_id (INT) - Auto-increment Primary Key
- candidate_name (VARCHAR 150) - Candidate name (UNIQUE)
- party_name (VARCHAR 100) - Political party
- description (TEXT) - Candidate biography
- image_url (VARCHAR 255) - Candidate photo
- vote_count (INT) - Total votes received
- created_at (TIMESTAMP) - Creation date
- updated_at (TIMESTAMP) - Last update (auto-updates)
```

**Sample Candidates:**
1. Alice Johnson - Progressive Alliance
2. Bob Smith - Democratic Union
3. Carol Davis - Green Party
4. David Wilson - Conservative Party
5. Emma Thompson - Socialist Movement

---

### 3. **VOTES TABLE**
Audit trail of all votes cast.

```sql
Fields:
- vote_id (INT) - Auto-increment Primary Key
- voter_id (VARCHAR 50) - Foreign Key to voters
- candidate_id (INT) - Foreign Key to candidates
- vote_timestamp (TIMESTAMP) - When vote was cast
- ip_address (VARCHAR 45) - Voter's IP address
- user_agent (VARCHAR 255) - Browser info
```

**Key Features:**
- UNIQUE constraint ensures one vote per voter
- Audit trail for security
- IP logging for fraud detection

---

### 4. **ADMIN_USERS TABLE**
Administrator accounts.

```sql
Fields:
- admin_id (INT) - Auto-increment Primary Key
- username (VARCHAR 50) - Login username (UNIQUE)
- password_hash (VARCHAR 255) - SHA256 hashed password
- email (VARCHAR 100) - Admin email
- full_name (VARCHAR 100) - Full name
- role (ENUM) - 'superadmin', 'admin', or 'moderator'
- is_active (BOOLEAN) - Account status
- created_at (TIMESTAMP) - Creation date
- last_login (TIMESTAMP) - Last login
```

**Default Admin (Test Credentials):**
- Username: `admin`
- Password: `admin123`
- Role: superadmin

---

### 5. **ELECTION_SETTINGS TABLE**
Configuration and settings.

```sql
Fields:
- setting_id (INT) - Auto-increment Primary Key
- setting_key (VARCHAR 100) - Setting name (UNIQUE)
- setting_value (VARCHAR 255) - Setting value
- setting_type (ENUM) - 'string', 'integer', 'boolean', 'json'
- description (TEXT) - Description
- updated_at (TIMESTAMP) - Last update
```

**Pre-configured Settings:**
- election_title: "General Election 2026"
- election_status: "active"
- one_vote_per_person: "true"
- results_visibility: "public"
- auto_refresh_interval: "30000" (milliseconds)

---

### 6. **VOTING_LOGS TABLE**
Complete audit log of all actions.

```sql
Fields:
- log_id (INT) - Auto-increment Primary Key
- action (VARCHAR 50) - Action type (LOGIN, VOTE_CAST, etc.)
- voter_id (VARCHAR 50) - Foreign Key to voters
- admin_id (INT) - Foreign Key to admin_users
- candidate_id (INT) - Foreign Key to candidates
- action_details (TEXT) - Additional details
- ip_address (VARCHAR 45) - IP address
- log_timestamp (TIMESTAMP) - Log time
```

**Actions Tracked:**
- LOGIN_SUCCESS, LOGIN_FAILED
- VOTE_CAST, VOTE_ERROR
- CANDIDATE_ADDED, CANDIDATE_DELETED
- ADMIN_LOGIN_SUCCESS, ADMIN_LOGIN_FAILED

---

### 7. **ELECTION_STATISTICS TABLE**
Overall election metrics.

```sql
Fields:
- stat_id (INT) - Auto-increment Primary Key
- total_voters (INT) - Total voter count
- total_votes_cast (INT) - Votes received
- total_candidates (INT) - Candidate count
- election_start (TIMESTAMP) - Election start time
- election_end (TIMESTAMP) - Election end time
- last_updated (TIMESTAMP) - Last update
```

Auto-updated by triggers when data changes.

---

## 🔍 Views (Simplified Queries)

### vw_election_results
Real-time election results with rankings.

```sql
SELECT * FROM vw_election_results;
```

**Returns:**
- candidate_id, name, party
- vote_count, vote_percentage
- ranking (1st, 2nd, etc.)
- status (LEADING/OTHER)

---

### vw_voter_statistics
Voter participation stats.

```sql
SELECT * FROM vw_voter_statistics;
```

**Returns:**
- total_voters
- voters_voted, voters_not_voted
- participation_rate (%)

---

### vw_candidate_performance
Candidate performance analysis.

```sql
SELECT * FROM vw_candidate_performance;
```

**Returns:**
- Candidate info
- Total votes
- Vote count vs recorded votes

---

## 🔒 Stored Procedures

### sp_cast_vote
Safely record a vote with validation.

```sql
CALL sp_cast_vote(
    'V001',           -- p_voter_id
    1,                -- p_candidate_id
    '192.168.1.1',    -- p_ip_address
    @success,         -- OUT p_success
    @message          -- OUT p_message
);

SELECT @success, @message;
```

**Validations:**
- Voter exists
- Candidate exists
- Voter hasn't already voted
- Records vote atomically

---

### sp_add_candidate
Add new candidate with validation.

```sql
CALL sp_add_candidate(
    'John Candidate',     -- p_candidate_name
    'My Party',           -- p_party_name
    'Bio here...',        -- p_description
    @success,             -- OUT p_success
    @message              -- OUT p_message
);
```

**Validations:**
- Name not empty
- Name doesn't already exist

---

### sp_delete_candidate
Safely delete candidate and handle votes.

```sql
CALL sp_delete_candidate(
    5,                -- p_candidate_id
    @success,         -- OUT p_success
    @message          -- OUT p_message
);
```

**Actions:**
- Deletes votes for candidate
- Resets has_voted flag for voters
- Removes candidate record

---

### sp_get_election_results
Get sorted election results.

```sql
CALL sp_get_election_results();
```

---

## 🔐 User Roles & Permissions

Three database users created for security:

### voteflow_read (Read-only)
```
User: voteflow_read
Password: read_pass_123
Permissions: SELECT only
```
Use for public results page.

### voteflow_app (Application)
```
User: voteflow_app
Password: app_pass_123
Permissions: SELECT, INSERT, UPDATE
```
Use for standard PHP backend.

### voteflow_admin (Full Access)
```
User: voteflow_admin
Password: admin_pass_123
Permissions: ALL PRIVILEGES
```
Use for database maintenance only.

---

## 📡 PHP API Endpoints

### Authentication
- `php/login.php` (POST) - Voter login
- `php/adminLogin.php` (POST) - Admin login

### Candidates
- `php/getCandidates.php` (GET) - List all candidates
- `php/addCandidate.php` (POST) - Add candidate (admin)
- `php/deleteCandidate.php` (POST) - Delete candidate (admin)

### Voting
- `php/vote.php` (POST) - Submit vote
- `php/results.php` (GET) - Get election results

---

## 📊 Integration with Frontend

### Updating JavaScript to Use Database

**Original** (JSON-only):
```javascript
await Candidates.loadCandidates();
```

**With Database**:
```javascript
// Change API endpoints in app.js
const API_BASE = 'http://localhost/scm/php';

// Login
const response = await fetch(`${API_BASE}/login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ voterId, password })
});

// Get candidates
const response = await fetch(`${API_BASE}/getCandidates.php`);

// Vote
const response = await fetch(`${API_BASE}/vote.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ voterId, candidateId })
});
```

---

## 🔍 SQL Query Examples

### Get Top 3 Candidates
```sql
SELECT * FROM vw_election_results LIMIT 3;
```

### Get Voter Participation Rate
```sql
SELECT * FROM vw_voter_statistics;
```

### Get Voting Logs (Last 24 Hours)
```sql
SELECT * FROM voting_logs
WHERE log_timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR)
ORDER BY log_timestamp DESC;
```

### Get All Votes for a Candidate
```sql
SELECT voter_id, vote_timestamp
FROM votes
WHERE candidate_id = 1
ORDER BY vote_timestamp DESC;
```

### Get Voters Who Haven't Voted
```sql
SELECT voter_id, voter_name, email
FROM voters
WHERE has_voted = FALSE;
```

### Export Results to CSV Format
```sql
SELECT 
    candidate_name,
    party_name,
    vote_count,
    vote_percentage
FROM vw_election_results
ORDER BY ranking ASC;
```

---

## 🛠️ Database Maintenance

### Backup Database
```bash
mysqldump -u root -p voteflow > voteflow_backup.sql
```

### Restore Database
```bash
mysql -u root -p voteflow < voteflow_backup.sql
```

### Reset Election (Clear All Votes)
```sql
DELETE FROM votes;
UPDATE candidates SET vote_count = 0;
UPDATE voters SET has_voted = FALSE, voted_for = NULL;
TRUNCATE TABLE voting_logs;
UPDATE election_statistics SET total_votes_cast = 0;
```

### Add More Test Voters
```sql
INSERT INTO voters (voter_id, voter_name, password_hash, email)
VALUES ('V004', 'New Voter', SHA2('voter123', 256), 'new@example.com');
```

---

## ⚠️ Important Security Notes

1. **Password Hashing**: All passwords are SHA256 hashed
   ```sql
   -- Hash a password
   SELECT SHA2('mypassword', 256);
   ```

2. **SQL Injection Prevention**: All PHP uses prepared statements
   ```php
   $stmt = $conn->prepare("SELECT * FROM voters WHERE voter_id = ?");
   $stmt->bind_param('s', $voterId);
   ```

3. **CORS Headers** (if needed):
   ```php
   header('Access-Control-Allow-Origin: *');
   header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
   header('Access-Control-Allow-Headers: Content-Type');
   ```

4. **Rate Limiting**: Consider adding rate limiting to prevent abuse
   ```php
   // Check voting attempts per IP
   SELECT COUNT(*) FROM voting_logs
   WHERE ip_address = ? AND log_timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR);
   ```

---

## 🐛 Troubleshooting

### "Access denied for user 'voteflow_app'@'localhost'"
- Ensure database users were created by schema.sql
- Run: `FLUSH PRIVILEGES;`

### "Table doesn't exist"
- Verify all SQL commands executed successfully
- Check database: `USE voteflow; SHOW TABLES;`

### "Duplicate entry for key"
- Clear test data: `TRUNCATE TABLE votes; TRUNCATE TABLE voting_logs;`
- Reset voters: `UPDATE voters SET has_voted = FALSE, voted_for = NULL;`

### "Stored procedure not found"
- Ensure `DELIMITER $$` and `DELIMITER ;` are properly used
- Re-run the stored procedure creation section

---

## 📈 Performance Tuning

All tables include optimized indexes:
- **voters**: Index on has_voted, email, created_at
- **candidates**: Index on party, vote_count, created_at
- **votes**: Indexes on voter_id, candidate_id, timestamp
- **voting_logs**: Indexes on action, timestamp, ip_address

For large datasets, monitor with:
```sql
SHOW INDEX FROM candidates;
ANALYZE TABLE candidates;
EXPLAIN SELECT * FROM vw_election_results;
```

---

## 📞 Support

For database issues:
1. Check PHP error logs: `C:\xampp\logs\error.log`
2. Enable MySQL query log: `SET GLOBAL general_log = 'ON';`
3. Verify user permissions: `SHOW GRANTS FOR 'voteflow_app'@'localhost';`

---

**Last Updated**: April 2026  
**Version**: 1.0.0  
**Compatible With**: MySQL 5.7+, MariaDB 10.2+
