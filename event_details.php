<?php
session_start();
include 'header.php';

// Verifica se foi passado um ID de evento válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: events.php');
    die();
}

$event_id = (int)$_GET['id'];
$db = new SQLite3('academies.db');

// Procura o evento na base de dados
$stmt = $db->prepare("SELECT * FROM events WHERE id = :id");
$stmt->bindValue(':id', $event_id, SQLITE3_INTEGER);
$result = $stmt->execute();
$event = $result->fetchArray(SQLITE3_ASSOC);

// Se o evento não existir, volta para a lista
if (!$event) {
    header('Location: events.php');
    die();
}

// Handle Image Upload for Gallery
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_gallery_image'])) {
    if (isset($_SESSION['user_id']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'instructor')) {
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $tmp_name = $_FILES['gallery_image']['tmp_name'];
            $name = basename($_FILES['gallery_image']['name']);
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];

            if (in_array($ext, $allowed_exts)) {
                $new_filename = uniqid('event_gal_') . '.' . $ext;
                $upload_path = 'uploads/event_gallery/' . $new_filename;

                if (move_uploaded_file($tmp_name, $upload_path)) {
                    $desc = isset($_POST['image_description']) ? trim($_POST['image_description']) : '';
                    $stmt_gal = $db->prepare("INSERT INTO event_gallery (event_id, image_path, description) VALUES (:eid, :path, :desc)");
                    $stmt_gal->bindValue(':eid', $event_id, SQLITE3_INTEGER);
                    $stmt_gal->bindValue(':path', $upload_path, SQLITE3_TEXT);
                    $stmt_gal->bindValue(':desc', $desc, SQLITE3_TEXT);
                    $stmt_gal->execute();
                    $success_msg = "Imagem adicionada à galeria com sucesso!";
                } else {
                    $error_msg = "Erro ao mover a imagem.";
                }
            } else {
                $error_msg = "Formato de imagem inválido. Apenas JPG, PNG, WEBP e GIF.";
            }
        } else {
             $error_msg = "Erro no upload da imagem.";
        }
    }
}

// Handle Gallery Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_gallery_review'])) {
    if (isset($_SESSION['user_id'])) {
        $gal_id = (int)$_POST['gallery_id'];
        $rating = (int)$_POST['rating'];
        $comment = trim($_POST['comment']);
        $user_id = $_SESSION['user_id'];

        if ($rating >= 1 && $rating <= 5) {
             $stmt_rev = $db->prepare("INSERT INTO gallery_reviews (gallery_id, user_id, rating, comment) VALUES (:gid, :uid, :rating, :comment)");
             $stmt_rev->bindValue(':gid', $gal_id, SQLITE3_INTEGER);
             $stmt_rev->bindValue(':uid', $user_id, SQLITE3_INTEGER);
             $stmt_rev->bindValue(':rating', $rating, SQLITE3_INTEGER);
             $stmt_rev->bindValue(':comment', $comment, SQLITE3_TEXT);
             $stmt_rev->execute();
             $success_msg = "Avaliação enviada com sucesso!";
        }
    }
}

// Fetch Gallery Images
$gallery_stmt = $db->prepare("SELECT * FROM event_gallery WHERE event_id = :eid ORDER BY created_at DESC");
$gallery_stmt->bindValue(':eid', $event_id, SQLITE3_INTEGER);
$gallery_res = $gallery_stmt->execute();
$gallery_images = [];
while ($row = $gallery_res->fetchArray(SQLITE3_ASSOC)) {
    $gallery_images[] = $row;
}

// =========================================================================
// FUNÇÃO MÁGICA: Transforma o texto com "Enter" numa Lista HTML (<ul><li>)
// =========================================================================
function formatar_lista($texto_do_banco) {
    if (empty(trim($texto_do_banco))) {
        return "<p class='empty-note'>Nenhuma informação fornecida.</p>";
    }
    $linhas = explode("\n", trim($texto_do_banco));
    $html = "<ul>";
    foreach ($linhas as $linha) {
        $linha = trim($linha);
        if (!empty($linha)) {
            $html .= "<li>" . htmlspecialchars($linha) . "</li>";
        }
    }
    $html .= "</ul>";
    return $html;
}
?>

