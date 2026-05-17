USE EncycLawPhilia_db;

-- =============================================================
-- STORED PROCEDURES: EncycLawPhilia_db
-- Dialect: MySQL 8.0+
-- Covers:
--   1. sp_UserSignUp
--   2. sp_UserLogin
--   3. sp_UserLogout
--   4. sp_AddOrdinance
--   5. sp_UpdateOrdinance
--   6. sp_ArchiveOrdinance
-- =============================================================

DELIMITER $$

-- -------------------------------------------------------------
-- HELPER TABLE: User Sessions (required by Login / Logout)
-- Run once before creating the procedures.
-- -------------------------------------------------------------
CREATE TABLE IF NOT EXISTS User_Sessions (
    session_id      VARCHAR(64)     NOT NULL,
    user_id         INT             NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME        NOT NULL,
    invalidated_at  DATETIME            NULL,   -- set on logout

    CONSTRAINT pk_user_sessions PRIMARY KEY (session_id),
    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) $$


-- =============================================================
-- 1. USER SIGN UP
--
-- Parameters:
--   p_full_name     – display name of the new user
--   p_email         – must be unique
--   p_password_hash – already-hashed password from the application
--                     layer (bcrypt / argon2 / sha256 etc.)
--   p_role_id       – FK to Roles; defaults to a "regular user" role
--
-- Out:
--   p_out_user_id   – newly created user_id (0 on failure)
--   p_out_message   – human-readable status
-- =============================================================
CREATE PROCEDURE sp_UserSignUp (
    IN  p_full_name     VARCHAR(100),
    IN  p_email         VARCHAR(150),
    IN  p_password_hash VARCHAR(255),
    IN  p_role_id       INT,
    OUT p_out_user_id   INT,
    OUT p_out_message   VARCHAR(255)
)
BEGIN
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_role_exists  INT DEFAULT 0;

    -- Initialise outputs
    SET p_out_user_id = 0;
    SET p_out_message = '';

    -- Guard: blank fields
    IF TRIM(p_full_name) = '' OR p_full_name IS NULL THEN
        SET p_out_message = 'ERROR: Full name is required.';
        LEAVE sp_UserSignUp;
    END IF;

    IF TRIM(p_email) = '' OR p_email IS NULL THEN
        SET p_out_message = 'ERROR: Email is required.';
        LEAVE sp_UserSignUp;
    END IF;

    IF TRIM(p_password_hash) = '' OR p_password_hash IS NULL THEN
        SET p_out_message = 'ERROR: Password hash is required.';
        LEAVE sp_UserSignUp;
    END IF;

    -- Guard: email already registered (including soft-deleted accounts)
    SELECT COUNT(*) INTO v_email_exists
    FROM   Users
    WHERE  email = p_email;

    IF v_email_exists > 0 THEN
        SET p_out_message = 'ERROR: Email is already registered.';
        LEAVE sp_UserSignUp;
    END IF;

    -- Guard: role must exist
    SELECT COUNT(*) INTO v_role_exists
    FROM   Roles
    WHERE  role_id = p_role_id;

    IF v_role_exists = 0 THEN
        SET p_out_message = 'ERROR: Invalid role_id provided.';
        LEAVE sp_UserSignUp;
    END IF;

    -- Insert new user
    INSERT INTO Users (full_name, email, password_hash, role_id)
    VALUES (TRIM(p_full_name), LOWER(TRIM(p_email)), p_password_hash, p_role_id);

    SET p_out_user_id = LAST_INSERT_ID();
    SET p_out_message = CONCAT('SUCCESS: User registered with ID ', p_out_user_id, '.');
END $$


