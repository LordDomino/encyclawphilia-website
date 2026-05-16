-- =============================================================
-- SEED DATA — TEST POPULATION SCRIPT
-- Dialect:     MySQL 8.0+
-- Depends on:  schema_definition.sql (must be executed first)
-- =============================================================
-- Generates up to 100 records per table using recursive CTEs
-- and MySQL random functions. Intended for development and
-- testing environments only. Do NOT run against production.
--
-- Execution order follows FK dependency chain:
--   Roles → Reaction_Types → Barangays → Categories → Tags
--   → Users → Ordinances → Ordinance_Tags → Comments
--   → Ordinance_Reactions → Comment_Reactions
--   → Soft-delete test cases (Users, Ordinances)
-- =============================================================

SET FOREIGN_KEY_CHECKS = 0;


-- -------------------------------------------------------------
-- 1. ROLES  (5 records)
-- -------------------------------------------------------------
INSERT INTO Roles (role_name) VALUES
    ('Administrator'),
    ('Moderator'),
    ('Legislator'),
    ('Citizen'),
    ('Guest');


-- -------------------------------------------------------------
-- 2. REACTION_TYPES  (2 records)
-- -------------------------------------------------------------
INSERT INTO Reaction_Types (reaction_type) VALUES
    ('like'),
    ('dislike');


-- -------------------------------------------------------------
-- 3. BARANGAYS  (33 records — all official Valenzuela City barangays)
-- -------------------------------------------------------------
INSERT INTO Barangays (barangay_name) VALUES
    ('Arkong Bato'),
    ('Bagbaguin'),
    ('Balangkas'),
    ('Bignay'),
    ('Bisig'),
    ('Canumay East'),
    ('Canumay West'),
    ('Coloong'),
    ('Dalandanan'),
    ('Gen. T. de Leon'),
    ('Hen. Katipunan'),
    ('Isla'),
    ('Karuhatan'),
    ('Lawang Bato'),
    ('Lingunan'),
    ('Mabolo'),
    ('Malanday'),
    ('Malolos'),
    ('Mapulang Lupa'),
    ('Marulas'),
    ('Maysan'),
    ('Palasan'),
    ('Parada'),
    ('Pariancillo Villa'),
    ('Paso de Blas'),
    ('Pasolo'),
    ('Poblacion'),
    ('Polo'),
    ('Punturin'),
    ('Rincon'),
    ('Tagalag'),
    ('Ugong'),
    ('Wawang Pulo');


-- -------------------------------------------------------------
-- 4. CATEGORIES  (10 records)
-- -------------------------------------------------------------
INSERT INTO Categories (category_name, description) VALUES
    ('Health',
        'Ordinances relating to public health, sanitation, and medical services.'),
    ('Education',
        'Ordinances governing schools, literacy programs, and learning institutions.'),
    ('Environment',
        'Ordinances addressing environmental protection, waste management, and pollution control.'),
    ('Public Safety',
        'Ordinances governing peace, order, disaster preparedness, and emergency response.'),
    ('Infrastructure',
        'Ordinances relating to roads, public works, buildings, and urban planning.'),
    ('Taxation',
        'Ordinances concerning local revenue, taxes, fees, and fiscal management.'),
    ('Social Welfare',
        'Ordinances addressing social services, poverty alleviation, and community assistance.'),
    ('Youth Affairs',
        'Ordinances concerning youth programs, student welfare, and development activities.'),
    ('Sports and Recreation',
        'Ordinances relating to sports facilities, recreational programs, and events.'),
    ('Cultural Heritage',
        'Ordinances for preserving and promoting local culture, history, and traditions.');


-- -------------------------------------------------------------
-- 5. TAGS  (20 records)
-- -------------------------------------------------------------
INSERT INTO Tags (tag_name) VALUES
    ('anti-smoking'),
    ('waste-management'),
    ('road-safety'),
    ('flood-control'),
    ('senior-citizens'),
    ('persons-with-disability'),
    ('urban-gardening'),
    ('noise-regulation'),
    ('business-permit'),
    ('building-code'),
    ('anti-littering'),
    ('curfew'),
    ('health-protocol'),
    ('disaster-risk-reduction'),
    ('public-transport'),
    ('market-regulation'),
    ('animal-welfare'),
    ('tree-planting'),
    ('water-conservation'),
    ('fire-safety');


