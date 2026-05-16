-- =============================================================
-- SOFT DELETE PROCEDURES
-- Dialect: MySQL 8.0+
-- =============================================================
-- Conventions:
--   p_  prefix  → input parameter
--   v_  prefix  → local variable
-- =============================================================


-- -------------------------------------------------------------
-- PROCEDURE: soft_delete_user
--
-- Anonymizes and soft-deletes a user account by:
--   1. Overwriting full_name with "Deleted User <random token>"
--      where the token is an 8-character uppercase string
--      derived from a UUID — unpredictable by design.
--   2. Overwriting email with "deleted_<user_id>@void.invalid"
--      — deterministic and guaranteed unique by construction,
--      freeing the original address for future registrations.
--   3. Stamping deleted_at with the current timestamp.
--
-- Guards:
--   - Raises an error if the user does not exist.
--   - Raises an error if the user is already soft-deleted,
--     preventing accidental re-anonymization of an account
--     that has already been processed.
-- -------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE soft_delete_user(IN p_user_id INT)
BEGIN
    DECLARE v_anon_token  VARCHAR(8);
    DECLARE v_exists      INT DEFAULT 0;
    DECLARE v_deleted     INT DEFAULT 0;

    -- Verify the user record exists at all
    SELECT COUNT(*) INTO v_exists
    FROM Users
    WHERE user_id = p_user_id;

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'soft_delete_user: No user found with the given user_id.';
    END IF;

    -- Guard against re-processing an already soft-deleted account
    SELECT COUNT(*) INTO v_deleted
    FROM Users
    WHERE user_id = p_user_id
      AND deleted_at IS NOT NULL;

    IF v_deleted > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'soft_delete_user: User account is already soft-deleted.';
    END IF;

    -- Generate an 8-character uppercase token from a fresh UUID.
    -- UUID() produces a new value on every call; stripping hyphens
    -- and taking the first 8 characters yields a hex-based token
    -- with sufficient entropy for anonymization purposes.
    SET v_anon_token = UPPER(LEFT(REPLACE(UUID(), '-', ''), 8));

    UPDATE Users
    SET
        full_name  = CONCAT('Deleted User ', v_anon_token),
        email      = CONCAT('deleted_', p_user_id, '_email'),
        deleted_at = NOW()
    WHERE user_id = p_user_id;

END$$

DELIMITER ;


-- -------------------------------------------------------------
-- PROCEDURE: soft_delete_ordinance
--
-- Soft-deletes an ordinance record by stamping deleted_at.
-- No field anonymization is performed — ordinance content is
-- a civic record and its metadata is preserved in full.
--
-- Guards:
--   - Raises an error if the ordinance does not exist.
--   - Raises an error if the ordinance is already soft-deleted.
-- -------------------------------------------------------------

DELIMITER $$

CREATE PROCEDURE soft_delete_ordinance(IN p_ordinance_id INT)
BEGIN
    DECLARE v_exists  INT DEFAULT 0;
    DECLARE v_deleted INT DEFAULT 0;

    -- Verify the ordinance record exists at all
    SELECT COUNT(*) INTO v_exists
    FROM Ordinances
    WHERE ordinance_id = p_ordinance_id;

    IF v_exists = 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'soft_delete_ordinance: No ordinance found with the given ordinance_id.';
    END IF;

    -- Guard against re-processing an already soft-deleted record
    SELECT COUNT(*) INTO v_deleted
    FROM Ordinances
    WHERE ordinance_id = p_ordinance_id
      AND deleted_at IS NOT NULL;

    IF v_deleted > 0 THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'soft_delete_ordinance: Ordinance is already soft-deleted.';
    END IF;

    UPDATE Ordinances
    SET deleted_at = NOW()
    WHERE ordinance_id = p_ordinance_id;

END$$

DELIMITER ;