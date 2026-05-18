-- =============================================================
-- SEED DATA — TEST POPULATION SCRIPT
-- Dialect:     MariaDB 10.2+
-- Depends on:  schema_definition.sql (must be executed first)
-- =============================================================
-- Generates up to 100 records per table using recursive CTEs
-- and MariaDB random functions. Intended for development and
-- testing environments only. Do NOT run against production.
--
-- Execution order follows FK dependency chain:
--   Roles → Barangays → Categories → Tags
--   → Users → Ordinances → Ordinance_Tags → Comments
--   → Ordinance_Reactions → Comment_Reactions
--   → Soft-delete test cases (Users, Ordinances)
--
-- NOTE: Reaction_Types is no longer seeded — the table was
-- removed from the schema. Reaction values are now expressed
-- as ENUM('like', 'dislike') directly on the reaction tables.
-- =============================================================

USE if0_41928864_encyclawphilia_db;

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
-- 2. BARANGAYS  (33 records — all official Valenzuela City barangays)
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
-- 3. CATEGORIES  (10 records)
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
-- 4. TAGS  (20 records)
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
-- 6. ORDINANCES  (100 records)
--
-- ordinance_number : zero-padded sequential identifier ORD-0001..ORD-0100
-- title            : composed from randomised preamble + subject pools
-- author_sponsor   : NOT NULL per schema; populated for all rows
-- series_year      : NOT NULL per schema; years from 2018 to 2024
-- status           : drawn from the ENUM('Pending','Active','Repealed','Amended')
-- category_id      : randomly assigned; mutable per write-once policy
-- barangay_id      : randomly assigned; write-once-after-null in production,
--                    but seeded here for test coverage
-- date_enacted     : random date within the past five years;
--                    write-once-after-null in production
-- summary          : short auto-generated text; write-once-after-null
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
    ELT(FLOOR(1 + RAND() * 4),
        'Pending', 'Active', 'Repealed', 'Amended')                    AS status,
    DATE_SUB(NOW(), INTERVAL FLOOR(RAND() * 1825) DAY)                 AS date_enacted,
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
-- 7. ORDINANCE_TAGS  (100 records)
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
    n                       AS ordinance_id,
    (MOD(n - 1, 20) + 1)   AS tag_id
FROM seq;


SET FOREIGN_KEY_CHECKS = 1;