-- -------------------------------------------------------------
-- 6. USERS  (100 records)
--
-- full_name  : randomly assembled from Filipino first/last name pools
-- email      : deterministic pattern user<n>@example.com, guaranteeing
--              uniqueness and satisfying the UNIQUE constraint on email
-- password_hash : SHA-256 of a static test string suffixed with n;
--                 never use real credentials in seed data
-- role_id    : random integer in [1, 5]
-- created_at : random date within the past two years
-- last_active_at : random date within the past 30 days
-- -------------------------------------------------------------
INSERT INTO Users (full_name, email, password_hash, role_id, created_at, last_active_at)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    CONCAT(
        ELT(FLOOR(1 + RAND() * 20),
            'Maria', 'Jose', 'Juan', 'Ana', 'Pedro',
            'Rosa', 'Carlo', 'Luz', 'Mark', 'Lea',
            'Ramon', 'Cristina', 'Eduardo', 'Filipina', 'Andres',
            'Natividad', 'Renato', 'Marites', 'Emmanuel', 'Lourdes'),
        ' ',
        ELT(FLOOR(1 + RAND() * 20),
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo',
            'Garcia', 'Torres', 'Flores', 'Rivera', 'Mendoza',
            'Aquino', 'Villanueva', 'Dela Cruz', 'Fernandez', 'Lopez',
            'Ramos', 'Gonzales', 'Castillo', 'Morales', 'Pascual')
    )                                                                   AS full_name,
    CONCAT('user', n, '@example.com')                                   AS email,
    SHA2(CONCAT('seed_password_', n), 256)                             AS password_hash,
    FLOOR(1 + RAND() * 5)                                              AS role_id,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 730) DAY)                  AS created_at,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 30)  DAY)                  AS last_active_at
FROM seq;


-- -------------------------------------------------------------
-- 7. ORDINANCES  (100 records)
--
-- ordinance_number : zero-padded sequential identifier ORD-0001..ORD-0100
-- title            : composed from randomized preamble + subject pools
-- series_year      : VARCHAR storing years from 2018 to 2024
-- status           : one of draft / pending / enacted / repealed
-- date_enacted     : random date within the past five years
-- pdf_file         : left NULL — no physical files exist in test env
-- full_text        : left NULL — not required for functional testing
-- -------------------------------------------------------------
INSERT INTO Ordinances (
    ordinance_number,
    title,
    author_sponsor,
    series_year,
    category_id,
    barangay_id,
    status,
    date_enacted,
    created_at,
    summary
)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    CONCAT('ORD-', LPAD(n, 4, '0'))                                    AS ordinance_number,
    CONCAT(
        ELT(FLOOR(1 + RAND() * 10),
            'An Ordinance Providing for',
            'An Ordinance Regulating',
            'An Ordinance Establishing',
            'An Ordinance Prohibiting',
            'An Ordinance Authorizing',
            'An Ordinance Requiring',
            'An Ordinance Amending',
            'An Ordinance Appropriating Funds for',
            'An Ordinance Creating',
            'An Ordinance Imposing Guidelines on'),
        ' ',
        ELT(FLOOR(1 + RAND() * 10),
            'Public Health and Sanitation Standards',
            'Solid Waste Segregation and Disposal',
            'Road Use and Traffic Flow Management',
            'Business Operations and Licensing Requirements',
            'Noise and Public Nuisance Abatement',
            'Environmental Protection and Conservation',
            'Senior Citizen and PWD Welfare Programs',
            'Youth Development and Scholarship Activities',
            'Barangay Disaster Risk Reduction Measures',
            'Local Infrastructure and Urban Development')
    )                                                                   AS title,
    CONCAT(
        'Hon. ',
        ELT(FLOOR(1 + RAND() * 10),
            'Santos', 'Reyes', 'Cruz', 'Bautista', 'Ocampo',
            'Garcia', 'Torres', 'Flores', 'Rivera', 'Mendoza')
    )                                                                   AS author_sponsor,
    CAST(FLOOR(2018 + RAND() * 7) AS CHAR)                             AS series_year,
    FLOOR(1 + RAND() * 10)                                             AS category_id,
    FLOOR(1 + RAND() * 33)                                             AS barangay_id,
    ELT(FLOOR(1 + RAND() * 4), 'draft', 'pending', 'enacted', 'repealed') AS status,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 1825) DAY)                 AS date_enacted,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 730)  DAY)                 AS created_at,
    CONCAT(
        'This ordinance addresses concerns related to ',
        ELT(FLOOR(1 + RAND() * 5),
            'public welfare and community health within the barangay.',
            'environmental sustainability and ecological balance.',
            'the regulation of business and commercial activities.',
            'the safety, security, and resilience of residents.',
            'infrastructure development and improvements to public works.')
    )                                                                   AS summary
FROM seq;


