USE if0_41928864_encyclawphilia_db;

-- =============================================================
-- STORED PROCEDURES: if0_41928864_encyclawphilia_db
-- Dialect: MariaDB 10.x+
-- Fix applied: All LEAVE <proc_name> replaced with explicit
--              block labels (proc_block: BEGIN ... END proc_block)
-- Covers:
--   1. sp_UserSignUp
--   2. sp_UserLogin
--   3. sp_UserLogout
--   4. sp_AddOrdinance
--   5. sp_UpdateOrdinance
--   6. sp_ArchiveOrdinance
--   7. sp_ReactToOrdinance
--   8. sp_AddComment
--   9. sp_DeleteComment
--  10. sp_GetOrdinanceReactionSummary
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
    invalidated_at  DATETIME            NULL,

    CONSTRAINT pk_user_sessions PRIMARY KEY (session_id),
    CONSTRAINT fk_sessions_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) $$


-- =============================================================
-- 1. USER SIGN UP
-- =============================================================
CREATE PROCEDURE sp_UserSignUp (
    IN  p_full_name     VARCHAR(100),
    IN  p_email         VARCHAR(150),
    IN  p_password_hash VARCHAR(255),
    IN  p_role_id       INT,
    OUT p_out_user_id   INT,
    OUT p_out_message   VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_email_exists INT DEFAULT 0;
    DECLARE v_role_exists  INT DEFAULT 0;

    SET p_out_user_id = 0;
    SET p_out_message = '';

    IF TRIM(p_full_name) = '' OR p_full_name IS NULL THEN
        SET p_out_message = 'ERROR: Full name is required.';
        LEAVE proc_block;
    END IF;

    IF TRIM(p_email) = '' OR p_email IS NULL THEN
        SET p_out_message = 'ERROR: Email is required.';
        LEAVE proc_block;
    END IF;

    IF TRIM(p_password_hash) = '' OR p_password_hash IS NULL THEN
        SET p_out_message = 'ERROR: Password hash is required.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*) INTO v_email_exists
    FROM   Users
    WHERE  email = p_email;

    IF v_email_exists > 0 THEN
        SET p_out_message = 'ERROR: Email is already registered.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*) INTO v_role_exists
    FROM   Roles
    WHERE  role_id = p_role_id;

    IF v_role_exists = 0 THEN
        SET p_out_message = 'ERROR: Invalid role_id provided.';
        LEAVE proc_block;
    END IF;

    INSERT INTO Users (full_name, email, password_hash, role_id)
    VALUES (TRIM(p_full_name), LOWER(TRIM(p_email)), p_password_hash, p_role_id);

    SET p_out_user_id = LAST_INSERT_ID();
    SET p_out_message = CONCAT('SUCCESS: User registered with ID ', p_out_user_id, '.');
END proc_block $$


-- =============================================================
-- 2. USER LOGIN
-- =============================================================
CREATE PROCEDURE sp_UserLogin (
    IN  p_email          VARCHAR(150),
    IN  p_ttl_hours      INT,
    OUT p_out_session_id VARCHAR(64),
    OUT p_out_user_id    INT,
    OUT p_out_role_id    INT,
    OUT p_out_message    VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_user_id    INT      DEFAULT 0;
    DECLARE v_role_id    INT      DEFAULT 0;
    DECLARE v_deleted_at DATETIME DEFAULT NULL;
    DECLARE v_session_id VARCHAR(64);

    SET p_out_session_id = '';
    SET p_out_user_id    = 0;
    SET p_out_role_id    = 0;
    SET p_out_message    = '';

    SELECT user_id, role_id, deleted_at
    INTO   v_user_id, v_role_id, v_deleted_at
    FROM   Users
    WHERE  email = LOWER(TRIM(p_email))
    LIMIT  1;

    IF v_user_id = 0 THEN
        SET p_out_message = 'ERROR: No account found for that email.';
        LEAVE proc_block;
    END IF;

    IF v_deleted_at IS NOT NULL THEN
        SET p_out_message = 'ERROR: This account has been deactivated.';
        LEAVE proc_block;
    END IF;

    SET v_session_id = CONCAT(UUID(), '-', UNIX_TIMESTAMP());

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
END proc_block $$


-- =============================================================
-- 3. USER LOGOUT
-- =============================================================
CREATE PROCEDURE sp_UserLogout (
    IN  p_session_id  VARCHAR(64),
    OUT p_out_message VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_session_exists INT      DEFAULT 0;
    DECLARE v_already_out    DATETIME DEFAULT NULL;

    SET p_out_message = '';

    SELECT COUNT(*), invalidated_at
    INTO   v_session_exists, v_already_out
    FROM   User_Sessions
    WHERE  session_id = p_session_id
    LIMIT  1;

    IF v_session_exists = 0 THEN
        SET p_out_message = 'ERROR: Session not found.';
        LEAVE proc_block;
    END IF;

    IF v_already_out IS NOT NULL THEN
        SET p_out_message = 'INFO: Session was already invalidated.';
        LEAVE proc_block;
    END IF;

    UPDATE User_Sessions
    SET    invalidated_at = NOW()
    WHERE  session_id = p_session_id;

    SET p_out_message = 'SUCCESS: Logged out successfully.';
END proc_block $$


-- =============================================================
-- 4. ADD ORDINANCE
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
proc_block: BEGIN
    DECLARE v_num_exists  INT DEFAULT 0;
    DECLARE v_cat_exists  INT DEFAULT 0;
    DECLARE v_bar_exists  INT DEFAULT 0;

    SET p_out_ordinance_id = 0;
    SET p_out_message      = '';

    IF TRIM(p_ordinance_number) = '' OR p_ordinance_number IS NULL THEN
        SET p_out_message = 'ERROR: Ordinance number is required.';
        LEAVE proc_block;
    END IF;

    IF TRIM(p_title) = '' OR p_title IS NULL THEN
        SET p_out_message = 'ERROR: Title is required.';
        LEAVE proc_block;
    END IF;

    IF TRIM(p_status) = '' OR p_status IS NULL THEN
        SET p_out_message = 'ERROR: Status is required.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*) INTO v_num_exists
    FROM   Ordinances
    WHERE  ordinance_number = TRIM(p_ordinance_number)
      AND  deleted_at IS NULL;

    IF v_num_exists > 0 THEN
        SET p_out_message = 'ERROR: Ordinance number already exists.';
        LEAVE proc_block;
    END IF;

    IF p_category_id IS NOT NULL THEN
        SELECT COUNT(*) INTO v_cat_exists
        FROM   Categories
        WHERE  category_id = p_category_id;

        IF v_cat_exists = 0 THEN
            SET p_out_message = 'ERROR: Invalid category_id.';
            LEAVE proc_block;
        END IF;
    END IF;

    IF p_barangay_id IS NOT NULL THEN
        SELECT COUNT(*) INTO v_bar_exists
        FROM   Barangays
        WHERE  barangay_id = p_barangay_id;

        IF v_bar_exists = 0 THEN
            SET p_out_message = 'ERROR: Invalid barangay_id.';
            LEAVE proc_block;
        END IF;
    END IF;

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
END proc_block $$


-- =============================================================
-- 5. UPDATE ORDINANCE
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
proc_block: BEGIN
    DECLARE v_exists          INT      DEFAULT 0;
    DECLARE v_is_archived     DATETIME DEFAULT NULL;
    DECLARE v_num_conflict    INT      DEFAULT 0;

    SET p_out_message = '';

    SELECT COUNT(*), deleted_at
    INTO   v_exists, v_is_archived
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE proc_block;
    END IF;

    IF v_is_archived IS NOT NULL THEN
        SET p_out_message = 'ERROR: Cannot update an archived ordinance.';
        LEAVE proc_block;
    END IF;

    IF p_ordinance_number IS NOT NULL THEN
        SELECT COUNT(*) INTO v_num_conflict
        FROM   Ordinances
        WHERE  ordinance_number = TRIM(p_ordinance_number)
          AND  ordinance_id    <> p_ordinance_id
          AND  deleted_at       IS NULL;

        IF v_num_conflict > 0 THEN
            SET p_out_message = 'ERROR: That ordinance number is already used by another record.';
            LEAVE proc_block;
        END IF;
    END IF;

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
END proc_block $$


-- =============================================================
-- 6. ARCHIVE ORDINANCE (soft-delete)
-- =============================================================
CREATE PROCEDURE sp_ArchiveOrdinance (
    IN  p_ordinance_id  INT,
    OUT p_out_message   VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_exists      INT      DEFAULT 0;
    DECLARE v_deleted_at  DATETIME DEFAULT NULL;

    SET p_out_message = '';

    SELECT COUNT(*), deleted_at
    INTO   v_exists, v_deleted_at
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE proc_block;
    END IF;

    IF v_deleted_at IS NOT NULL THEN
        SET p_out_message = 'INFO: Ordinance is already archived.';
        LEAVE proc_block;
    END IF;

    UPDATE Ordinances
    SET    deleted_at = NOW()
    WHERE  ordinance_id = p_ordinance_id;

    SET p_out_message = CONCAT('SUCCESS: Ordinance ID ', p_ordinance_id, ' has been archived.');
END proc_block $$


-- =============================================================
-- 7. REACT TO ORDINANCE (Like / Dislike)
-- =============================================================
CREATE PROCEDURE sp_ReactToOrdinance (
    IN  p_ordinance_id      INT,
    IN  p_user_id           INT,
    IN  p_reaction_type_id  INT,
    OUT p_out_action        VARCHAR(10),
    OUT p_out_message       VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_ord_exists      INT      DEFAULT 0;
    DECLARE v_ord_archived    DATETIME DEFAULT NULL;
    DECLARE v_user_exists     INT      DEFAULT 0;
    DECLARE v_rtype_exists    INT      DEFAULT 0;
    DECLARE v_existing_rid    INT      DEFAULT 0;
    DECLARE v_existing_rtype  INT      DEFAULT 0;

    SET p_out_action  = 'ERROR';
    SET p_out_message = '';

    SELECT COUNT(*), deleted_at
    INTO   v_ord_exists, v_ord_archived
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_ord_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE proc_block;
    END IF;

    IF v_ord_archived IS NOT NULL THEN
        SET p_out_message = 'ERROR: Cannot react to an archived ordinance.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*) INTO v_user_exists
    FROM   Users
    WHERE  user_id    = p_user_id
      AND  deleted_at IS NULL;

    IF v_user_exists = 0 THEN
        SET p_out_message = 'ERROR: User not found or account is deactivated.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*) INTO v_rtype_exists
    FROM   Reaction_Types
    WHERE  reaction_type_id = p_reaction_type_id;

    IF v_rtype_exists = 0 THEN
        SET p_out_message = 'ERROR: Invalid reaction_type_id.';
        LEAVE proc_block;
    END IF;

    SELECT reaction_id, reaction_type_id
    INTO   v_existing_rid, v_existing_rtype
    FROM   Ordinance_Reactions
    WHERE  ordinance_id = p_ordinance_id
      AND  user_id      = p_user_id
    LIMIT  1;

    IF v_existing_rid = 0 THEN
        INSERT INTO Ordinance_Reactions (ordinance_id, user_id, reaction_type_id)
        VALUES (p_ordinance_id, p_user_id, p_reaction_type_id);

        SET p_out_action  = 'ADDED';
        SET p_out_message = CONCAT(
            'SUCCESS: Reaction (type ', p_reaction_type_id,
            ') added to ordinance ID ', p_ordinance_id, '.'
        );

    ELSEIF v_existing_rtype = p_reaction_type_id THEN
        DELETE FROM Ordinance_Reactions
        WHERE  reaction_id = v_existing_rid;

        SET p_out_action  = 'REMOVED';
        SET p_out_message = CONCAT(
            'SUCCESS: Reaction removed from ordinance ID ', p_ordinance_id, '.'
        );

    ELSE
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
END proc_block $$


-- =============================================================
-- 8. ADD COMMENT
-- =============================================================
CREATE PROCEDURE sp_AddComment (
    IN  p_ordinance_id   INT,
    IN  p_user_id        INT,
    IN  p_comment_text   TEXT,
    OUT p_out_comment_id INT,
    OUT p_out_message    VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_ord_exists   INT      DEFAULT 0;
    DECLARE v_ord_archived DATETIME DEFAULT NULL;
    DECLARE v_user_exists  INT      DEFAULT 0;

    SET p_out_comment_id = 0;
    SET p_out_message    = '';

    IF p_comment_text IS NULL OR TRIM(p_comment_text) = '' THEN
        SET p_out_message = 'ERROR: Comment text cannot be empty.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*), deleted_at
    INTO   v_ord_exists, v_ord_archived
    FROM   Ordinances
    WHERE  ordinance_id = p_ordinance_id
    LIMIT  1;

    IF v_ord_exists = 0 THEN
        SET p_out_message = 'ERROR: Ordinance not found.';
        LEAVE proc_block;
    END IF;

    IF v_ord_archived IS NOT NULL THEN
        SET p_out_message = 'ERROR: Cannot comment on an archived ordinance.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*) INTO v_user_exists
    FROM   Users
    WHERE  user_id    = p_user_id
      AND  deleted_at IS NULL;

    IF v_user_exists = 0 THEN
        SET p_out_message = 'ERROR: User not found or account is deactivated.';
        LEAVE proc_block;
    END IF;

    INSERT INTO Comments (ordinance_id, user_id, comment_text)
    VALUES (p_ordinance_id, p_user_id, TRIM(p_comment_text));

    SET p_out_comment_id = LAST_INSERT_ID();
    SET p_out_message    = CONCAT(
        'SUCCESS: Comment posted with ID ', p_out_comment_id,
        ' on ordinance ID ', p_ordinance_id, '.'
    );
END proc_block $$


-- =============================================================
-- 9. DELETE COMMENT
-- =============================================================
CREATE PROCEDURE sp_DeleteComment (
    IN  p_comment_id  INT,
    IN  p_user_id     INT,
    OUT p_out_message VARCHAR(255)
)
proc_block: BEGIN
    DECLARE v_comment_exists INT DEFAULT 0;
    DECLARE v_owner_id       INT DEFAULT 0;
    DECLARE v_user_active    INT DEFAULT 0;

    SET p_out_message = '';

    SELECT COUNT(*) INTO v_user_active
    FROM   Users
    WHERE  user_id    = p_user_id
      AND  deleted_at IS NULL;

    IF v_user_active = 0 THEN
        SET p_out_message = 'ERROR: User not found or account is deactivated.';
        LEAVE proc_block;
    END IF;

    SELECT COUNT(*), user_id
    INTO   v_comment_exists, v_owner_id
    FROM   Comments
    WHERE  comment_id = p_comment_id
    LIMIT  1;

    IF v_comment_exists = 0 THEN
        SET p_out_message = 'ERROR: Comment not found.';
        LEAVE proc_block;
    END IF;

    IF v_owner_id <> p_user_id THEN
        SET p_out_message = 'ERROR: Unauthorized. You can only delete your own comments.';
        LEAVE proc_block;
    END IF;

    DELETE FROM Comments
    WHERE  comment_id = p_comment_id;

    SET p_out_message = CONCAT(
        'SUCCESS: Comment ID ', p_comment_id, ' has been deleted.'
    );
END proc_block $$


-- =============================================================
-- 10. GET ORDINANCE REACTION SUMMARY
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

-- =============================================================
-- GET ORDINANCES BY TITLE
-- =============================================================
CREATE PROCEDURE GetOrdinancesByTitle(
    IN p_search_keyword VARCHAR(255)
)
BEGIN
    DECLARE v_search_pattern VARCHAR(257);
    SET v_search_pattern = CONCAT('%', p_search_keyword, '%');

    -- CTE: Pre-aggregate metrics per ordinance item
    WITH MetricAggregations AS (
        SELECT 
            rel.ordinance_id,
            SUM(CASE WHEN typ.reaction_type = 'like' THEN 1 ELSE 0 END) AS total_likes,
            SUM(CASE WHEN typ.reaction_type = 'dislike' THEN 1 ELSE 0 END) AS total_dislikes
        FROM 
            Ordinance_Reactions rel
        INNER JOIN 
            Reaction_Types typ ON rel.reaction_type_id = typ.reaction_type_id
        GROUP BY 
            rel.ordinance_id
    )

    -- Primary Query Expression
    SELECT 
        ord.ordinance_id,
        SUBSTRING(ord.ordinance_number, 5) AS ordinance_number,
        ord.title,
        ord.series_year,
        IFNULL(DAY(ord.date_enacted), '') AS enactment_day,
        IFNULL(MONTHNAME(ord.date_enacted), '') AS enactment_month,
        IFNULL(YEAR(ord.date_enacted), ord.series_year) AS enactment_year,
        ord.status,
        -- Use COALESCE to gracefully map missing metrics to a baseline zero state
        COALESCE(metrics.total_likes, 0) AS like_count,
        COALESCE(metrics.total_dislikes, 0) AS dislike_count
    FROM 
        Ordinances ord
    LEFT JOIN 
        MetricAggregations metrics ON ord.ordinance_id = metrics.ordinance_id
    WHERE 
        ord.title LIKE v_search_pattern
        AND ord.deleted_at IS NULL
    ORDER BY 
        ord.date_enacted DESC, 
        ord.ordinance_id DESC;
END $$


-- =============================================================
-- GET TRENDING ORDINANCE
--
-- Selects the single ordinance with the highest activity score
-- over a rolling 7-day window ending at the current timestamp.
--
-- Trending score (tunable):
--   score = likes - dislikes + (comment_count * 0.5)
--
-- Only reactions and comments created within the last 7 days
-- contribute to the score, but the ordinance itself may have
-- been enacted at any point in the past.
--
-- Exclusions:
--   • Archived ordinances (deleted_at IS NOT NULL) are ignored.
--
-- Result set (single row):
--   ordinance_id       – PK, for frontend routing
--   ordinance_number   – digits only, affixes stripped
--   series_year
--   date_enacted_fmt   – "DD Month YYYY"  e.g. "15 April 2026"
--   title
--   trending_score     – numeric score used for ranking
--                        (exposed so the UI can display it or
--                         log it; can be omitted in the view layer)
-- =============================================================
CREATE PROCEDURE sp_GetTrendingOrdinance ()
BEGIN
    -- Window boundary computed once
    SET @window_start = DATE_SUB(NOW(), INTERVAL 7 DAY);

    SELECT
        o.ordinance_id,

        -- Strip every non-digit character from ordinance_number
        REGEXP_REPLACE(o.ordinance_number, '[^0-9]', '')   AS ordinance_number,

        o.series_year,

        -- Format enactment date as "DD Month YYYY"
        DATE_FORMAT(o.date_enacted, '%d %M %Y')            AS date_enacted_fmt,

        o.title,

        -- Trending score: likes - dislikes + (comments * 0.5)
        (
            -- Likes within window
            COALESCE((
                SELECT COUNT(*)
                FROM   Ordinance_Reactions  r
                JOIN   Reaction_Types       rt
                       ON rt.reaction_type_id = r.reaction_type_id
                       AND rt.reaction_type   = 'Like'
                WHERE  r.ordinance_id = o.ordinance_id
                  AND  r.created_at  >= @window_start
            ), 0)
            -
            -- Dislikes within window
            COALESCE((
                SELECT COUNT(*)
                FROM   Ordinance_Reactions  r
                JOIN   Reaction_Types       rt
                       ON rt.reaction_type_id = r.reaction_type_id
                       AND rt.reaction_type   = 'Dislike'
                WHERE  r.ordinance_id = o.ordinance_id
                  AND  r.created_at  >= @window_start
            ), 0)
            +
            -- Comments within window (weighted 0.5)
            COALESCE((
                SELECT COUNT(*) * 0.5
                FROM   Comments c
                WHERE  c.ordinance_id = o.ordinance_id
                  AND  c.created_at  >= @window_start
            ), 0)
        )                                                   AS trending_score

    FROM  Ordinances o
    WHERE o.deleted_at IS NULL

    ORDER BY trending_score DESC,
             -- Tie-break: most recently enacted ordinance wins
             o.date_enacted  DESC,
             o.ordinance_id  DESC

    LIMIT 1;
END $$


-- =============================================================
-- GET RECENT ORDINANCES
--
-- Returns the 20 most recently enacted active ordinances whose
-- enactment date is on or before today (no future-dated rows).
--
-- Like / Dislike counts are lifetime totals (not windowed),
-- as the intent here is a recency list, not a trending list.
--
-- Exclusions:
--   • Archived ordinances (deleted_at IS NOT NULL) are ignored.
--   • Ordinances with a NULL or future date_enacted are ignored.
--
-- Result set (up to 20 rows):
--   ordinance_id        – PK, for frontend routing
--   ordinance_number    – digits only, affixes stripped
--   series_year
--   enactment_day       – "DD"   (zero-padded), own column
--   enactment_month     – "Month" full name,    own column
--   enactment_year      – "YYYY",               own column
--   title
--   like_count          – lifetime Like reactions
--   dislike_count       – lifetime Dislike reactions
-- =============================================================
CREATE PROCEDURE sp_GetRecentOrdinances ()
BEGIN
    SELECT
        o.ordinance_id,

        -- Digits-only ordinance number
        REGEXP_REPLACE(o.ordinance_number, '[^0-9]', '')   AS ordinance_number,

        o.series_year,

        -- Enactment date split into three separate columns
        DATE_FORMAT(o.date_enacted, '%d')                  AS enactment_day,
        DATE_FORMAT(o.date_enacted, '%M')                  AS enactment_month,
        DATE_FORMAT(o.date_enacted, '%Y')                  AS enactment_year,

        o.title,

        -- Lifetime Like count
        COALESCE(SUM(
            CASE WHEN rt.reaction_type = 'Like'    THEN 1 ELSE 0 END
        ), 0)                                              AS like_count,

        -- Lifetime Dislike count
        COALESCE(SUM(
            CASE WHEN rt.reaction_type = 'Dislike' THEN 1 ELSE 0 END
        ), 0)                                              AS dislike_count

    FROM       Ordinances        o
    LEFT JOIN  Ordinance_Reactions  orr
                   ON  orr.ordinance_id = o.ordinance_id
    LEFT JOIN  Reaction_Types       rt
                   ON  rt.reaction_type_id = orr.reaction_type_id

    WHERE  o.deleted_at   IS NULL
      AND  o.date_enacted IS NOT NULL
      AND  o.date_enacted <= NOW()   -- exclude any future-dated ordinances

    GROUP BY
        o.ordinance_id,
        o.ordinance_number,
        o.series_year,
        o.date_enacted,
        o.title

    ORDER BY o.date_enacted  DESC,
             o.ordinance_id  DESC   -- stable tie-break for same-day entries

    LIMIT 20;
END $$


DELIMITER ;


-- =============================================================
-- SAMPLE USAGE
-- =============================================================

/*
-- 11. Trending ordinance (last 7 days)
CALL sp_GetTrendingOrdinance();
-- Returns one row, e.g.:
-- ordinance_id | ordinance_number | series_year | date_enacted_fmt | title                        | trending_score
-- 42           | 2024001          | 2024        | 15 March 2024    | An Ordinance Regulating ...  | 7.5


-- 12. Top 20 most recent ordinances
CALL sp_GetRecentOrdinances();
-- Returns up to 20 rows, e.g.:
-- ordinance_id | ordinance_number | series_year | enactment_day | enactment_month | enactment_year | title | like_count | dislike_count
-- 42           | 2024001          | 2024        | 15            | March           | 2024           | An Ordinance... | 10 | 2
*/

-- =============================================================
-- SAMPLE USAGE
-- =============================================================

/*
-- 1. Sign up
CALL sp_UserSignUp('Juan dela Cruz', 'juan@example.com', '$2b$12$hashedPasswordHere', 1, @uid, @msg);
SELECT @uid AS new_user_id, @msg AS message;

-- 2. Login
CALL sp_UserLogin('juan@example.com', 8, @sid, @uid, @rid, @msg);
SELECT @sid AS session_id, @uid AS user_id, @rid AS role_id, @msg AS message;

-- 3. Logout
CALL sp_UserLogout(@sid, @msg);
SELECT @msg AS message;

-- 4. Add ordinance
CALL sp_AddOrdinance(
    'ORD-2024-001', 'An Ordinance Regulating Noise Pollution in Barangay X',
    'Hon. Maria Santos', '2024', 2, 3, 'Active', '2024-03-15',
    NULL, 'Regulates noise levels in residential areas.',
    'WHEREAS, the Sangguniang Barangay of Barangay X...', @oid, @msg
);
SELECT @oid AS new_ordinance_id, @msg AS message;

-- 5. Update ordinance
CALL sp_UpdateOrdinance(@oid, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '/uploads/ord2024001.pdf', NULL, NULL, @msg);
SELECT @msg AS message;

-- 6. Archive ordinance
CALL sp_ArchiveOrdinance(@oid, @msg);
SELECT @msg AS message;

-- 7. React to ordinance
CALL sp_ReactToOrdinance(1, 5, 1, @action, @msg);
SELECT @action AS action, @msg AS message;

-- 8. Add comment
CALL sp_AddComment(1, 5, 'This ordinance greatly benefits our community.', @cid, @msg);
SELECT @cid AS new_comment_id, @msg AS message;

-- 9. Delete comment
CALL sp_DeleteComment(3, 5, @msg);
SELECT @msg AS message;

-- 10. Get reaction summary
CALL sp_GetOrdinanceReactionSummary(1);
*/