-- =============================================================
-- 2. USER LOGIN
--
-- The application layer is responsible for:
--   a) Fetching the stored password_hash via a plain SELECT on email.
--   b) Verifying the plain-text password against that hash.
--   c) Calling this procedure only after successful verification.
--
-- This procedure:
--   • Confirms the account is active (not soft-deleted).
--   • Creates a session token (UUID-based) valid for p_ttl_hours.
--   • Returns the session_id so the app can store it in a cookie/header.
--
-- Parameters:
--   p_email       – the user's email
--   p_ttl_hours   – session lifetime in hours (e.g. 8)
--
-- Out:
--   p_out_session_id – new session token (empty string on failure)
--   p_out_user_id    – authenticated user_id (0 on failure)
--   p_out_role_id    – role of the user (0 on failure)
--   p_out_message    – human-readable status
-- =============================================================
CREATE PROCEDURE sp_UserLogin (
    IN  p_email          VARCHAR(150),
    IN  p_ttl_hours      INT,
    OUT p_out_session_id VARCHAR(64),
    OUT p_out_user_id    INT,
    OUT p_out_role_id    INT,
    OUT p_out_message    VARCHAR(255)
)
BEGIN
    DECLARE v_user_id    INT      DEFAULT 0;
    DECLARE v_role_id    INT      DEFAULT 0;
    DECLARE v_deleted_at DATETIME DEFAULT NULL;
    DECLARE v_session_id VARCHAR(64);

    -- Initialise outputs
    SET p_out_session_id = '';
    SET p_out_user_id    = 0;
    SET p_out_role_id    = 0;
    SET p_out_message    = '';

    -- Look up the user
    SELECT user_id, role_id, deleted_at
    INTO   v_user_id, v_role_id, v_deleted_at
    FROM   Users
    WHERE  email = LOWER(TRIM(p_email))
    LIMIT  1;

    IF v_user_id = 0 THEN
        SET p_out_message = 'ERROR: No account found for that email.';
        LEAVE sp_UserLogin;
    END IF;

    -- Guard: account must not be soft-deleted
    IF v_deleted_at IS NOT NULL THEN
        SET p_out_message = 'ERROR: This account has been deactivated.';
        LEAVE sp_UserLogin;
    END IF;

    -- Generate a session token
    SET v_session_id = CONCAT(
        UUID(), '-', UNIX_TIMESTAMP()
    );

    -- Persist session
    INSERT INTO User_Sessions (session_id, user_id, expires_at)
    VALUES (
        v_session_id,
        v_user_id,
        DATE_ADD(NOW(), INTERVAL p_ttl_hours HOUR)
    );

    SET p_out_session_id = v_session_id;
    SET p_out_user_id    = v_user_id;
    SET p_out_role_id    = v_role_id;
    SET p_out_message    = 'SUCCESS: Login successful.';
END $$


-- =============================================================
-- 3. USER LOGOUT
--
-- Invalidates (soft-deletes) the specified session token so it
-- can no longer be used. Does NOT delete the row so audit
-- trails remain intact.
--
-- Parameters:
--   p_session_id – the session token to invalidate
--
-- Out:
--   p_out_message – human-readable status
-- =============================================================
CREATE PROCEDURE sp_UserLogout (
    IN  p_session_id  VARCHAR(64),
    OUT p_out_message VARCHAR(255)
)
BEGIN
    DECLARE v_session_exists INT     DEFAULT 0;
    DECLARE v_already_out    DATETIME DEFAULT NULL;

    SET p_out_message = '';

    -- Check session exists
    SELECT COUNT(*), invalidated_at
    INTO   v_session_exists, v_already_out
    FROM   User_Sessions
    WHERE  session_id = p_session_id
    LIMIT  1;

    IF v_session_exists = 0 THEN
        SET p_out_message = 'ERROR: Session not found.';
        LEAVE sp_UserLogout;
    END IF;

    IF v_already_out IS NOT NULL THEN
        SET p_out_message = 'INFO: Session was already invalidated.';
        LEAVE sp_UserLogout;
    END IF;

    -- Invalidate
    UPDATE User_Sessions
    SET    invalidated_at = NOW()
    WHERE  session_id = p_session_id;

    SET p_out_message = 'SUCCESS: Logged out successfully.';
END $$


