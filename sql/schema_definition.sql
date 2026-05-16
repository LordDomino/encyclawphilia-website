-- =============================================================
-- DATABASE SCHEMA DEFINITION
-- Generated from: Database_Schema.pdf
-- Dialect: MySQL 8.0+
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
CREATE TABLE Users (
    user_id         INT             NOT NULL AUTO_INCREMENT,
    full_name       VARCHAR(100)    NOT NULL,
    email           VARCHAR(150)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    role_id         INT             NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_active_at  DATETIME            NULL,
    deleted_at      DATETIME            NULL,

    CONSTRAINT pk_users PRIMARY KEY (user_id),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT fk_users_role
        FOREIGN KEY (role_id) REFERENCES Roles (role_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
);


-- -------------------------------------------------------------
-- 3. BARANGAYS
-- -------------------------------------------------------------
CREATE TABLE Barangays (
    barangay_id     INT             NOT NULL AUTO_INCREMENT,
    barangay_name   VARCHAR(100)    NOT NULL,

    CONSTRAINT pk_barangays PRIMARY KEY (barangay_id),
    CONSTRAINT uq_barangays_name UNIQUE (barangay_name)
);


-- -------------------------------------------------------------
-- 4. CATEGORIES
-- -------------------------------------------------------------
CREATE TABLE Categories (
    category_id     INT             NOT NULL AUTO_INCREMENT,
    category_name   VARCHAR(100)    NOT NULL,
    description     TEXT                NULL,

    CONSTRAINT pk_categories PRIMARY KEY (category_id),
    CONSTRAINT uq_categories_name UNIQUE (category_name)
);


-- -------------------------------------------------------------
-- 5. ORDINANCES
-- -------------------------------------------------------------
CREATE TABLE Ordinances (
    ordinance_id        INT             NOT NULL AUTO_INCREMENT,
    ordinance_number    VARCHAR(50)     NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    author_sponsor      VARCHAR(150)        NULL,
    series_year         VARCHAR(10)         NULL,
    category_id         INT                 NULL,
    barangay_id         INT                 NULL,
    status              VARCHAR(50)     NOT NULL,
    date_enacted        DATETIME            NULL,
    pdf_file            VARCHAR(255)        NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    summary             TEXT                NULL,
    full_text           LONGTEXT            NULL,
    deleted_at          DATETIME            NULL,

    CONSTRAINT pk_ordinances PRIMARY KEY (ordinance_id),
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


-- -------------------------------------------------------------
-- 9. REACTION_TYPES
-- -------------------------------------------------------------
CREATE TABLE Reaction_Types (
    reaction_type_id    INT             NOT NULL AUTO_INCREMENT,
    reaction_type       VARCHAR(50)     NOT NULL,

    CONSTRAINT pk_reaction_types PRIMARY KEY (reaction_type_id),
    CONSTRAINT uq_reaction_types_name UNIQUE (reaction_type)
);


-- -------------------------------------------------------------
-- 10. ORDINANCE_REACTIONS
-- -------------------------------------------------------------
CREATE TABLE Ordinance_Reactions (
    reaction_id     INT             NOT NULL AUTO_INCREMENT,
    ordinance_id    INT             NOT NULL,
    user_id         INT             NOT NULL,
    reaction_type_id    INT             NOT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_ordinance_reactions PRIMARY KEY (reaction_id),
    CONSTRAINT uq_ordinance_reactions UNIQUE (ordinance_id, user_id),
    CONSTRAINT fk_ordinance_reactions_reaction_type
        FOREIGN KEY (reaction_type_id) REFERENCES Reaction_Types (reaction_type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
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
CREATE TABLE Comment_Reactions (
    reaction_id         INT             NOT NULL AUTO_INCREMENT,
    comment_id          INT             NOT NULL,
    user_id             INT             NOT NULL,
    reaction_type_id    INT             NOT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_comment_reactions PRIMARY KEY (reaction_id),
    CONSTRAINT uq_comment_reactions UNIQUE (comment_id, user_id),
    CONSTRAINT fk_comment_reactions_reaction_type
        FOREIGN KEY (reaction_type_id) REFERENCES Reaction_Types (reaction_type_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_comment_reactions_comment
        FOREIGN KEY (comment_id) REFERENCES Comments (comment_id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_comment_reactions_user
        FOREIGN KEY (user_id) REFERENCES Users (user_id)
        ON UPDATE CASCADE ON DELETE CASCADE
);