-- -------------------------------------------------------------
-- 8. ORDINANCE_TAGS  (100 records)
--
-- Each ordinance n is assigned tag_id = MOD(n-1, 20) + 1.
-- This cycles deterministically through all 20 available tags.
-- Because ordinance_id is always distinct across rows, all
-- (ordinance_id, tag_id) pairs are unique by construction,
-- satisfying the composite PK constraint without collision risk.
-- -------------------------------------------------------------
INSERT INTO Ordinance_Tags (ordinance_id, tag_id)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    n                           AS ordinance_id,
    (MOD(n - 1, 20) + 1)       AS tag_id
FROM seq;


-- -------------------------------------------------------------
-- 9. COMMENTS  (100 records)
--
-- ordinance_id and user_id are independently randomized.
-- No unique constraint exists on Comments, so random assignment
-- is safe here without collision concerns.
-- comment_text is drawn from a pool of realistic civic responses.
-- -------------------------------------------------------------
INSERT INTO Comments (ordinance_id, user_id, comment_text, created_at)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    FLOOR(1 + RAND() * 100)                                            AS ordinance_id,
    FLOOR(1 + RAND() * 100)                                            AS user_id,
    ELT(FLOOR(1 + RAND() * 10),
        'I fully support this ordinance. It addresses a long-standing concern in our community.',
        'This ordinance needs further review. Some provisions are unclear to ordinary residents.',
        'The penalties outlined here seem disproportionate. A graduated enforcement approach would be fairer.',
        'I appreciate the intent of this ordinance but the implementation details are still lacking.',
        'This is a welcome development. Our barangay has needed this regulation for years.',
        'Was this properly consulted with the affected residents and business stakeholders?',
        'The ordinance is well-structured and appears to cover all the necessary aspects.',
        'I have reservations about the enforcement mechanism. Who will be responsible for compliance checks?',
        'This duplicates an existing ordinance from a neighboring barangay. Harmonization is needed.',
        'A public hearing should be conducted before this is enacted to gather community feedback.')
                                                                        AS comment_text,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY)                  AS created_at
FROM seq;


-- -------------------------------------------------------------
-- 10. ORDINANCE_REACTIONS  (100 records)
--
-- The UNIQUE constraint on (ordinance_id, user_id) means that
-- randomly generating both columns risks producing duplicate
-- pairs, which would abort the insert. To guarantee uniqueness
-- by construction, user n is paired with ordinance n (1:1).
-- Since n is always distinct, all pairs are necessarily unique.
-- reaction_type_id is randomized between 1 (like) and 2 (dislike).
-- -------------------------------------------------------------
INSERT INTO Ordinance_Reactions (ordinance_id, user_id, reaction_type_id, created_at)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    n                                                                   AS ordinance_id,
    n                                                                   AS user_id,
    FLOOR(1 + RAND() * 2)                                              AS reaction_type_id,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY)                  AS created_at
FROM seq;


-- -------------------------------------------------------------
-- 11. COMMENT_REACTIONS  (100 records)
--
-- Same uniqueness rationale as Ordinance_Reactions above.
-- Comment n is paired with user n to guarantee unique
-- (comment_id, user_id) pairs across all 100 inserted rows.
-- -------------------------------------------------------------
INSERT INTO Comment_Reactions (comment_id, user_id, reaction_type_id, created_at)
WITH RECURSIVE seq AS (
    SELECT 1 AS n
    UNION ALL
    SELECT n + 1 FROM seq WHERE n < 100
)
SELECT
    n                                                                   AS comment_id,
    n                                                                   AS user_id,
    FLOOR(1 + RAND() * 2)                                              AS reaction_type_id,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 365) DAY)                  AS created_at
FROM seq;


-- -------------------------------------------------------------
-- SOFT-DELETE TEST CASES
--
-- Users 96–100 and Ordinances 96–100 are soft-deleted directly
-- via UPDATE rather than through soft_delete_procedures.sql,
-- keeping this seed file self-contained with no external
-- execution-order dependency.
--
-- Each soft-deleted user receives:
--   full_name  → "Deleted User <8-char UUID token>"
--   email      → "deleted_<user_id>@void.invalid"
--   deleted_at → a random timestamp within the past 60 days
--
-- Each soft-deleted ordinance receives only a deleted_at stamp;
-- its civic metadata is intentionally preserved in full.
-- -------------------------------------------------------------
UPDATE Users
SET
    full_name  = CONCAT('Deleted User ', UPPER(LEFT(REPLACE(UUID(), '-', ''), 8))),
    email      = CONCAT('deleted_', user_id, '@void.invalid'),
    deleted_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 60) DAY)
WHERE user_id BETWEEN 96 AND 100;

UPDATE Ordinances
SET
    deleted_at = DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 60) DAY)
WHERE ordinance_id BETWEEN 96 AND 100;


SET FOREIGN_KEY_CHECKS = 1;