-- =============================================================
-- 4. ADD ORDINANCE
--
-- All text fields are entered manually by the client (admin /
-- editor). The PDF path is optional – it can be supplied later
-- via sp_UpdateOrdinance.
--
-- Parameters (all IN):
--   p_ordinance_number  – unique identifier (e.g. "ORD-2024-001")
--   p_title             – full title of the ordinance
--   p_author_sponsor    – name of the author/sponsor
--   p_series_year       – e.g. "2024"
--   p_category_id       – FK to Categories (NULL allowed)
--   p_barangay_id       – FK to Barangays   (NULL allowed)
--   p_status            – e.g. "Active", "Pending", "Repealed"
--   p_date_enacted      – DATE string 'YYYY-MM-DD' (NULL allowed)
--   p_pdf_file          – file path / URL  (NULL allowed)
--   p_summary           – short summary    (NULL allowed)
--   p_full_text         – full ordinance text (NULL allowed)
--
-- Out:
--   p_out_ordinance_id  – newly created ordinance_id (0 on failure)
--   p_out_message       – human-readable status
-- =============================================================
CREATE PROCEDURE sp_AddOrdinance (
    IN  p_ordinance_number  VARCHAR(50),
    IN  p_title             VARCHAR(255),
    IN  p_author_sponsor    VARCHAR(150),
    IN  p_series_year       VARCHAR(10),
    IN  p_category_id       INT,
    IN  p_barangay_id       INT,
    IN  p_status            VARCHAR(50),
    IN  p_date_enacted      DATE,
    IN  p_pdf_file          VARCHAR(255),
    IN  p_summary           TEXT,
    IN  p_full_text         LONGTEXT,
    OUT p_out_ordinance_id  INT,
    OUT p_out_message       VARCHAR(255)
)
BEGIN
    DECLARE v_num_exists  INT DEFAULT 0;
    DECLARE v_cat_exists  INT DEFAULT 0;
    DECLARE v_bar_exists  INT DEFAULT 0;

    SET p_out_ordinance_id = 0;
    SET p_out_message      = '';

    -- Guard: required fields
    IF TRIM(p_ordinance_number) = '' OR p_ordinance_number IS NULL THEN
        SET p_out_message = 'ERROR: Ordinance number is required.';
        LEAVE sp_AddOrdinance;
    END IF;

    IF TRIM(p_title) = '' OR p_title IS NULL THEN
        SET p_out_message = 'ERROR: Title is required.';
        LEAVE sp_AddOrdinance;
    END IF;

    IF TRIM(p_status) = '' OR p_status IS NULL THEN
        SET p_out_message = 'ERROR: Status is required.';
        LEAVE sp_AddOrdinance;
    END IF;

    -- Guard: duplicate ordinance number
    SELECT COUNT(*) INTO v_num_exists
    FROM   Ordinances
    WHERE  ordinance_number = TRIM(p_ordinance_number)
      AND  deleted_at IS NULL;

    IF v_num_exists > 0 THEN
        SET p_out_message = 'ERROR: Ordinance number already exists.';
        LEAVE sp_AddOrdinance;
    END IF;

    -- Guard: category FK (only if provided)
    IF p_category_id IS NOT NULL THEN
        SELECT COUNT(*) INTO v_cat_exists
        FROM   Categories
        WHERE  category_id = p_category_id;

        IF v_cat_exists = 0 THEN
            SET p_out_message = 'ERROR: Invalid category_id.';
            LEAVE sp_AddOrdinance;
        END IF;
    END IF;

    -- Guard: barangay FK (only if provided)
    IF p_barangay_id IS NOT NULL THEN
        SELECT COUNT(*) INTO v_bar_exists
        FROM   Barangays
        WHERE  barangay_id = p_barangay_id;

        IF v_bar_exists = 0 THEN
            SET p_out_message = 'ERROR: Invalid barangay_id.';
            LEAVE sp_AddOrdinance;
        END IF;
    END IF;

    -- Insert
    INSERT INTO Ordinances (
        ordinance_number, title, author_sponsor, series_year,
        category_id, barangay_id, status, date_enacted,
        pdf_file, summary, full_text
    )
    VALUES (
        TRIM(p_ordinance_number), TRIM(p_title), p_author_sponsor, p_series_year,
        p_category_id, p_barangay_id, TRIM(p_status), p_date_enacted,
        p_pdf_file, p_summary, p_full_text
    );

    SET p_out_ordinance_id = LAST_INSERT_ID();
    SET p_out_message = CONCAT('SUCCESS: Ordinance added with ID ', p_out_ordinance_id, '.');
END $$


