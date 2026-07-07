<?php
$content = file_get_contents('index.php');

$styleBlock = <<<EOD
@media (prefers-color-scheme: light) {
        .fp-cs-dot.active {
            background-color: var(--gold) !important;
            transform: scale(1.2);
        }
        .fp-cs-img-overlay {
            background: linear-gradient(to top, rgba(255,255,255,0.9), transparent) !important;
        }
        .fp-cs-title {
            color: #111111 !important;
        }
        .fp-cs-desc {
            color: #333333 !important;
        }
        .fp-cs-price, .fp-cs-date {
            color: var(--gold) !important;
        }
        .fp-cs-btn {
            background-color: var(--gold) !important;
        }
        .fp-cs-arrow:hover {
            background-color: rgba(0,0,0,0.1) !important;
            color: var(--gold) !important;
        }
    }
EOD;

if (strpos($content, '.fp-cs-arrow:hover') !== false && strpos($content, 'prefers-color-scheme: light') === false) {
    // Find where the style tag ends for the carousel
    $content = preg_replace('/(<\/style>)/', $styleBlock . "\n$1", $content, 1);
    file_put_contents('index.php', $content);
    echo "Fixed index.php\n";
} else {
    echo "Pattern not found or already applied.\n";
}
?>
