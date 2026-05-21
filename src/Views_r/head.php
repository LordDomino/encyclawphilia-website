<!DOCTYPE html>
<html lang="en" style="scroll-behavior: smooth;">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="google-site-verification" content="Aj0rwC2OO3km5QpsVYiFonJkqjfOrn7ZIgnHu_O5oew" />
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/add_ordinance.css">
    <?php if ($_SESSION['is_admin'] ?? false): ?>
        <link rel="stylesheet" href="css/admin_dashboard.css">
    <?php endif; ?>
</head>