<?php

function show_errors($errors)
{
    if (empty($errors)) {
        return;
    }
    foreach ($errors as $key => $error) {
        echo '<div class="alert alert-danger" role="alert">';
        echo '<h4 class="alert-heading">'. htmlspecialchars($key) .'</h4>';
        echo '<hr>';
        echo '<p>' . htmlspecialchars($error) . '</p>';
        echo '</div>';
    }
}