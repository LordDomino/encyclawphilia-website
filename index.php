<?php
$pageTitle = "EncycLawPhilia Valenzuela";
$currentPage = "home";

require_once 'fragments/head.php';
?>

<body class="<?php echo isset($currentPage) ? htmlspecialchars($currentPage) : 'default'; ?>">
    <?php require_once 'fragments/header.php'; ?>

    <search>
        <hgroup class="title">
            <?php require_once 'fragments/brand_title.php';?>
            <p>Wisdom Beyond Law and Order</p>
        </hgroup>
        <form class="search-bar" action="browse.php" method="get">
            <input aria-label="Search for city legislations" autocomplete="off" inputmode="search" type="search"
                placeholder="Search..." name="q" />
        </form>
    </search>
    <main class="home">
        <section class="dashboard" id="browse-latest">
            <div class="flex-column" id="featured">
                <h2>Featured</h2>
                <p>
                    Honorable _____ signs City Ordinance No. 3750 s. 2026 for improving the quality education in the
                    Pamantasan ng Lungsod ng Valenzuela.
                </p>
                <div class="card-featured">
                    <div class="card-title">City Ordinance<br /><span class="numeral-and-series">No. 3750 s. 2026</span>
                    </div>
                    <div class="date">15 April 2026</div>
                    <div class="preview-container">
                        <div class="preview-text">
                            AN ORDINANCE AMENDING CITY ORDINANCE NO. 3725, SERIES OF 2024,
                            ENTITLED "AN ORDINANCE REGULATING THE USE OF PUBLIC PARKS AND
                            OPEN SPACES IN THE CITY OF VALENZUELA", BY INCREASING THE PENALTIES
                            FOR VIOLATIONS AND ADDING PROVISIONS FOR ENVIRONMENTAL PROTECTION.
                        </div>
                    </div>
                </div>
                <a>
                    <p class="link">Read More</p>
                </a>
            </div>
            <div class="flex-column">
                <h1>Latest City Ordinances in Valenzuela</h1>
                <p>Browse the latest city ordinances in Valenzuela.</p>
                <div class="vertical-carousel-frame">
                    <div class="vertical-carousel-wrapper">
                        <ul class="vertical-carousel">
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
                                        <div class="card-title">City Ordinance <span class="numeral-and-series">No. 3749 s. 2026</span></div>
                                        <div class="date">10 April 2026</div>
                                    </div>
                                    <div class="preview-container">
                                        <div class="preview-text">
                                            AN ORDINANCE ESTABLISHING A COMPREHENSIVE TRAFFIC MANAGEMENT
                                            SYSTEM IN HIGH-DENSITY AREAS OF VALENZUELA CITY, INCLUDING
                                            THE INSTALLATION OF TRAFFIC LIGHTS, PEDESTRIAN CROSSINGS,
                                            AND PARKING REGULATIONS TO IMPROVE ROAD SAFETY.
                                        </div>
                                    </div>
                                    <a>
                                        <p class="link">Read More</p>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
                                        <div class="card-title">City Ordinance <span class="numeral-and-series">No. 3747 s. 2026</span></div>
                                        <div class="date">28 February 2026</div>
                                    </div>
                                    <div class="preview-container">
                                        <div class="preview-text">
                                            AN ORDINANCE PROMOTING ENVIRONMENTAL SUSTAINABILITY BY
                                            MANDATING THE USE OF ECO-FRIENDLY MATERIALS IN CITY
                                            CONSTRUCTION PROJECTS AND ENCOURAGING GREEN PRACTICES
                                            AMONG RESIDENTS AND BUSINESSES.
                                        </div>
                                    </div>
                                    <a>
                                        <p class="link">Read More</p>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
                                        <div class="card-title">City Ordinance <span class="numeral-and-series">No. 3746 s. 2026</span></div>
                                        <div class="date">15 February 2026</div>
                                    </div>
                                    <div class="preview-container">
                                        <div class="preview-text">
                                            AN ORDINANCE STRENGTHENING PUBLIC SAFETY MEASURES BY
                                            ENHANCING POLICE PATROL FREQUENCY IN COMMERCIAL AREAS,
                                            INSTALLING ADDITIONAL SECURITY CAMERAS, AND ESTABLISHING
                                            COMMUNITY WATCH PROGRAMS.
                                        </div>
                                    </div>
                                    <a>
                                        <p class="link">Read More</p>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
                                        <div class="card-title">City Ordinance <span class="numeral-and-series">No. 3745 s. 2026</span></div>
                                        <div class="date">01 February 2026</div>
                                    </div>
                                    <div class="preview-container">
                                        <div class="preview-text">
                                            AN ORDINANCE REGULATING THE OPERATION OF FOOD VENDORS
                                            AND STREET FOOD STALLS WITHIN CITY PREMISES, INCLUDING
                                            HEALTH AND SANITATION STANDARDS, PERMIT REQUIREMENTS,
                                            AND DESIGNATED VENDING ZONES.
                                        </div>
                                    </div>
                                    <a>
                                        <p class="link">Read More</p>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
                                        <div class="card-title">City Ordinance <span class="numeral-and-series">No. 3744 s. 2026</span></div>
                                        <div class="date">20 January 2026</div>
                                    </div>
                                    <div class="preview-container">
                                        <div class="preview-text">
                                            AN ORDINANCE ESTABLISHING DIGITAL TRANSFORMATION INITIATIVES
                                            FOR CITY SERVICES, INCLUDING ONLINE PERMIT APPLICATIONS,
                                            E-GOVERNANCE PORTALS, AND MOBILE APPS FOR CITIZEN ENGAGEMENT.
                                        </div>
                                    </div>
                                    <a>
                                        <p class="link">Read More</p>
                                    </a>
                                </div>
                            </li>
                            <li>
                                <div class="card-banner">
                                    <div class="card-header">
                                        <div class="card-title">City Ordinance <span class="numeral-and-series">No. 3748 s. 2026</span></div>
                                        <div class="date">04 March 2026</div>
                                    </div>
                                    <div class="preview-container">
                                        <div class="preview-text">
                                            A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                            SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                            ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                            CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                            THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                            HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                            IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                        </div>
                                    </div>
                                    <a>
                                        <p class="link">Read More</p>
                                    </a>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

        </section>

        <section class="dashboard" id="browse-category">
            <h1>Browse by Category</h1>
            <h2>Traffic and Transportation</h2>
            <div class="carousel-wrapper">
                <ul class="carousel">
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3735 s. 2025</span></h2>
                                <div class="date">12 December 2025</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE REGULATING PARKING IN CENTRAL BUSINESS DISTRICTS,
                                    INCLUDING TIME LIMITATIONS, FEES FOR VIOLATIONS, AND DESIGNATED
                                    PARKING ZONES FOR DISABLED PERSONS AND EMERGENCY VEHICLES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <h2>Health and Sanitation</h2>
            <div class="carousel-wrapper">
                <ul class="carousel">
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3738 s. 2025</span></h2>
                                <div class="date">05 November 2025</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE MANDATING REGULAR CLEAN-UP DRIVES AND PROPER
                                    WASTE SEGREGATION IN RESIDENTIAL AREAS, WITH PENALTIES FOR
                                    NON-COMPLIANCE AND INCENTIVES FOR PARTICIPATING HOUSEHOLDS.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <h2>Public Safety</h2>
            <div class="carousel-wrapper">
                <ul class="carousel">
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3741 s. 2025</span></h2>
                                <div class="date">18 October 2025</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE ESTABLISHING FIRE SAFETY STANDARDS FOR COMMERCIAL
                                    BUILDINGS, INCLUDING REGULAR INSPECTIONS, FIRE EXTINGUISHER
                                    REQUIREMENTS, AND EMERGENCY EVACUATION PROCEDURES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <h2>Environment and Zoning</h2>
            <div class="carousel-wrapper">
                <ul class="carousel">
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3732 s. 2025</span></h2>
                                <div class="date">25 September 2025</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    AN ORDINANCE REGULATING ZONING FOR INDUSTRIAL AREAS TO MINIMIZE
                                    ENVIRONMENTAL IMPACT, INCLUDING EMISSION CONTROLS, WASTE MANAGEMENT,
                                    AND BUFFER ZONES FROM RESIDENTIAL AREAS.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                    <li>
                        <div class="content-card">
                            <div class="content-card-header">
                                <h2>City Ordinance<br /><span class="numeral-and-series">No. 3748 s. 2026</span></h2>
                                <div class="date">04 March 2026</div>
                            </div>
                            <div class="preview-container">
                                <div class="preview-text">
                                    A RESOLUTION DECLARING BARANGAY PARADA ORDINANCE NO. 08-26,
                                    SERIES OF 2026, ENTITLED: ?AN ORDINANCE CREATING AN ADDITIONAL
                                    ELEVEN (11) PLANTILLA POSITIONS OF TWO (2) WATCHMAN 1, ONE (1)
                                    CLERK IV, SEVEN (7) CLERK I AND ONE (1) MESSENGER, IDENTIFYING
                                    THE SOURCE OF FUND AND PROVIDING THE SALARY GRADE THEREOF?,
                                    HAS BEEN REVIEWED IN ACCORDANCE WITH R.A. 7160 AND THE SAME
                                    IS FOUND NOT INCONSISTENT WITH LAW OR ANY CITY ORDINANCES.
                                </div>
                            </div>
                            <a>
                                <p class="link">Read More</p>
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

    </main>

    <?php require_once 'fragments/footer.php'; ?>
</body>
</html>