-- =============================================================
-- 5. UPDATE ORDINANCE
--
-- Updates any subset of editable fields. Only non-NULL IN
-- parameters overwrite the existing value. Pass NULL to leave
-- a column unchanged.
--
-- Parameters:
--   p_ordinance_id      – PK of the ordinance to update (required)
--   All other IN params  – new values (NULL = keep existing)
--
-- Out:
--   p_out_message – human-readable status
-- =============================================================
CREATE PROCEDURE sp_UpdateOrdinance (
    IN  p_ordinance_id      INT,
    IN  p_ordinance_number  VARCHAR(50),
    IN  p_title             VARCHAR(255),
    IN  p_author_sponsor    VARCHAR(150),
    IN  p_series_year       VARCHAR(10),
    IN  p_category_id       INT,
    IN  p_barangay_id       INT,
    IN  p_status            VARCHAR(50),
    IN  p_date_enacted      DATE,
    IN  p_pdf_file          VARCHAR(255),
    IN  p_summary           TEXT,
    IN  p_full_text         LONGTEXT,
    OUT p_out_message       VARCHAR(255)
)
BEGIN
    DECLARE v_exists          INT      DEFAULT 0;
    DECLARE v_is_archived     DATETIME DEFAULT NULL;
    DECLARE v_num_conflict    INT      DEFAULT 0;

    SET p_out_message = '';

    -- Guard: ordinance must exist and must not be archived
    SELECT COUNT(*), deleted_at
    INTO   v_exists, v_is_archived
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE sp_UpdateOrdinance;
    END IF;

    IF v_is_archived IS NOT NULL THEN
        SET p_out_message = 'ERROR: Cannot update an archived ordinance.';
        LEAVE sp_UpdateOrdinance;
    END IF;

    -- Guard: new ordinance_number must not collide with another record
    IF p_ordinance_number IS NOT NULL THEN
        SELECT COUNT(*) INTO v_num_conflict
        FROM   Ordinances
        WHERE  ordinance_number = TRIM(p_ordinance_number)
          AND  ordinance_id    <> p_ordinance_id
          AND  deleted_at       IS NULL;

        IF v_num_conflict > 0 THEN
            SET p_out_message = 'ERROR: That ordinance number is already used by another record.';
            LEAVE sp_UpdateOrdinance;
        END IF;
    END IF;

    -- Selective update using COALESCE (keeps old value when IN param is NULL)
    UPDATE Ordinances
    SET
        ordinance_number = COALESCE(TRIM(p_ordinance_number), ordinance_number),
        title            = COALESCE(TRIM(p_title),            title),
        author_sponsor   = COALESCE(p_author_sponsor,         author_sponsor),
        series_year      = COALESCE(p_series_year,            series_year),
        category_id      = COALESCE(p_category_id,            category_id),
        barangay_id      = COALESCE(p_barangay_id,            barangay_id),
        status           = COALESCE(TRIM(p_status),           status),
        date_enacted     = COALESCE(p_date_enacted,           date_enacted),
        pdf_file         = COALESCE(p_pdf_file,               pdf_file),
        summary          = COALESCE(p_summary,                summary),
        full_text        = COALESCE(p_full_text,              full_text)
    WHERE  ordinance_id = p_ordinance_id;

    SET p_out_message = CONCAT('SUCCESS: Ordinance ID ', p_ordinance_id, ' updated successfully.');
END $$


-- =============================================================
-- 6. ARCHIVE ORDINANCE  (soft-delete)
--
-- Sets deleted_at to the current timestamp, effectively hiding
-- the ordinance from normal queries without losing data.
-- Archived ordinances cannot be updated (see sp_UpdateOrdinance).
--
-- Parameters:
--   p_ordinance_id – PK of the ordinance to archive
--
-- Out:
--   p_out_message – human-readable status
-- =============================================================
CREATE PROCEDURE sp_ArchiveOrdinance (
    IN  p_ordinance_id  INT,
    OUT p_out_message   VARCHAR(255)
)
BEGIN
    DECLARE v_exists      INT      DEFAULT 0;
    DECLARE v_deleted_at  DATETIME DEFAULT NULL;

    SET p_out_message = '';

    -- Confirm the ordinance exists
    SELECT COUNT(*), deleted_at
    INTO   v_exists, v_deleted_at
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE sp_ArchiveOrdinance;
    END IF;

    IF v_deleted_at IS NOT NULL THEN
        SET p_out_message = 'INFO: Ordinance is already archived.';
        LEAVE sp_ArchiveOrdinance;
    END IF;

    -- Soft-delete
    UPDATE Ordinances
    SET    deleted_at = NOW()
    WHERE  ordinance_id = p_ordinance_id;

    SET p_out_message = CONCAT('SUCCESS: Ordinance ID ', p_ordinance_id, ' has been archived.');
