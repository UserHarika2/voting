-- =====================================================
-- VOTEFLOW DATABASE SCHEMA
-- Complete SQL setup for MySQL
-- =====================================================

-- Drop existing database (if you want to start fresh)
-- DROP DATABASE IF EXISTS voteflow_app;

-- Create database
CREATE DATABASE IF NOT EXISTS voteflow_app;
USE voteflow_app;

-- =====================================================
-- 1. VOTERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS app_voters (
    voter_id VARCHAR(50) PRIMARY KEY,
    voter_name VARCHAR(100) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    has_voted BOOLEAN DEFAULT FALSE,
    voted_for INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    FOREIGN KEY (voted_for) REFERENCES app_candidates(candidate_id) ON DELETE SET NULL
);

-- =====================================================
-- 2. CANDIDATES TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS app_candidates (
    candidate_id INT AUTO_INCREMENT PRIMARY KEY,
    candidate_name VARCHAR(150) NOT NULL,
    party_name VARCHAR(100),
    description TEXT,
    image_url VARCHAR(255),
    vote_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_candidate_name (candidate_name)
);

-- =====================================================
-- 3. VOTES TABLE (Audit trail)
-- =====================================================
CREATE TABLE IF NOT EXISTS app_votes (
    vote_id INT AUTO_INCREMENT PRIMARY KEY,
    voter_id VARCHAR(50) NOT NULL,
    candidate_id INT NOT NULL,
    vote_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    FOREIGN KEY (voter_id) REFERENCES app_voters(voter_id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id) REFERENCES app_candidates(candidate_id) ON DELETE CASCADE,
    UNIQUE KEY unique_voter_vote (voter_id)
);

-- =====================================================
-- 4. ADMIN USERS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS app_admin_users (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100),
    full_name VARCHAR(100),
    role ENUM('superadmin', 'admin', 'moderator') DEFAULT 'admin',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL
);

-- =====================================================
-- 5. ELECTION SETTINGS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS app_election_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value VARCHAR(255),
    setting_type ENUM('string', 'integer', 'boolean', 'json') DEFAULT 'string',
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- 6. VOTING LOGS TABLE (Audit & Security)
-- =====================================================
CREATE TABLE IF NOT EXISTS app_voting_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    voter_id VARCHAR(50),
    admin_id INT,
    candidate_id INT,
    action_details TEXT,
    ip_address VARCHAR(45),
    log_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (voter_id) REFERENCES app_voters(voter_id) ON DELETE SET NULL,
    FOREIGN KEY (admin_id) REFERENCES app_admin_users(admin_id) ON DELETE SET NULL,
    FOREIGN KEY (candidate_id) REFERENCES app_candidates(candidate_id) ON DELETE SET NULL
);

