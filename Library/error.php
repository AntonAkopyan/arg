<?php
// try to send 503 error
if (!headers_sent()) {
    header("Content-Type: text/html");
    header("HTTP/1.1 503 Service Unavailable");
    header('Retry-After: 600');
}
?>
<h1>Error 503</h1>
<h2>Server Temporarily Unavailable</h2>
<?php if (getenv('DEBUG') && isset($e)) : ?>
    <?php if (is_array($e)) : ?>
        <p><?=$e['message']?></p>
        <pre><?=$e['file'].'#'.$e['line']?></pre>
    <?php else : ?>
        <p><?=$e->getMessage()?></p>
        <pre><?=$e->getTraceAsString()?></pre>
    <?php endif;?>
<?php endif;?>