END $$


DELIMITER ;


-- =============================================================
-- SAMPLE USAGE
-- =============================================================

/*
-- 1. Sign up
CALL sp_UserSignUp(
    'Juan dela Cruz',
    'juan@example.com',
    '$2b$12$hashedPasswordHere',
    1,           -- role_id
    @uid, @msg
);
SELECT @uid AS new_user_id, @msg AS message;

-- 2. Login  (call AFTER verifying password on the app layer)
CALL sp_UserLogin('juan@example.com', 8, @sid, @uid, @rid, @msg);
SELECT @sid AS session_id, @uid AS user_id, @rid AS role_id, @msg AS message;

-- 3. Logout
CALL sp_UserLogout(@sid, @msg);
SELECT @msg AS message;

-- 4. Add ordinance
CALL sp_AddOrdinance(
    'ORD-2024-001',
    'An Ordinance Regulating Noise Pollution in Barangay X',
    'Hon. Maria Santos',
    '2024',
    2,                  -- category_id
    3,                  -- barangay_id
    'Active',
    '2024-03-15',
    NULL,               -- pdf_file (none yet)
    'Regulates noise levels in residential areas.',
    'WHEREAS, the Sangguniang Barangay of Barangay X...',
    @oid, @msg
);
SELECT @oid AS new_ordinance_id, @msg AS message;

-- 5. Update ordinance (only fields passed as non-NULL change)
CALL sp_UpdateOrdinance(
    @oid,
    NULL,               -- keep same ordinance_number
    NULL,               -- keep same title
    NULL,               -- keep same author
    NULL,               -- keep same series_year
    NULL,               -- keep same category
    NULL,               -- keep same barangay
    NULL,               -- keep same status
    NULL,               -- keep same date
    '/uploads/ord2024001.pdf',  -- update pdf_file
    NULL,               -- keep same summary
    NULL,               -- keep same full_text
    @msg
);
SELECT @msg AS message;

-- 6. Archive ordinance
CALL sp_ArchiveOrdinance(@oid, @msg);
SELECT @msg AS message;
*/

-- XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX

-- =============================================================
-- STORED PROCEDURES: EncycLawPhilia_db  (Addendum)
-- Dialect: MySQL 8.0+
-- Covers:
--   7. sp_ReactToOrdinance   – Like / Dislike (toggle-aware)
--   8. sp_AddComment         – Post a comment on an ordinance
--   9. sp_GetOrdinanceReactionSummary  – Reaction counts helper
-- =============================================================

DELIMITER $$