-- =====================================================
-- 7. ELECTION STATISTICS TABLE
-- =====================================================
CREATE TABLE IF NOT EXISTS app_election_statistics (
    stat_id INT AUTO_INCREMENT PRIMARY KEY,
    total_voters INT DEFAULT 0,
    total_votes_cast INT DEFAULT 0,
    total_candidates INT DEFAULT 0,
    election_start TIMESTAMP,
    election_end TIMESTAMP,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- INDEXES FOR PERFORMANCE
-- =====================================================

-- Voters indexes
CREATE INDEX idx_voter_has_voted ON app_voters(has_voted);
CREATE INDEX idx_voter_email ON app_voters(email);
CREATE INDEX idx_voter_created ON app_voters(created_at);

-- Candidates indexes
CREATE INDEX idx_candidate_party ON app_candidates(party_name);
CREATE INDEX idx_candidate_votes ON app_candidates(vote_count DESC);
CREATE INDEX idx_candidate_created ON app_candidates(created_at);

-- Votes indexes
CREATE INDEX idx_vote_voter ON app_votes(voter_id);
CREATE INDEX idx_vote_candidate ON app_votes(candidate_id);
CREATE INDEX idx_vote_timestamp ON app_votes(vote_timestamp DESC);

-- Admin indexes
CREATE INDEX idx_admin_username ON app_admin_users(username);
CREATE INDEX idx_admin_active ON app_admin_users(is_active);

-- Logs indexes
CREATE INDEX idx_log_voter ON app_voting_logs(voter_id);
CREATE INDEX idx_log_action ON app_voting_logs(action);
CREATE INDEX idx_log_timestamp ON app_voting_logs(log_timestamp DESC);

-- =====================================================
-- SAMPLE DATA - VOTERS
-- =====================================================

INSERT INTO app_voters (voter_id, voter_name, password_hash, email, phone, has_voted, voted_for) VALUES
('V001', 'John Smith', SHA2('voter123', 256), 'john@example.com', '555-0001', FALSE, NULL),
('V002', 'Jane Doe', SHA2('voter123', 256), 'jane@example.com', '555-0002', FALSE, NULL),
('V003', 'Mike Johnson', SHA2('voter123', 256), 'mike@example.com', '555-0003', FALSE, NULL),
('V004', 'Sarah Williams', SHA2('voter123', 256), 'sarah@example.com', '555-0004', FALSE, NULL),
('V005', 'Robert Brown', SHA2('voter123', 256), 'robert@example.com', '555-0005', FALSE, NULL);

-- =====================================================
-- SAMPLE DATA - CANDIDATES
-- =====================================================

INSERT INTO app_candidates (candidate_name, party_name, description, vote_count) VALUES
('Alice Johnson', 'Progressive Alliance', 'Experienced economist with 15 years in public service', 0),
('Bob Smith', 'Democratic Union', 'Local business owner and community leader', 0),
('Carol Davis', 'Green Party', 'Environmental scientist and sustainability advocate', 0),
('David Wilson', 'Conservative Party', 'Senior diplomat and policy expert', 0),
('Emma Thompson', 'Socialist Movement', 'Labor rights activist and educator', 0);

-- =====================================================
-- SAMPLE DATA - ADMIN USERS
-- =====================================================

INSERT INTO app_admin_users (username, password_hash, email, full_name, role, is_active) VALUES
('admin', SHA2('admin123', 256), 'admin@voteflow.com', 'System Administrator', 'superadmin', TRUE),
('moderator1', SHA2('mod123', 256), 'mod1@voteflow.com', 'John Moderator', 'moderator', TRUE),
('moderator2', SHA2('mod123', 256), 'mod2@voteflow.com', 'Jane Moderator', 'moderator', TRUE);

-- =====================================================
-- SAMPLE DATA - ELECTION SETTINGS
-- =====================================================

INSERT INTO app_election_settings (setting_key, setting_value, setting_type, description) VALUES
('election_title', 'General Election 2026', 'string', 'Title of the current election'),
('election_status', 'active', 'string', 'Status: pending, active, closed'),
('one_vote_per_person', 'true', 'boolean', 'Enforce one vote per voter'),
('results_visibility', 'public', 'string', 'Public or restricted results'),
('auto_refresh_interval', '30000', 'integer', 'Results refresh interval in milliseconds'),
('max_votes_allowed', '1', 'integer', 'Maximum votes per voter'),
('enable_voting', 'true', 'boolean', 'Enable/disable voting'),
('enable_voter_registration', 'false', 'boolean', 'Allow new voter registration');

-- =====================================================
-- SAMPLE DATA - ELECTION STATISTICS
-- =====================================================

INSERT INTO app_election_statistics (total_voters, total_votes_cast, total_candidates, election_start) VALUES
(5, 0, 5, NOW());

-- =====================================================
-- VIEWS FOR EASIER QUERYING
-- =====================================================

-- View: Current Election Results
CREATE OR REPLACE VIEW vw_app_election_results AS
SELECT 
    c.candidate_id,
    c.candidate_name,
    c.party_name,
    c.vote_count,
    ROUND((c.vote_count * 100.0 / 
        GREATEST((SELECT SUM(vote_count) FROM app_candidates), 1)), 2) AS vote_percentage,
    RANK() OVER (ORDER BY c.vote_count DESC) AS ranking,
    CASE 
        WHEN RANK() OVER (ORDER BY c.vote_count DESC) = 1 THEN 'LEADING'
        ELSE 'OTHER'
    END AS status
FROM app_candidates c
ORDER BY c.vote_count DESC;

-- View: Voter Statistics
CREATE OR REPLACE VIEW vw_app_voter_statistics AS
SELECT 
    COUNT(*) AS total_voters,
    SUM(CASE WHEN has_voted = TRUE THEN 1 ELSE 0 END) AS voters_voted,
    SUM(CASE WHEN has_voted = FALSE THEN 1 ELSE 0 END) AS voters_not_voted,
    ROUND((SUM(CASE WHEN has_voted = TRUE THEN 1 ELSE 0 END) * 100.0 / COUNT(*)), 2) AS participation_rate
FROM app_voters;

-- View: Candidate Performance
CREATE OR REPLACE VIEW vw_app_candidate_performance AS
SELECT 
    c.candidate_id,
    c.candidate_name,
    c.party_name,
    c.vote_count,
    COUNT(v.vote_id) AS total_votes_recorded,
    c.created_at
FROM app_candidates c
LEFT JOIN app_votes v ON c.candidate_id = v.candidate_id
GROUP BY c.candidate_id, c.candidate_name, c.party_name, c.vote_count, c.created_at
ORDER BY c.vote_count DESC;

-- =====================================================
-- STORED PROCEDURES
-- =====================================================

-- Procedure: Cast Vote
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS sp_cast_vote(
    IN p_voter_id VARCHAR(50),
    IN p_candidate_id INT,
    IN p_ip_address VARCHAR(45),
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    DECLARE voter_already_voted BOOLEAN;
    
    -- Check if voter exists
    IF NOT EXISTS (SELECT 1 FROM app_voters WHERE voter_id = p_voter_id) THEN
        SET p_success = FALSE;
        SET p_message = 'Voter ID not found';
        LEAVE;
    END IF;
    
    -- Check if candidate exists
    IF NOT EXISTS (SELECT 1 FROM app_candidates WHERE candidate_id = p_candidate_id) THEN
        SET p_success = FALSE;
        SET p_message = 'Candidate not found';
        LEAVE;
    END IF;
    
    -- Check if voter has already voted
    SET voter_already_voted = (SELECT has_voted FROM app_voters WHERE voter_id = p_voter_id);
    
    IF voter_already_voted = TRUE THEN
        SET p_success = FALSE;
        SET p_message = 'Voter has already cast a vote';
        LEAVE;
    END IF;
    
    -- Start transaction
    START TRANSACTION;
    
    -- Record the vote
    INSERT INTO app_votes (voter_id, candidate_id, ip_address)
    VALUES (p_voter_id, p_candidate_id, p_ip_address);
    
    -- Update candidate vote count
    UPDATE app_candidates 
    SET vote_count = vote_count + 1
    WHERE candidate_id = p_candidate_id;
    
    -- Mark voter as voted
    UPDATE app_voters
    SET has_voted = TRUE, voted_for = p_candidate_id, last_login = NOW()
    WHERE voter_id = p_voter_id;
    
    -- Log the action
    INSERT INTO app_voting_logs (action, voter_id, candidate_id, ip_address)
    VALUES ('VOTE_CAST', p_voter_id, p_candidate_id, p_ip_address);
    
    -- Commit transaction
    COMMIT;
    
    SET p_success = TRUE;
    SET p_message = 'Vote recorded successfully';
END$$

DELIMITER ;

-- Procedure: Get Election Results
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS sp_get_election_results()
BEGIN
    SELECT * FROM vw_app_election_results;
END$$

DELIMITER ;

-- Procedure: Get Voter Count
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS sp_get_voter_statistics()
BEGIN
    SELECT * FROM vw_app_voter_statistics;
END$$

DELIMITER ;

-- Procedure: Add Candidate (Admin)
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS sp_add_candidate(
    IN p_candidate_name VARCHAR(150),
    IN p_party_name VARCHAR(100),
    IN p_description TEXT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    -- Check if candidate already exists
    IF EXISTS (SELECT 1 FROM app_candidates WHERE candidate_name = p_candidate_name) THEN
        SET p_success = FALSE;
        SET p_message = 'Candidate already exists';
        LEAVE;
    END IF;
    
    -- Check if name is empty
    IF p_candidate_name IS NULL OR p_candidate_name = '' THEN
        SET p_success = FALSE;
        SET p_message = 'Candidate name is required';
        LEAVE;
    END IF;
    
    -- Insert candidate
    INSERT INTO app_candidates (candidate_name, party_name, description, vote_count)
    VALUES (p_candidate_name, p_party_name, p_description, 0);
    
    -- Log the action
    INSERT INTO app_voting_logs (action, candidate_id, action_details)
    VALUES ('CANDIDATE_ADDED', LAST_INSERT_ID(), CONCAT('New candidate: ', p_candidate_name));
    
    SET p_success = TRUE;
    SET p_message = 'Candidate added successfully';
END$$

DELIMITER ;

-- Procedure: Delete Candidate (Admin)
DELIMITER $$

CREATE PROCEDURE IF NOT EXISTS sp_delete_candidate(
    IN p_candidate_id INT,
    OUT p_success BOOLEAN,
    OUT p_message VARCHAR(255)
)
BEGIN
    -- Check if candidate exists
    IF NOT EXISTS (SELECT 1 FROM app_candidates WHERE candidate_id = p_candidate_id) THEN
        SET p_success = FALSE;
        SET p_message = 'Candidate not found';
        LEAVE;
    END IF;
    
    -- Delete associated votes
    DELETE FROM app_votes WHERE candidate_id = p_candidate_id;
    
    -- Update voters who voted for this candidate
    UPDATE app_voters SET has_voted = FALSE, voted_for = NULL 
    WHERE voted_for = p_candidate_id;
    
    -- Delete candidate
    DELETE FROM app_candidates WHERE candidate_id = p_candidate_id;
    
    -- Log the action
    INSERT INTO app_voting_logs (action, candidate_id, action_details)
    VALUES ('CANDIDATE_DELETED', p_candidate_id, 'Candidate deleted');
    
    SET p_success = TRUE;
    SET p_message = 'Candidate deleted successfully';
END$$

DELIMITER ;

-- =====================================================
-- TRIGGERS FOR DATA INTEGRITY
-- =====================================================

-- Trigger: Update election statistics when vote is cast
DELIMITER $$

CREATE TRIGGER trg_update_stats_on_vote
AFTER INSERT ON app_votes
FOR EACH ROW
BEGIN
    UPDATE app_election_statistics
    SET total_votes_cast = (SELECT COUNT(*) FROM app_votes),
        last_updated = NOW();
END$$

DELIMITER ;

-- Trigger: Update total candidates count
DELIMITER $$

CREATE TRIGGER trg_update_stats_on_candidate_add
AFTER INSERT ON app_candidates
FOR EACH ROW
BEGIN
    UPDATE app_election_statistics
    SET total_candidates = (SELECT COUNT(*) FROM app_candidates),
        last_updated = NOW();
END$$

DELIMITER ;

-- Trigger: Update total candidates count on delete
DELIMITER $$

CREATE TRIGGER trg_update_stats_on_candidate_delete
AFTER DELETE ON app_candidates
FOR EACH ROW
BEGIN
    UPDATE app_election_statistics
    SET total_candidates = (SELECT COUNT(*) FROM app_candidates),
        last_updated = NOW();
END$$

DELIMITER ;

-- =====================================================
-- SECURITY: CREATE SPECIFIC USER ROLES
-- =====================================================

-- Create application user for reading data
CREATE USER IF NOT EXISTS 'voteflow_read'@'localhost' IDENTIFIED BY 'read_pass_123';
GRANT SELECT ON voteflow.* TO 'voteflow_read'@'localhost';

-- Create application user for reading and writing
CREATE USER IF NOT EXISTS 'voteflow_app'@'localhost' IDENTIFIED BY 'app_pass_123';
GRANT SELECT, INSERT, UPDATE ON voteflow.* TO 'voteflow_app'@'localhost';

-- Create admin user with full access
CREATE USER IF NOT EXISTS 'voteflow_admin'@'localhost' IDENTIFIED BY 'admin_pass_123';
GRANT ALL PRIVILEGES ON voteflow.* TO 'voteflow_admin'@'localhost';

FLUSH PRIVILEGES;

-- =====================================================
-- QUERY EXAMPLES
-- =====================================================

-- Get all candidates with vote counts
-- SELECT * FROM vw_election_results;

-- Get top 3 candidates
-- SELECT * FROM vw_election_results LIMIT 3;

-- Get voters who have voted
-- SELECT voter_id, voter_name, email, voted_for, vote_timestamp
-- FROM voters v
-- LEFT JOIN votes vo ON v.voter_id = vo.voter_id
-- WHERE v.has_voted = TRUE;

-- Get voters who haven't voted yet
-- SELECT voter_id, voter_name, email FROM voters WHERE has_voted = FALSE;

-- Get voting statistics
-- SELECT * FROM vw_voter_statistics;

-- Get candidate performance
-- SELECT * FROM vw_candidate_performance;

-- Get recent voting logs
-- SELECT * FROM voting_logs ORDER BY log_timestamp DESC LIMIT 20;

-- =====================================================
-- END OF SCHEMA
-- =====================================================
