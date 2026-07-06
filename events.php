<?php
session_start();
include 'header.php';

$db = new SQLite3('academies.db');

// Lógica de pesquisa
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$is_searching = !empty($search);
$search_escaped = SQLite3::escapeString($search);

// Buscar eventos (com ou sem pesquisa)
$query = "SELECT * FROM events";
if ($is_searching) {
    $query .= " WHERE name LIKE '%$search_escaped%' OR martial_art_type LIKE '%$search_escaped%'";
}
$query .= " ORDER BY id DESC";

$result = $db->query($query);
?>

<main style="max-width: 1200px; margin: 0 auto; padding: 2rem;">

    <div class="event-hero">
        <h1>🏆 Calendário de Eventos</h1>
        <p>Descubra os próximos campeonatos, torneios e seminários. Prepare-se para testar os seus limites.</p>
    </div>

    <div class="search-container" style="max-width: 600px; margin: 0 auto 3rem auto;">
        <form class="fp-search-form" method="GET">
            <input type="text" name="search" placeholder="Buscar por torneio ou estilo de luta (ex: BJJ, Karate)..." value="<?php echo htmlspecialchars($search); ?>">
            <button type="submit">🔍 Procurar</button>
        </form>
        <?php if ($is_searching): ?>
            <div style="margin-top: 10px; text-align:center;">
                <a href="events.php" class="clear-search">✖ Limpar Pesquisa</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="events-grid">
        <?php
        $has_events = false;
        while ($event = $result->fetchArray(SQLITE3_ASSOC)):
            $has_events = true;
        ?>
            <div class="event-card-dark" style="display: flex; flex-direction: column;">
                <?php if (!empty($event['profile_image']) && file_exists($event['profile_image'])): ?>
                    <div style="height: 180px; overflow: hidden; border-radius: 8px 8px 0 0; margin: -1.5rem -1.5rem 1rem -1.5rem;">
                        <img src="<?php echo htmlspecialchars($event['profile_image']); ?>" alt="Profile Image" style="width: 100%; height: 100%; object-fit: cover;">
                    </div>
                <?php endif; ?>
                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 0.5rem;">
                    <span class="event-tag"><?php echo htmlspecialchars($event['martial_art_type']); ?></span>
                    <?php if (!empty($event['federation'])): ?>
                        <span style="background: rgba(255, 255, 255, 0.1); color: #ccc; padding: 0.2rem 0.5rem; border-radius: 4px; font-size: 0.8rem;"><?php echo htmlspecialchars($event['federation']); ?></span>
                    <?php endif; ?>
                </div>
                <h3><?php echo htmlspecialchars($event['name']); ?></h3>
                <p class="event-desc" style="flex-grow: 1;"><?php echo htmlspecialchars($event['description']); ?></p>
                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn-card-details" style="margin-top: auto;">Ver Detalhes do Torneio</a>
            </div>
        <?php endwhile; ?>
    </div>

    <?php if (!$has_events): ?>
        <div class="fp-empty-state">
            <span>🏟️</span>
            <p>Nenhum evento encontrado.</p>
        </div>
    <?php endif; ?>

</main>

<?php
$db->close();
include 'footer.php';
?>