<main style="max-width: 1000px; margin: 0 auto; padding: 2rem;">

    <?php if (isset($success_msg)): ?>
        <div class="fp-alert fp-alert-success" style="background: #4CAF50; color: white; padding: 1rem; margin-bottom: 1rem; border-radius: 5px; text-align: center;">
            <?php echo $success_msg; ?>
        </div>
    <?php endif; ?>
    <?php if (isset($error_msg)): ?>
        <div class="fp-alert fp-alert-error" style="background: #f44336; color: white; padding: 1rem; margin-bottom: 1rem; border-radius: 5px; text-align: center;">
            <?php echo $error_msg; ?>
        </div>
    <?php endif; ?>

    <div class="event-detail-hero" style="position: relative; overflow: hidden; <?php if(!empty($event['profile_image'])) echo 'padding: 4rem 2rem;'; ?>">
        <?php if(!empty($event['profile_image'])): ?>
            <div style="position: absolute; top: 0; left: 0; right: 0; bottom: 0; z-index: 0;">
                <img src="<?php echo htmlspecialchars($event['profile_image']); ?>" style="width: 100%; height: 100%; object-fit: cover; opacity: 0.3;">
            </div>
        <?php endif; ?>
        <div style="position: relative; z-index: 1;">
            <div style="display: flex; justify-content: center; gap: 10px; margin-bottom: 1rem;">
                <span class="event-tag"><?php echo htmlspecialchars($event['martial_art_type']); ?></span>
                <?php if(!empty($event['federation'])): ?>
                    <span style="background: rgba(255, 255, 255, 0.2); color: #fff; padding: 0.3rem 0.8rem; border-radius: 20px; font-weight: bold; font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; border: 1px solid rgba(255,255,255,0.4);"><?php echo htmlspecialchars($event['federation']); ?></span>
                <?php endif; ?>
            </div>
            <h1 style="text-shadow: 2px 2px 4px rgba(0,0,0,0.8);"><?php echo htmlspecialchars($event['name']); ?></h1>
        </div>
    </div>

    <div class="event-detail-body">
        <h2>📖 Sobre o Torneio</h2>
        <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
    </div>

    <div class="event-info-grid">

        <div class="event-info-card">
            <h3><span>⚖️</span> Livro de Regras</h3>
            <hr>
            <?php echo formatar_lista($event['rules']); ?>
        </div>

        <div class="event-info-card">
            <h3><span>⚖️</span> Tabela de Pesos</h3>
            <hr>
            <?php echo formatar_lista($event['weight_classes']); ?>
        </div>

        <div class="event-info-card">
            <h3><span>🥋</span> Graduações</h3>
            <hr>
            <?php echo formatar_lista($event['belt_ranks']); ?>
        </div>

        <div class="event-info-card">
            <h3><span>👶</span> Classes de Idade</h3>
            <hr>
            <?php echo formatar_lista($event['age_classes'] ?? ''); ?>
        </div>

    </div>

    <div class="fp-divider" style="height: 2px; background: #333; margin: 3rem 0;"></div>

    <div class="event-gallery-section">
        <h2 style="color: #ff9800; text-align: center; margin-bottom: 2rem;">📸 Galeria do Evento</h2>

        <?php if (isset($_SESSION['user_id']) && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'instructor')): ?>
            <div style="background: #222; padding: 1.5rem; border-radius: 8px; margin-bottom: 2rem; border: 1px solid #333;">
                <h3 style="margin-top: 0; color: #fff;">Adicionar Imagem</h3>
                <form method="POST" enctype="multipart/form-data" style="display: flex; gap: 10px; align-items: flex-end; flex-wrap: wrap;">
                    <div style="flex: 1; min-width: 250px;">
                        <label style="display: block; color: #ccc; margin-bottom: 5px; font-size: 0.9rem;">Imagem:</label>
                        <input type="file" name="gallery_image" accept="image/*" required style="width: 100%; padding: 8px; background: #111; color: #fff; border: 1px solid #444; border-radius: 4px;">
                    </div>
                    <div style="flex: 2; min-width: 250px;">
                        <label style="display: block; color: #ccc; margin-bottom: 5px; font-size: 0.9rem;">Descrição/Legenda (opcional):</label>
                        <input type="text" name="image_description" placeholder="Ex: Pódio da categoria absoluto" style="width: 100%; padding: 10px; background: #111; color: #fff; border: 1px solid #444; border-radius: 4px;">
                    </div>
                    <button type="submit" name="upload_gallery_image" class="btn" style="padding: 10px 20px;">Upload</button>
                </form>
            </div>
        <?php endif; ?>

        <?php if (empty($gallery_images)): ?>
            <div class="fp-empty-state">
                <span>📷</span>
                <p>Nenhuma imagem disponível nesta galeria ainda.</p>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 1.5rem;">
                <?php foreach ($gallery_images as $img): ?>
                    <div style="background: #1a1a1a; border-radius: 8px; overflow: hidden; border: 1px solid #333; display: flex; flex-direction: column;">
                        <img src="<?php echo htmlspecialchars($img['image_path']); ?>" alt="Event photo" style="width: 100%; height: 250px; object-fit: cover; display: block;">
                        <div style="padding: 1rem; flex-grow: 1; display: flex; flex-direction: column;">
                            <?php if(!empty($img['description'])): ?>
                                <p style="color: #ddd; margin-top: 0; font-size: 0.95rem; line-height: 1.4; flex-grow: 1;"><?php echo htmlspecialchars($img['description']); ?></p>
                            <?php endif; ?>

                            <hr style="border: 0; border-top: 1px solid #333; margin: 1rem 0;">

                            <!-- Fetch Reviews for this image -->
                            <?php
                            $stmt_r = $db->prepare("SELECT gr.*, u.username FROM gallery_reviews gr JOIN users u ON gr.user_id = u.id WHERE gr.gallery_id = :gid ORDER BY gr.created_at DESC");
                            $stmt_r->bindValue(':gid', $img['id'], SQLITE3_INTEGER);
                            $res_r = $stmt_r->execute();
                            $reviews = [];
                            $total_rating = 0;
                            while($r = $res_r->fetchArray(SQLITE3_ASSOC)) {
                                $reviews[] = $r;
                                $total_rating += $r['rating'];
                            }
                            $avg_r = count($reviews) > 0 ? $total_rating / count($reviews) : 0;
                            ?>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                                <span style="color: #ff9800; font-weight: bold;">
                                    <?php echo count($reviews) > 0 ? number_format($avg_r, 1) . ' ★' : 'Sem avaliações'; ?>
                                </span>
                                <span style="color: #888; font-size: 0.8rem;"><?php echo count($reviews); ?> avaliações</span>
                            </div>

                            <div style="max-height: 100px; overflow-y: auto; margin-bottom: 10px; padding-right: 5px; font-size: 0.85rem; color: #bbb;">
                                <?php foreach($reviews as $rev): ?>
                                    <div style="margin-bottom: 8px; border-left: 2px solid #ff9800; padding-left: 8px;">
                                        <strong style="color: #fff;">@<?php echo htmlspecialchars($rev['username']); ?></strong> <span style="color: #ff9800;"><?php echo str_repeat('★', $rev['rating']); ?></span>
                                        <?php if(!empty($rev['comment'])): ?>
                                            <br><i>"<?php echo htmlspecialchars($rev['comment']); ?>"</i>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <?php if (isset($_SESSION['user_id'])): ?>
                                <form method="POST" style="margin-top: auto; background: #111; padding: 10px; border-radius: 4px;">
                                    <input type="hidden" name="gallery_id" value="<?php echo $img['id']; ?>">
                                    <div style="display: flex; gap: 5px; align-items: center; margin-bottom: 8px;">
                                        <span style="color: #ccc; font-size: 0.85rem;">Avaliar:</span>
                                        <select name="rating" required style="background: #222; color: #fff; border: 1px solid #444; padding: 4px; border-radius: 3px;">
                                            <option value="5">5 Estrelas</option>
                                            <option value="4">4 Estrelas</option>
                                            <option value="3">3 Estrelas</option>
                                            <option value="2">2 Estrelas</option>
                                            <option value="1">1 Estrela</option>
                                        </select>
                                    </div>
                                    <div style="display: flex; gap: 5px;">
                                        <input type="text" name="comment" placeholder="Comentário..." style="flex-grow: 1; padding: 6px; background: #222; color: #fff; border: 1px solid #444; border-radius: 3px; font-size: 0.85rem;">
                                        <button type="submit" name="submit_gallery_review" style="padding: 6px 12px; background: #ff9800; color: #111; border: none; border-radius: 3px; font-weight: bold; cursor: pointer; font-size: 0.85rem;">Enviar</button>
                                    </div>
                                </form>
                            <?php else: ?>
                                <p style="font-size: 0.8rem; color: #888; text-align: center; margin-top: auto;">Faça <a href="login.php" style="color: #ff9800;">login</a> para avaliar.</p>
                            <?php endif; ?>

                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 3rem;">
        <a href="events.php" class="clear-search">← Voltar ao Calendário</a>
    </div>

</main>

<?php
$db->close();
include 'footer.php';
?>
