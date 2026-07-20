<?php 
require_once 'db.php';

$chapter_id = isset($_GET['chapter_id']) ? (int)$_GET['chapter_id'] : 0;

if ($chapter_id <= 0) {
    header('Location: index.php');
    exit;
}

// Bölüm ve Kitap Bilgilerini Çekme
$stmt = $pdo->prepare("SELECT c.*, b.title as book_title, b.reader_theme, b.id as book_id FROM chapters c JOIN books b ON c.book_id = b.id WHERE c.id = ?");
$stmt->execute([$chapter_id]);
$chapter = $stmt->fetch();

if (!$chapter) {
    header('Location: index.php');
    exit;
}

// Kullanıcıyı Tanıma (Kayıtsız Hatırlama)
if (!isset($_COOKIE['anon_user'])) {
    $random_name = "Okur_" . rand(1000, 9999);
    setcookie('anon_user', $random_name, time() + (86400 * 365), "/"); // 1 yıl geçerli
    $_COOKIE['anon_user'] = $random_name;
}
$visitor_username = $_COOKIE['anon_user'];

// İsim Düzenleme / Değiştirme Talebi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_username'])) {
    $new_name = trim($_POST['new_username']);
    if(!empty($new_name)) {
        setcookie('anon_user', $new_name, time() + (86400 * 365), "/");
        $_COOKIE['anon_user'] = $new_name;
        $visitor_username = $new_name;
    }
}

// Yorum Ekleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $paragraph_id = (int)$_POST['paragraph_id'];
    $comment_text = trim($_POST['comment_text'] ?? '');
    
    if ($paragraph_id > 0 && !empty($comment_text)) {
        $cStmt = $pdo->prepare("INSERT INTO comments (paragraph_id, username, comment_text) VALUES (?, ?, ?)");
        $cStmt->execute([$paragraph_id, $visitor_username, $comment_text]);
    }
    header("Location: kitap-detay.php?chapter_id=" . $chapter_id . "#p-wrapper-" . $paragraph_id);
    exit;
}

// Kitabın tüm bölümlerini üst menü için çekelim
$all_chapters = $pdo->prepare("SELECT id, chapter_title FROM chapters WHERE book_id = ? ORDER BY sort_order ASC");
$all_chapters->execute([$chapter['book_id']]);
$chapters_list = $all_chapters->fetchAll();

