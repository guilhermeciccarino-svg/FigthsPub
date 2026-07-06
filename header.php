<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// LÓGICA DAS NOTIFICAÇÕES (Apenas para Alunos)
$pending_count = 0;
$notifs = [];

if (isset($_SESSION['user_id']) && $_SESSION['role'] == 'user') {
    try {
        // Abre uma ligação temporária apenas para ler as notificações
        $db_header = new SQLite3('academies.db');
        $stmt_notif = $db_header->prepare("SELECT id, type, message FROM notifications WHERE user_id = :uid AND status = 'pending' ORDER BY created_at DESC");
        $stmt_notif->bindValue(':uid', $_SESSION['user_id'], SQLITE3_INTEGER);
        $res_notif = $stmt_notif->execute();
        
        while($row = $res_notif->fetchArray(SQLITE3_ASSOC)) {
            $notifs[] = $row;
        }
        $pending_count = count($notifs);
        $db_header->close();
    } catch(Exception $e) {
        // Ignora erros no header para não quebrar o layout
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fight Pub</title>
    <link rel="icon" type="image/png" href="Figth.png">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header>
        <nav>
            <div class="logo">
                <a href="index.php">
                    <img src="Figth.png" alt="Logo Fight Pub" width="160" height="160" style="object-fit: contain; border-radius: 50%;">
                </a>
            </div>
            <input type="checkbox" id="menu-toggle" class="menu-checkbox">
            <label for="menu-toggle" class="menu-icon">&#9776;</label>
            <ul>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'user'): ?>
                        <li class="nav-notifications">
                            <a href="#" class="bell-icon">
                                🔔
                                <?php if($pending_count > 0): ?>
                                    <span class="notification-badge"><?php echo $pending_count; ?></span>
                                <?php endif; ?>
                            </a>
                            
                            <div class="notifications-dropdown" style="display: none; width: 300px;">
                                <div class="notifications-header" style="font-weight: bold; padding-bottom: 5px; border-bottom: 1px solid #ccc; color:#111; text-align: left; margin-bottom: 10px;">Notificações</div>
                                <?php if($pending_count > 0): ?>
                                    <div class="notifications-list" style="max-height: 300px; overflow-y: auto;">
                                        <?php foreach($notifs as $n): ?>
                                            <div class="notif-item type-<?php echo htmlspecialchars($n['type']); ?>" style="padding: 10px; border-bottom: 1px solid #eee; text-align: left;">
                                                <div class="notif-content" style="display: flex; gap: 8px; margin-bottom: 5px;">
                                                    <?php if($n['type'] == 'invite'): ?>
                                                        <span class="notif-icon">🤝</span>
                                                    <?php elseif($n['type'] == 'alert'): ?>
                                                        <span class="notif-icon">⚠️</span>
                                                    <?php elseif($n['type'] == 'graduation'): ?>
                                                        <span class="notif-icon">🥋</span>
                                                    <?php else: ?>
                                                        <span class="notif-icon">ℹ️</span>
                                                    <?php endif; ?>
                                                    <p style="margin: 0; font-size: 0.9rem; color: #333;"><?php echo htmlspecialchars($n['message']); ?></p>
                                                </div>

                                                <?php if($n['type'] == 'invite'): ?>
                                                    <div class="notif-actions" style="display: flex; gap: 5px; margin-top: 5px; justify-content: flex-end;">
                                                        <a href="process_invite.php?action=accept&id=<?php echo $n['id']; ?>" class="btn-accept" style="font-size: 0.8rem; padding: 2px 8px; background-color: #4CAF50; color: white; text-decoration: none; border-radius: 3px;">Aceitar</a>
                                                        <a href="process_invite.php?action=reject&id=<?php echo $n['id']; ?>" class="btn-reject" style="font-size: 0.8rem; padding: 2px 8px; background-color: #f44336; color: white; text-decoration: none; border-radius: 3px;">Recusar</a>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="notif-actions" style="display: flex; gap: 5px; margin-top: 5px; justify-content: flex-end;">
                                                        <a href="process_notif.php?action=read&id=<?php echo $n['id']; ?>" class="btn-read" style="font-size: 0.8rem; padding: 2px 8px; background: #eee; color: #333; text-decoration: none; border-radius: 3px; border: 1px solid #ccc;">Marcar lida</a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="notif-empty" style="padding: 10px; color: #666; text-align: center; font-size: 0.9rem;">
                                        Nenhuma notificação nova.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endif; ?>
<li><a href="index.php">Início</a></li>
                <li><a href="academies.php">Academias</a></li>
                <li><a href="instructors.php">Instrutores</a></li>
                <li><a href="graduation.php">Graduação</a></li>
                <li><a href="events.php">Eventos</a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <?php if($_SESSION['role'] == 'user'): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">Perfil <span class="arrow">▼</span></a>
                            <ul class="dropdown-menu">
                                <li><a href="user_profiles.php">Perfil</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if($_SESSION['role'] == 'admin'): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">Admin <span class="arrow">▼</span></a>
                            <ul class="dropdown-menu">
                                <li><a href="admin.php">Admin</a></li>
                                <li><a href="admin_announcements.php">Gerir Avisos</a></li>
                                <li><a href="user_profiles.php">Perfil</a></li>
                            </ul>
                        </li>
                    <?php elseif($_SESSION['role'] == 'instructor'): ?>
                        <li class="dropdown">
                            <a href="#" class="dropdown-toggle">Instrutor <span class="arrow">▼</span></a>
                            <ul class="dropdown-menu">
                                <li><a href="painel_instructor.php">Painel do Instrutor</a></li>
                                <li><a href="instructor_attendance.php">Presenças</a></li>
                                <li><a href="manage_students.php">Alunos</a></li>
                                <li><a href="user_profiles.php">Perfil</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <li><a href="logout.php">Sair</a></li>
                <?php endif; ?>
                <?php else: ?>
                    <li><a href="login.php">Entrar</a></li>
                    <li><a href="register.php">Registrar</a></li>
                <?php endif; ?>
            </ul>
        </nav>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('header');
            const body = document.body;
            const main = document.querySelector('main');
            let headerHeight = header.offsetHeight;
            let ticking = false;

            // Set initial padding 0 and transitions
            body.style.paddingTop = '0px';
            body.style.transition = 'padding-top 0.3s ease';
            if (main) {
                main.style.transition = 'margin-top 0.3s ease';
            }

            function updateHeader() {
                const isScrolledNearTop = window.scrollY < 100;
                if (isScrolledNearTop) {
                    header.classList.add('scrolled');
                    body.style.paddingTop = headerHeight + 'px';
                } else {
                    header.classList.remove('scrolled');
                    body.style.paddingTop = '0px';
                }
            }

            // Handle resize
            window.addEventListener('resize', function() {
                headerHeight = header.offsetHeight;
                updateHeader();
            });

            // Inicializar estado do header imediatamente (scrollY inicia em 0)
            updateHeader();

            window.addEventListener('scroll', function() {
                if (!ticking) {
                    requestAnimationFrame(updateHeader);
                    ticking = true;
                    setTimeout(() => { ticking = false; }, 16);
                }
            });

            // Trigger on mousemove near top - also update padding
            document.addEventListener('mousemove', function(e) {
                if (e.clientY < 50) {
                    header.classList.add('scrolled');
                    body.style.paddingTop = headerHeight + 'px';
                }
            });

            const bellBtn = document.querySelector('.bell-icon');
            const notifDropdown = document.querySelector('.notifications-dropdown');

            if (bellBtn && notifDropdown) {
                bellBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    notifDropdown.style.display = notifDropdown.style.display === 'block' ? 'none' : 'block';
                });

                notifDropdown.addEventListener('click', function(e) {
                    e.stopPropagation();
                });

                document.addEventListener('click', function() {
                    notifDropdown.style.display = 'none';
                });
            }

            // Mobile menu support for dropdowns
            const menuToggle = document.querySelector('#menu-toggle');
            if (menuToggle) {
                menuToggle.addEventListener('change', function() {
                    const dropdowns = document.querySelectorAll('.dropdown-menu');
                    dropdowns.forEach(dd => dd.style.display = 'none');
                });
            }
        });
        </script>

    </header>