-- =============================================================
-- 7. REACT TO ORDINANCE  (Like / Dislike)
--
-- Business rules
-- ──────────────
--  • A user may hold only ONE reaction per ordinance at a time
--    (enforced by the UNIQUE constraint on Ordinance_Reactions).
--  • Calling with the SAME reaction_type the user already has
--    REMOVES it  (toggle off / "un-like").
--  • Calling with a DIFFERENT reaction_type SWITCHES the reaction
--    (e.g. Like → Dislike).
--  • Calling when no prior reaction exists ADDS it.
--
-- Parameters:
--   p_ordinance_id      – target ordinance
--   p_user_id           – reacting user
--   p_reaction_type_id  – FK to Reaction_Types
--                         (e.g. 1 = Like, 2 = Dislike)
--
-- Out:
--   p_out_action  – 'ADDED' | 'REMOVED' | 'SWITCHED' | 'ERROR'
--   p_out_message – human-readable status
-- =============================================================
CREATE PROCEDURE sp_ReactToOrdinance (
    IN  p_ordinance_id      INT,
    IN  p_user_id           INT,
    IN  p_reaction_type_id  INT,
    OUT p_out_action        VARCHAR(10),
    OUT p_out_message       VARCHAR(255)
)
BEGIN
    DECLARE v_ord_exists      INT      DEFAULT 0;
    DECLARE v_ord_archived    DATETIME DEFAULT NULL;
    DECLARE v_user_exists     INT      DEFAULT 0;
    DECLARE v_rtype_exists    INT      DEFAULT 0;
    DECLARE v_existing_rid    INT      DEFAULT 0;   -- existing reaction_id
    DECLARE v_existing_rtype  INT      DEFAULT 0;   -- existing reaction_type_id

    SET p_out_action  = 'ERROR';
    SET p_out_message = '';

    -- Guard: ordinance must exist and not be archived
    SELECT COUNT(*), deleted_at
    INTO   v_ord_exists, v_ord_archived
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_ord_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE sp_ReactToOrdinance;
    END IF;

    IF v_ord_archived IS NOT NULL THEN
        SET p_out_message = 'ERROR: Cannot react to an archived ordinance.';
        LEAVE sp_ReactToOrdinance;
    END IF;

    -- Guard: user must exist and not be soft-deleted
    SELECT COUNT(*) INTO v_user_exists
    FROM   Users
    WHERE  user_id   = p_user_id
      AND  deleted_at IS NULL;

    IF v_user_exists = 0 THEN
        SET p_out_message = 'ERROR: User not found or account is deactivated.';
        LEAVE sp_ReactToOrdinance;
    END IF;

    -- Guard: reaction type must exist
    SELECT COUNT(*) INTO v_rtype_exists
    FROM   Reaction_Types
    WHERE  reaction_type_id = p_reaction_type_id;

    IF v_rtype_exists = 0 THEN
        SET p_out_message = 'ERROR: Invalid reaction_type_id.';
        LEAVE sp_ReactToOrdinance;
    END IF;

    -- Check whether the user already has a reaction on this ordinance
    SELECT reaction_id, reaction_type_id
    INTO   v_existing_rid, v_existing_rtype
    FROM   Ordinance_Reactions
    WHERE  ordinance_id = p_ordinance_id
      AND  user_id      = p_user_id
    LIMIT  1;

    IF v_existing_rid = 0 THEN
        -- ── No prior reaction → INSERT ────────────────────────────
        INSERT INTO Ordinance_Reactions (ordinance_id, user_id, reaction_type_id)
        VALUES (p_ordinance_id, p_user_id, p_reaction_type_id);

        SET p_out_action  = 'ADDED';
        SET p_out_message = CONCAT(
            'SUCCESS: Reaction (type ', p_reaction_type_id,
            ') added to ordinance ID ', p_ordinance_id, '.'
        );

    ELSEIF v_existing_rtype = p_reaction_type_id THEN
        -- ── Same reaction → TOGGLE OFF (DELETE) ───────────────────
        DELETE FROM Ordinance_Reactions
        WHERE  reaction_id = v_existing_rid;

        SET p_out_action  = 'REMOVED';
        SET p_out_message = CONCAT(
            'SUCCESS: Reaction removed from ordinance ID ', p_ordinance_id, '.'
        );

    ELSE
        -- ── Different reaction → SWITCH (UPDATE) ──────────────────
        UPDATE Ordinance_Reactions
        SET    reaction_type_id = p_reaction_type_id,
               created_at       = NOW()
        WHERE  reaction_id = v_existing_rid;

        SET p_out_action  = 'SWITCHED';
        SET p_out_message = CONCAT(
            'SUCCESS: Reaction switched to type ', p_reaction_type_id,
            ' on ordinance ID ', p_ordinance_id, '.'
        );
    END IF;
END $$