// Sonraki Bölümü Bulma
$next_chapter_id = null;
foreach ($chapters_list as $key => $ch) {
    if ($ch['id'] == $chapter_id && isset($chapters_list[$key + 1])) {
        $next_chapter_id = $chapters_list[$key + 1]['id'];
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($chapter['book_title']) ?> - <?= e($chapter['chapter_title']) ?></title>
    <link rel="stylesheet" href="style_iki.css">
    <style>
        .hidden { display: none; }
        .user-remember-box { background: #eee; padding: 5px 10px; margin: 5px; font-size:12px; border-radius:4px; display:inline-block; color:#333; }
        .user-remember-box input { font-size:11px; padding:2px; }
    </style>
</head>
<body id="book-reader-body" class="<?= e($chapter['reader_theme']) ?>">

    <header id="main-header" class="site-header">
        <nav id="chapters-nav" class="scrollable-chapters-wrapper">
            <ul id="chapters-list" class="chapters-menu">
                <li id="item-back" class="chapter-item">
                    <a id="link-back" href="index.php" class="chapter-link back-to-author">← Anasayfaya dön</a>
                </li>
                <?php foreach($chapters_list as $ch): ?>
                    <li class="chapter-item">
                        <a href="kitap-detay.php?chapter_id=<?= $ch['id'] ?>" class="chapter-link <?= $ch['id'] == $chapter_id ? 'active-chapter' : '' ?>">
                            <?= e($ch['chapter_title']) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </header>

    <!-- Kullanıcı Hatırlama Çubuğu -->
    <div style="text-align: center; margin-top: 10px;">
        <div class="user-remember-box">
             <strong><?= e($visitor_username) ?></strong> 
            <form action="" method="POST" style="display:inline-block; margin-left:10px;">
                <input type="text" name="new_username" placeholder="İsmini değiştir...">
                <button type="submit" name="update_username">Güncelle</button>
            </form>
        </div>
    </div>

    <main id="main-content" class="reader-container">
        <section id="book-meta-section" class="book-info-header">
            <h1 id="book-title" class="main-title"><?= e($chapter['book_title']) ?> <br><small style="font-size:18px; font-weight:normal;"><?= e($chapter['chapter_title']) ?></small></h1>
        </section>

        <hr id="divider-top" class="section-divider">

        <article id="book-text-article" class="story-content">
            <?php
            // Paragrafları Çek
            $pStmt = $pdo->prepare("SELECT * FROM paragraphs WHERE chapter_id = ? ORDER BY sort_order ASC");
            $pStmt->execute([$chapter_id]);
            $paragraphs = $pStmt->fetchAll();
            
            foreach($paragraphs as $p):
                // Paragraf Yorum Sayısı
                $cCount = $pdo->prepare("SELECT COUNT(*) FROM comments WHERE paragraph_id = ?");
                $cCount->execute([$p['id']]);
                $comment_count = $cCount->fetchColumn();
            ?>
            <div id="p-wrapper-<?= $p['id'] ?>" class="paragraph-wrapper" style="margin-bottom: 25px;">
                <p class="story-paragraph"><?= nl2br(e($p['paragraph_text'])) ?></p>
                
                <div id="p-comments-<?= $p['id'] ?>" class="paragraph-comment-section">
                    <button type="button" class="btn-toggle-comments" onclick="toggleComments(<?= $p['id'] ?>)">
                        💬 Yorumlar (<?= $comment_count ?>)
                    </button>
                    
                    <div id="dropdown-area-<?= $p['id'] ?>" class="comments-dropdown-area hidden" style="background: rgba(0,0,0,0.03); padding:10px; margin-top:5px; border-radius:4px;">
                        <?php
                        $cList = $pdo->prepare("SELECT * FROM comments WHERE paragraph_id = ? ORDER BY id ASC");
                        $cList->execute([$p['id']]);
                        foreach($cList->fetchAll() as $comment):
                        ?>
                        <div class="single-comment" style="margin-bottom:8px; border-bottom:1px dashed #ccc; padding-bottom:4px;">
                            <span class="comment-user" style="font-weight:bold;"><?= e($comment['username']) ?>:</span>
                            <span class="comment-text"><?= e($comment['comment_text']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <form class="comment-inline-form" action="" method="POST" style="margin-top:10px;">
                            <input type="hidden" name="paragraph_id" value="<?= $p['id'] ?>">
                            <input type="text" name="comment_text" class="input-comment-text" placeholder="Bu paragrafa yorum yap..." required style="width:70%; padding:5px;">
                            <button type="submit" name="add_comment" class="btn-submit-comment" style="padding:5px 10px;">Gönder</button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </article>
    </main>

    <footer id="reader-footer" class="content-footer">
        <div id="footer-actions-container" class="actions-wrapper" style="text-align:center; padding:20px;">
            <?php if ($next_chapter_id): ?>
                <a href="kitap-detay.php?chapter_id=<?= $next_chapter_id ?>" class="btn btn-primary" style="text-decoration:none; background:#007bff; color:#fff; padding:10px 20px; border-radius:4px;">Sonraki Bölüme Geç →</a>
            <?php else: ?>
                <span style="color:#666;">Kitabın sonuna geldiniz.</span>
            <?php endif; ?>
        </div>
    </footer>

    <script>
    function toggleComments(id) {
        var area = document.getElementById('dropdown-area-' + id);
        if(area.classList.contains('hidden')) {
            area.classList.remove('hidden');
        } else {
            area.classList.add('hidden');
        }
    }
    </script>
</body>
</html>
//kodlar yapay zekaya temizletilmiş ve en anlaşılır haliyle sizlere sunulmuştur.
