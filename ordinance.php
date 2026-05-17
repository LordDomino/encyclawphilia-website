<?php

$ordinance_id = isset($_GET['id']) ? trim($_GET['id']) : '';
// $safe_ordinance_id = htmlspecialchars($ordinance_id, ENT_QUOTES, 'UTF-8');
$results = [];

$pageTitle = "EncycLawPhilia Valenzuela";
$currentPage = "home";

require_once 'fragments/head.php';
?>

<body>
    <?php require_once 'fragments/header.php'; ?>
    
    <main>
        
    </main>

    <?php require_once 'fragments/footer.php'; ?>

</body>

</html>