-- =============================================================
-- 8. ADD COMMENT
--
-- Business rules
-- ──────────────
--  • The ordinance must be active (not archived).
--  • The user must be active (not soft-deleted).
--  • Comment text must not be blank.
--  • The new comment_id is returned so the caller can display
--    the comment immediately without a second query.
--
-- Parameters:
--   p_ordinance_id  – target ordinance
--   p_user_id       – commenting user
--   p_comment_text  – comment body (plain text / HTML sanitised
--                     on the application layer before calling)
--
-- Out:
--   p_out_comment_id – newly created comment_id (0 on failure)
--   p_out_message    – human-readable status
-- =============================================================
CREATE PROCEDURE sp_AddComment (
    IN  p_ordinance_id   INT,
    IN  p_user_id        INT,
    IN  p_comment_text   TEXT,
    OUT p_out_comment_id INT,
    OUT p_out_message    VARCHAR(255)
)
BEGIN
    DECLARE v_ord_exists   INT      DEFAULT 0;
    DECLARE v_ord_archived DATETIME DEFAULT NULL;
    DECLARE v_user_exists  INT      DEFAULT 0;

    SET p_out_comment_id = 0;
    SET p_out_message    = '';

    -- Guard: comment text must not be blank
    IF p_comment_text IS NULL OR TRIM(p_comment_text) = '' THEN
        SET p_out_message = 'ERROR: Comment text cannot be empty.';
        LEAVE sp_AddComment;
    END IF;

    -- Guard: ordinance must exist and not be archived
    SELECT COUNT(*), deleted_at
    INTO   v_ord_exists, v_ord_archived
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_ord_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE sp_AddComment;
    END IF;

    IF v_ord_archived IS NOT NULL THEN
        SET p_out_message = 'ERROR: Cannot comment on an archived ordinance.';
        LEAVE sp_AddComment;
    END IF;

    -- Guard: user must exist and not be soft-deleted
    SELECT COUNT(*) INTO v_user_exists
    FROM   Users
    WHERE  user_id    = p_user_id
      AND  deleted_at IS NULL;

    IF v_user_exists = 0 THEN
        SET p_out_message = 'ERROR: User not found or account is deactivated.';
        LEAVE sp_AddComment;
    END IF;

    -- Insert the comment
    INSERT INTO Comments (ordinance_id, user_id, comment_text)
    VALUES (p_ordinance_id, p_user_id, TRIM(p_comment_text));

    SET p_out_comment_id = LAST_INSERT_ID();
    SET p_out_message    = CONCAT(
        'SUCCESS: Comment posted with ID ', p_out_comment_id,
        ' on ordinance ID ', p_ordinance_id, '.'
    );
END $$

-- =============================================================
-- DELETE COMMENT
--
-- Business rules
-- ──────────────
--  • Only the user who wrote the comment may delete it.
--  • If p_user_id does not match the comment's owner, the
--    procedure returns an UNAUTHORIZED error — no deletion
--    occurs, and no hint is given about whether the comment
--    belongs to someone else (security-safe message).
--  • The comment must actually exist.
--  • Deleting a comment also cascades and removes all
--    Comment_Reactions tied to it (handled by the ON DELETE
--    CASCADE FK already defined in the schema).
--
-- Parameters:
--   p_comment_id – PK of the comment to delete
--   p_user_id    – ID of the user requesting the deletion
--
-- Out:
--   p_out_message – human-readable status
-- =============================================================
CREATE PROCEDURE sp_DeleteComment (
    IN  p_comment_id  INT,
    IN  p_user_id     INT,
    OUT p_out_message VARCHAR(255)
)
BEGIN
    DECLARE v_comment_exists INT DEFAULT 0;
    DECLARE v_owner_id       INT DEFAULT 0;
    DECLARE v_user_active    INT DEFAULT 0;
 
    SET p_out_message = '';
 
    -- Guard: user must exist and not be soft-deleted
    SELECT COUNT(*) INTO v_user_active
    FROM   Users
    WHERE  user_id    = p_user_id
      AND  deleted_at IS NULL;
 
    IF v_user_active = 0 THEN
        SET p_out_message = 'ERROR: User not found or account is deactivated.';
        LEAVE sp_DeleteComment;
    END IF;
 
    -- Guard: comment must exist
    SELECT COUNT(*), user_id
    INTO   v_comment_exists, v_owner_id
    FROM   Comments
    WHERE  comment_id = p_comment_id
    LIMIT  1;
 
    IF v_comment_exists = 0 THEN
        SET p_out_message = 'ERROR: Comment not found.';
        LEAVE sp_DeleteComment;
    END IF;
 
    -- Guard: requesting user must be the comment owner
    IF v_owner_id <> p_user_id THEN
        SET p_out_message = 'ERROR: Unauthorized. You can only delete your own comments.';
        LEAVE sp_DeleteComment;
    END IF;
 
    -- Delete the comment (Comment_Reactions cascade automatically)
    DELETE FROM Comments
    WHERE  comment_id = p_comment_id;
 
    SET p_out_message = CONCAT(
        'SUCCESS: Comment ID ', p_comment_id, ' has been deleted.'
    );
