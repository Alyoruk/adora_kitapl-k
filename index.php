<?php 
require_once 'db.php'; 

// İletişim Formu Gönderimi
$msg_status = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if(!empty($name) && !empty($email) && !empty($message)) {
        $stmt = $pdo->prepare("INSERT INTO messages (name, email, message) VALUES (?, ?, ?)");
        $stmt->execute([$name, $email, $message]);
        $msg_status = "<p style='color:green;'>Mesajınız başarıyla gönderildi.</p>";
    } else {
        $msg_status = "<p style='color:red;'>Lütfen tüm alanları doldurun.</p>";
    }
}

// Hakkında Metni Çekme
$stmt = $pdo->prepare("SELECT meta_value FROM settings WHERE meta_key = 'about_text'");
$stmt->execute();
$about_text = $stmt->fetchColumn() ?: 'Biyografi bulunamadı.';
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ADORA YAĞMUR</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header class="site-header">
        <div class="header-container">
            <h1 class="site-title">ADORA YAĞMUR</h1>
            <nav class="main-nav">
                <ul class="nav-list">
                    <li><a href="#imza_gunleri" class="nav-link">İmza Günleri</a></li>
                    <li><a href="#eserler" class="nav-link">Eserler & Hikayeler</a></li>
                    <li><a href="#hakkimda" class="nav-link">Hakkında</a></li>
                    <li><a href="#iletisim" class="nav-link">İletişim</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main class="main-content">

        <!-- İMZA GÜNLERİ -->
        <section id="imza_gunleri" class="content-section">
            <h2 class="section-title">İmza Günleri</h2>
            <div class="events-list">
                <?php
                $events = $pdo->query("SELECT * FROM events ORDER BY id DESC")->fetchAll();
                if(count($events) > 0):
                    foreach($events as $event): ?>
                        <div class="event-card">
                            <span class="event-date"><?= e($event['event_date']) ?></span>
                            <p class="event-address"><?= e($event['event_address']) ?></p>
                        </div>
                    <?php endforeach;
                else: ?>
                    <p>Yaklaşan imza günü bulunmamaktadır.</p>
                <?php endif; ?>
            </div>
        </section>

        <!-- ESERLER BÖLÜMÜ -->
        <section id="eserler" class="content-section">
            <h2 class="section-title">Eserler</h2>
            
            <?php
            $books = $pdo->query("SELECT * FROM books ORDER BY id DESC")->fetchAll();
            foreach($books as $book): 
                // Kitabın ilk bölümünün ID'sini bulalım
                $chStmt = $pdo->prepare("SELECT id FROM chapters WHERE book_id = ? ORDER BY sort_order ASC LIMIT 1");
                $chStmt->execute([$book['id']]);
                $first_ch_id = $chStmt->fetchColumn();
                $read_link = $first_ch_id ? "kitap-detay.php?chapter_id=".$first_ch_id : "#";
            ?>
            <article class="book-card <?= e($book['theme_class']) ?>">
                <a href="<?= $read_link ?>" class="book-card-link">
                    <div class="book-cover-wrapper">
                        <img src="uploads/<?= e($book['cover_image']) ?>" alt="Kitap Kapağı" class="book-cover">
                    </div>
                    <div class="book-card-info">
                        <h3 class="book-title"><?= e($book['title']) ?></h3>
                        <p class="book-short-desc"><?= e($book['short_desc']) ?></p>
                        <span class="btn-read-more">Kitabı Oku &rarr;</span>
                    </div>
                </a>
            </article>
            <?php endforeach; ?>
        </section>
            
        <!-- HAKKINDA BÖLÜMÜ -->
        <section id="hakkimda" class="content-section">
            <h2 class="section-title">Hakkında</h2>
            <div class="about-content">
                <p><?= nl2br(e($about_text)) ?></p>
            </div>
        </section>

        <!-- İLETİŞİM BÖLÜMÜ -->
        <section id="iletisim" class="content-section">
            <h2 class="section-title">İletişim</h2>
            <p class="section-description">Görüş, öneri veya telif hakları iletişimi için mesaj gönderebilirsiniz.</p>
            <?= $msg_status ?>
            <form class="contact-form" action="index.php#iletisim" method="POST">
                <div class="form-group">
                    <label for="contact-name" class="form-label">Adınız Soyadınız:</label>
                    <input type="text" id="contact-name" name="name" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="contact-email" class="form-label">E-posta Adresiniz:</label>
                    <input type="email" id="contact-email" name="email" class="form-input" required>
                </div>
                <div class="form-group">
                    <label for="contact-message" class="form-label">Mesajınız:</label>
                    <textarea id="contact-message" name="message" rows="5" class="form-textarea" required></textarea>
                </div>
                <button type="submit" name="send_message" class="btn btn-submit">Mesajı Gönder</button>
            </form>
        </section>

    </main>

    <footer class="site-footer">
        <p>&copy; Tüm Hakları Yazarına Aittir.</p>
    </footer>

</body>
</html>
