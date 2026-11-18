
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/activity.php";

// Récupération des articles et de leurs catégories
$sql = "SELECT
            a.id,
            a.titre,
            a.slug,
            a.image_couverture,
            a.statut,
            a.date_publication,
            a.created_at,
            c.nom AS categorie
        FROM articles a
        LEFT JOIN categories c ON a.categorie_id = c.id
        ORDER BY a.created_at DESC";

$stmt = $pdo->query($sql);
$articles = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>







<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestion des articles — AGZ Solutions</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="agz-sidebar"></aside>

  <div>
    <header class="topbar" id="agz-topbar"></header>

    <main class="content">

        <?php if (($_GET['succes'] ?? '') === 'article_supprime'): ?>
          <div id="notification-succes" role="status" style="
              display: flex;
              align-items: center;
              gap: 12px;
              margin-bottom: 20px;
              padding: 16px 20px;
              color: #166534;
              background: #f0fdf4;
              border: 1px solid #bbf7d0;
              border-radius: 10px;
              box-shadow: 0 4px 12px rgba(0, 0, 0, 0.04);
          ">
            <span style="font-size: 22px;">✓</span>
            <div>
              <strong>Suppression réussie</strong>
              <div style="margin-top: 4px; font-size: 14px;">
                L’article a été supprimé avec succès.
              </div>
            </div>
          </div>

          <script>
            document.addEventListener('DOMContentLoaded', () => {
              const notification = document.getElementById('notification-succes');

              if (notification) {
                setTimeout(() => {
                  notification.style.transition = 'opacity 0.4s ease';
                  notification.style.opacity = '0';

                  setTimeout(() => notification.remove(), 400);
                }, 4000);

                // Nettoyer l'URL sans recharger la page
                const url = new URL(window.location.href);
                url.searchParams.delete('succes');
                window.history.replaceState({}, '', url);
              }
            });
          </script>
        <?php endif; ?>


      <div class="page-head">
        <div class="page-head-row">
          <div>
            <h1>Gestion des articles</h1>
            <p>Créez et gérez vos articles de blog.</p>
          </div>
          <a href="ajouter-article.php" class="btn btn-primary" id="newArticleBtn">Nouvel article</a>
        </div>
      </div>

      <div class="card">
        <div class="card-header">
          <div class="topbar-search" style="width:280px"><input type="text" placeholder="Rechercher un article…"></div>
          <span class="muted">12 articles</span>
        </div>

        <div class="table-wrap">
          <table class="data-table">
            <thead>
              <tr><th>#</th><th>Image</th><th>Titre</th><th>Catégorie</th><th>Statut</th><th>Date</th><th>Actions</th></tr>
            </thead>
            <tbody id="articlesBody"></tbody>
          </table>
        </div>

        <div class="pagination">
          <span class="muted">Affichage 1–5 sur 12</span>
          <div class="page-btns">
            <button class="page-btn active">1</button>
            <button class="page-btn">2</button>
            <button class="page-btn">3</button>
          </div>
        </div>
      </div>
    </main>
  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
  AGZ.initLayout('articles', { searchPlaceholder:'Rechercher…' });


const ARTICLES = <?= json_encode(
    array_map(function ($article) {
        return [
            'id' => (int) $article['id'],
            'titre' => $article['titre'],
            'cat' => $article['categorie'] ?? 'Sans catégorie',
            'statut' => match ($article['statut']) {
                'publie' => 'Publié',
                'brouillon' => 'Brouillon',
                'archive' => 'Archivé',
                default => 'Inconnu'
            },
            'date' => $article['date_publication']
                ? date('d/m/Y', strtotime($article['date_publication']))
                : date('d/m/Y', strtotime($article['created_at'])),
            'image' => $article['image_couverture']
        ];
    }, $articles),
    JSON_UNESCAPED_UNICODE
) ?>;

  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('newArticleBtn').innerHTML = AGZ.icon('plus') + ' Nouvel article';
    const body = document.getElementById('articlesBody');

body.innerHTML = ARTICLES.map((a, i) => `
  <tr>
    <td>${i + 1}</td>
    <td>
      <div class="row-thumb" style="display:flex;align-items:center;justify-content:center;font-size:18px">
        ${a.image
          ? `<img src="${a.image}" alt="" style="width:100%;height:100%;object-fit:cover;border-radius:6px">`
          : '📝'}
      </div>
    </td>
    <td class="cell-title">${a.titre}</td>
    <td>${a.cat}</td>
    <td>
      <span class="badge ${
        a.statut === 'Publié' ? 'badge-green'
        : a.statut === 'Brouillon' ? 'badge-amber'
        : 'badge-gray'
      }">${a.statut}</span>
    </td>
    <td>${a.date}</td>
    <td>
      <div class="actions-cell">
        <a class="icon-action" href="modifier-article.php?id=${a.id}" title="Modifier">${AGZ.icon('edit')}</a>
        <a class="icon-action" href="apercu-article.php?id=${a.id}" title="Aperçu">${AGZ.icon('eye')}</a>
        <a class="icon-action danger" href="supprimer-article.php?id=${a.id}" title="Supprimer">${AGZ.icon('trash')}</a>
      </div>
    </td>
  </tr>`).join('');

  });
</script>
</body>
</html>