END $$


-- =============================================================
-- 9. GET ORDINANCE REACTION SUMMARY  (convenience read helper)
--
-- Returns one result-set row per reaction type showing the
-- count for a given ordinance — useful for rendering the
-- Like / Dislike counters in the UI.
--
-- Parameters:
--   p_ordinance_id – the ordinance to summarise
-- =============================================================
CREATE PROCEDURE sp_GetOrdinanceReactionSummary (
    IN p_ordinance_id INT
)
BEGIN
    SELECT
        rt.reaction_type_id,
        rt.reaction_type,
        COUNT(orr.reaction_id) AS reaction_count
    FROM       Reaction_Types      rt
    LEFT JOIN  Ordinance_Reactions orr
           ON  orr.reaction_type_id = rt.reaction_type_id
          AND  orr.ordinance_id     = p_ordinance_id
    GROUP BY   rt.reaction_type_id, rt.reaction_type
    ORDER BY   rt.reaction_type_id;
END $$


DELIMITER ;


-- =============================================================
-- SAMPLE USAGE
-- =============================================================

/*
-- Seed reaction types (run once)
INSERT IGNORE INTO Reaction_Types (reaction_type) VALUES ('Like'), ('Dislike');

-- ── 7. React to an ordinance ──────────────────────────────────

-- First call: adds a Like
CALL sp_ReactToOrdinance(1, 5, 1, @action, @msg);
SELECT @action AS action, @msg AS message;
-- action: ADDED | message: SUCCESS: Reaction (type 1) added to ordinance ID 1.

-- Second call with same type: removes it (toggle off)
CALL sp_ReactToOrdinance(1, 5, 1, @action, @msg);
SELECT @action AS action, @msg AS message;
-- action: REMOVED | message: SUCCESS: Reaction removed from ordinance ID 1.

-- Add a Like, then switch to Dislike
CALL sp_ReactToOrdinance(1, 5, 1, @action, @msg);   -- Like added
CALL sp_ReactToOrdinance(1, 5, 2, @action, @msg);   -- switched to Dislike
SELECT @action AS action, @msg AS message;
-- action: SWITCHED | message: SUCCESS: Reaction switched to type 2 on ordinance ID 1.

-- ── 8. Post a comment ─────────────────────────────────────────
CALL sp_AddComment(
    1,                                          -- ordinance_id
    5,                                          -- user_id
    'This ordinance greatly benefits our community.',
    @cid, @msg
);
SELECT @cid AS new_comment_id, @msg AS message;

-- ── 9. Delete a comment ─────────────────────────────────────────

-- User 5 deletes their own comment (comment_id = 3)
CALL sp_DeleteComment(3, 5, @msg);
SELECT @msg AS message;
-- SUCCESS: Comment ID 3 has been deleted.
 
-- User 7 tries to delete a comment that belongs to user 5
CALL sp_DeleteComment(3, 7, @msg);
SELECT @msg AS message;
-- ERROR: Unauthorized. You can only delete your own comments.
 
-- Comment does not exist
CALL sp_DeleteComment(999, 5, @msg);
SELECT @msg AS message;
-- ERROR: Comment not found.
 
-- Deactivated / non-existent user attempts deletion
CALL sp_DeleteComment(3, 99, @msg);
SELECT @msg AS message;
-- ERROR: User not found or account is deactivated.

-- ── 10. Get reaction summary for ordinance 1 ───────────────────
CALL sp_GetOrdinanceReactionSummary(1);
-- Returns:
-- reaction_type_id | reaction_type | reaction_count
-- 1                | Like          | 3
-- 2                | Dislike       | 1
*/