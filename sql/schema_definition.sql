CREATE DATABASE if0_41928864_encyclawphilia_db;

USE if0_41928864_encyclawphilia_db;

-- =============================================================
-- DATABASE SCHEMA DEFINITION
-- Dialect: MariaDB 10+
-- =============================================================


-- -------------------------------------------------------------
-- 1. ROLES
-- -------------------------------------------------------------
CREATE TABLE Roles (
    role_id     INT             NOT NULL AUTO_INCREMENT,
    role_name   VARCHAR(50)     NOT NULL,

    CONSTRAINT pk_roles PRIMARY KEY (role_id),
    CONSTRAINT uq_roles_role_name UNIQUE (role_name)
);


-- -------------------------------------------------------------
-- 2. USERS
-- -------------------------------------------------------------
-- Write Once, Transparent Forever (enforced in PHP, not DB):
--
--   WRITE-ONCE FIELDS (set at INSERT, never touched again):
--     • email    — PHP must reject any UPDATE that changes this field.
--     • role_id  — PHP must reject any UPDATE that changes this field.
--                  role_id changes would retroactively reframe all
--                  historical comments and reactions made by the user.
--
--   MUTABLE FIELDS (permitted post-creation):
--     • username      — may be changed by the user at any time.
--     • password_hash — may be changed by the user at any time.
--     • updated_at    — maintained automatically by the engine via
--                       ON UPDATE CURRENT_TIMESTAMP.
--
--   SOFT DELETION (enforced in PHP):
--     • Hard deletion is forbidden. Deactivation is done by setting
--       deleted_at = NOW() in a dedicated PHP routine.
--     • The same routine must also overwrite, in the same statement:
--         username → 'Deleted User <user_id>_<substr(sha256(original_username), 0, 8)>'
--         email    → 'deleted_user_email_<user_id>_<substr(sha256(original_email), 0, 8)>'
--     • The email format intentionally omits any @domain component so
--       it can never collide with a real registrant email while still
--       satisfying the UNIQUE constraint on email.
--     • Un-deletion (setting deleted_at back to NULL) is forbidden.
--       PHP must reject any UPDATE that clears this field once set.
-- -------------------------------------------------------------
CREATE TABLE Users (
    user_id         INT             NOT NULL AUTO_INCREMENT,
    username        VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role_id         INT             NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,
    deleted_at      DATETIME            NULL,

    CONSTRAINT pk_users          PRIMARY KEY (user_id),
    CONSTRAINT uq_users_username UNIQUE (username),
    CONSTRAINT uq_users_email    UNIQUE (email),
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES Roles (role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);


-- -------------------------------------------------------------
-- 3. BARANGAYS
-- -------------------------------------------------------------
-- Reference table: all 33 official Valenzuela City barangays are
-- seeded at deployment time and treated as stable reference data.
--
-- Access control (enforced in PHP, not DB):
--   • INSERT — restricted to super-administrator or DBA only.
--   • UPDATE — forbidden for all application-level roles.
--   • DELETE — forbidden for all application-level roles.
--              Barangay records are permanently retained to preserve
--              the integrity of historical ordinance associations.
-- -------------------------------------------------------------
CREATE TABLE Barangays (
    barangay_id     INT             NOT NULL AUTO_INCREMENT,
    barangay_name   VARCHAR(100)    NOT NULL,

    CONSTRAINT pk_barangays      PRIMARY KEY (barangay_id),
    CONSTRAINT uq_barangays_name UNIQUE (barangay_name)
);


-- -------------------------------------------------------------
-- 4. CATEGORIES
-- -------------------------------------------------------------
-- Reference table: categories are defined at deployment time and
-- are not modifiable through the standard application interface.
--
-- Access control (enforced in PHP, not DB):
--   • INSERT — restricted to super-administrator or DBA only.
--   • UPDATE — forbidden for all application-level roles.
--   • DELETE — forbidden for all application-level roles.
--              Category records are permanently retained to preserve
--              the integrity of historical ordinance classifications.
-- -------------------------------------------------------------
CREATE TABLE Categories (
    category_id     INT             NOT NULL AUTO_INCREMENT,
    category_name   VARCHAR(100)    NOT NULL,
    description     TEXT                NULL,

    CONSTRAINT pk_categories      PRIMARY KEY (category_id),
    CONSTRAINT uq_categories_name UNIQUE (category_name)
);


-- -------------------------------------------------------------
-- 5. ORDINANCES
-- -------------------------------------------------------------
-- Write Once, Transparent Forever (enforced in PHP, not DB):
--
-- REQUIRED AT CREATION — write-once, never modifiable thereafter:
--   • ordinance_number  — the official legislative identifier.
--   • title             — the full official title of the ordinance.
--   • author_sponsor    — the legislator(s) who authored the ordinance.
--   • series_year       — the legislative series year.
--   These four fields represent the known public identity of the
--   ordinance at the time of registration. PHP must reject any
--   UPDATE that attempts to change them after initial INSERT.
--   Records should not be created if any of these fields are unknown.
--
-- OPTIONAL AT CREATION — write-once-after-null:
--   • category_id   — exempt from write-once; see mutable fields below.
--   • barangay_id   — may be set exactly once from NULL; PHP must reject
--                     any UPDATE that attempts to overwrite a non-NULL value.
--   • date_enacted  — same write-once-after-null rule as barangay_id.
--   • pdf_file      — same write-once-after-null rule as barangay_id.
--   • summary       — same write-once-after-null rule as barangay_id.
--   • full_text     — same write-once-after-null rule as barangay_id.
--   Administrators inputting these fields must be made aware of the
--   write-once policy. Human error in these fields is not correctable
--   through the application; only a DBA can intervene.
--
-- MUTABLE FIELDS — may be updated freely at any time:
--   • status        — reflects the current legislative standing of the
--                     ordinance; does not alter its factual content.
--   • category_id   — reflects the current classification; the original
--                     title and content remain unaffected by reclassification.
--   Any change to any field (mutable or otherwise attempted) should
--   be reflected in updated_at, which the engine maintains automatically.
--
-- ARCHIVING (enforced in PHP, not DB):
--   • Hard deletion is forbidden under any circumstance.
--   • Archiving is performed by setting archived_at = NOW().
--   • Un-archiving (clearing archived_at back to NULL) is forbidden.
--   • PHP must filter out archived records (archived_at IS NOT NULL)
--     from all public-facing queries unless explicitly surfaced by
--     a privileged administrator view.
-- -------------------------------------------------------------
CREATE TABLE Ordinances (
    ordinance_id        INT             NOT NULL AUTO_INCREMENT,
    ordinance_number    VARCHAR(50)     NOT NULL,
    title               TEXT            NOT NULL,
    author_sponsor      TEXT            NOT NULL,
    series_year         VARCHAR(10)     NOT NULL,
    category_id         INT                 NULL,   -- mutable; exempt from write-once
    barangay_id         INT                 NULL,   -- write-once-after-null; see above
    status              ENUM(
                            'Pending',
                            'Active',
                            'Repealed',
                            'Amended'
                        )               NOT NULL DEFAULT 'Pending', -- mutable; exempt from write-once
    date_enacted        DATETIME            NULL,   -- write-once-after-null; see above
    pdf_file            VARCHAR(255)        NULL,   -- write-once-after-null; see above
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,
    summary             TEXT                NULL,   -- write-once-after-null; see above
    full_text           LONGTEXT            NULL,   -- write-once-after-null; see above
    archived_at         DATETIME            NULL,   -- formerly deleted_at; see archiving note

    CONSTRAINT pk_ordinances        PRIMARY KEY (ordinance_id),
    CONSTRAINT uq_ordinances_number UNIQUE (ordinance_number),
    CONSTRAINT fk_ordinances_category
        FOREIGN KEY (category_id) REFERENCES Categories (category_id)
        ON UPDATE CASCADE ON DELETE SET NULL,
    CONSTRAINT fk_ordinances_barangay
        FOREIGN KEY (barangay_id) REFERENCES Barangays (barangay_id)
        ON UPDATE CASCADE ON DELETE SET NULL
);


-- -------------------------------------------------------------
-- 6. TAGS
-- -------------------------------------------------------------
CREATE TABLE Tags (
    tag_id      INT             NOT NULL AUTO_INCREMENT,
    tag_name    VARCHAR(100)    NOT NULL,

    CONSTRAINT pk_tags PRIMARY KEY (tag_id),
    CONSTRAINT uq_tags_name UNIQUE (tag_name)
);


-- -------------------------------------------------------------
-- 7. ORDINANCE_TAGS  (junction table)
-- -------------------------------------------------------------
CREATE TABLE Ordinance_Tags (
    ordinance_id    INT NOT NULL,
    tag_id          INT NOT NULL,

    CONSTRAINT pk_ordinance_tags PRIMARY KEY (ordinance_id, tag_id),
    CONSTRAINT fk_ordinance_tags_ordinance
        FOREIGN KEY (ordinance_id) REFERENCES Ordinances (ordinance_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ordinance_tags_tag
        FOREIGN KEY (tag_id) REFERENCES Tags (tag_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);


-- -------------------------------------------------------------
-- 8. COMMENTS
-- -------------------------------------------------------------
-- Write Once, Transparent Forever (enforced in PHP, not DB):
--
--   • comment_text is write-once. PHP must reject any UPDATE
--     on this table under all circumstances.
--   • Hard deletion is forbidden. PHP must reject any DELETE
--     on this table under all circumstances.
--   • No updated_at or deleted_at/archived_at column is added
--     intentionally — their absence signals at the schema level
--     that neither mutation nor removal is a supported operation.
--   • PHP must display a clear public notice in the comment UI
--     informing users that submitted comments are permanent and
--     publicly visible before they confirm submission.
-- -------------------------------------------------------------
CREATE TABLE Comments (
    comment_id      INT         NOT NULL AUTO_INCREMENT,
    ordinance_id    INT         NOT NULL,
    user_id         INT         NOT NULL,
    comment_text    TEXT        NOT NULL,
    created_at      DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_comments PRIMARY KEY (comment_id),
    CONSTRAINT fk_comments_ordinance
        FOREIGN KEY (ordinance_id) REFERENCES Ordinances (ordinance_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comments_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);


-- NOTE FOR DEVELOPER:
-- The Reaction_Types lookup table has been removed from the schema.
-- Reaction values are now expressed as ENUM('like', 'dislike') directly
-- on Ordinance_Reactions and Comment_Reactions, consistent with the
-- pattern used for Ordinances.status. A dedicated lookup table is only
-- warranted when values are dynamic; since like/dislike are permanently
-- fixed, the indirection adds joins with no integrity benefit.
-- Remove this table from any existing migration scripts and seed data.


-- -------------------------------------------------------------
-- 10. ORDINANCE_REACTIONS
-- -------------------------------------------------------------
-- Civic engagement rule (enforced in PHP, not DB):
--
--   • A user may hold exactly one reaction per ordinance at any
--     time, enforced by the UNIQUE constraint on (ordinance_id, user_id).
--   • Reactions are toggleable: PHP implements the following logic:
--       - No existing row   → INSERT the new reaction (like or dislike).
--       - Row exists, same reaction type  → DELETE the row (un-react).
--       - Row exists, different reaction  → UPDATE reaction_type to switch.
--   • Direct UPDATE or DELETE outside of the toggle routine is
--     forbidden at the application level.
--   • Archived ordinances (archived_at IS NOT NULL) must not accept
--     new reactions; PHP must enforce this guard before any INSERT.
-- -------------------------------------------------------------
CREATE TABLE Ordinance_Reactions (
    reaction_id         INT                         NOT NULL AUTO_INCREMENT,
    ordinance_id        INT                         NOT NULL,
    user_id             INT                         NOT NULL,
    reaction_type       ENUM('like', 'dislike')     NOT NULL,
    created_at          DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_ordinance_reactions   PRIMARY KEY (reaction_id),
    CONSTRAINT uq_ordinance_reactions   UNIQUE (ordinance_id, user_id),
    CONSTRAINT fk_ordinance_reactions_ordinance
        FOREIGN KEY (ordinance_id) REFERENCES Ordinances (ordinance_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_ordinance_reactions_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);


-- -------------------------------------------------------------
-- 11. COMMENT_REACTIONS
-- -------------------------------------------------------------
-- Civic engagement rule (enforced in PHP, not DB):
--
--   • A user may hold exactly one reaction per comment at any
--     time, enforced by the UNIQUE constraint on (comment_id, user_id).
--   • Reactions are toggleable: PHP implements the same three-branch
--     logic as Ordinance_Reactions (INSERT / DELETE / UPDATE).
--   • Direct UPDATE or DELETE outside of the toggle routine is
--     forbidden at the application level.
-- -------------------------------------------------------------
CREATE TABLE Comment_Reactions (
    reaction_id         INT                         NOT NULL AUTO_INCREMENT,
    comment_id          INT                         NOT NULL,
    user_id             INT                         NOT NULL,
    reaction_type       ENUM('like', 'dislike')     NOT NULL,
    created_at          DATETIME                    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_comment_reactions     PRIMARY KEY (reaction_id),
    CONSTRAINT uq_comment_reactions     UNIQUE (comment_id, user_id),
    CONSTRAINT fk_comment_reactions_comment
        FOREIGN KEY (comment_id) REFERENCES Comments (comment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comment_reactions_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);