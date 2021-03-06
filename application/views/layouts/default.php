<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo "$title"; ?></title>
    <link rel="shortcut icon" href="/public/images/logo.png" type="image/png" />
    <link href="https://fonts.googleapis.com/css?family=Poppins&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/public/styles/header.css">
    <link rel="stylesheet" href="/public/styles/footer.css">
    <link rel="stylesheet" href="/public/styles/login.css">
    <link rel="stylesheet" href="/public/styles/register.css">
    <link rel="stylesheet" href="/public/styles/profile.css">
    <link rel="stylesheet" href="/public/styles/selfie.css">
    <link rel="stylesheet" href="/public/styles/gallery.css">
    <link rel="stylesheet" href="/public/styles/recovery.css">
    <link rel="stylesheet" href="/public/styles/reset.css">
    <link rel="stylesheet" href="/public/styles/confirm.css">


</head>
<body>

<section id="maket">
    <?php require_once "application/views/layouts/header.php" ?>

    <?php echo "$content"; ?>

    <div id="rasporka"></div>

</section>



<?php require_once "application/views/layouts/footer.php" ?>

    <script src="/public/scripts/ajax_register.js"></script>
    <script src="/public/scripts/ajax_profile.js"></script>
    <script src="/public/scripts/ajax_login.js"></script>
    <script src="/public/scripts/ajax_recovery.js"></script>

</body>
</html>