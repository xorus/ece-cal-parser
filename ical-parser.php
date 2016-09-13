<?php

require __DIR__ . '/vendor/autoload.php';

if (empty($_GET['source'])) {
    header('400 Bad Request');
    echo 'Paramètre manquant : source';
    exit;
}

try {
    $calParser = new \Xorus\CalParser($_GET['source']);
} catch (Exception $e) {
    header('400 Bad Request');
    echo 'Erreur : ' . $e->getMessage();
    exit;
}

$calParser->parseCalendar();