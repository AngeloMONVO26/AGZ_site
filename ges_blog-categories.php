
<?php
require_once "auth.php";
require_once "db.php";
require_once "activity.php";


if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];


$message = "";
$erreur = "";

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["supprimer_categorie"] ?? "") === "1"
) {

        if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Requête refusée : jeton de sécurité invalide.");
    }

    $categorieId = filter_var(
        $_POST["categorie_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$categorieId || $categorieId < 1) {
        $erreur = "Catégorie invalide.";
    } else {
        try {
            $pdo->beginTransaction();

            // Vérifier que la catégorie existe et la verrouiller
            $requeteCategorie = $pdo->prepare(
                "SELECT id, nom FROM categories WHERE id = :id FOR UPDATE"
            );
            $requeteCategorie->execute(["id" => $categorieId]);
            $categorie = $requeteCategorie->fetch(PDO::FETCH_ASSOC);

            if (!$categorie) {
                throw new RuntimeException("Catégorie introuvable.");
            }

            // Empêcher la suppression si des articles y sont associés
            $requeteArticles = $pdo->prepare(
                "SELECT COUNT(*) FROM articles WHERE categorie_id = :id"
            );
            $requeteArticles->execute(["id" => $categorieId]);
            $nombreArticles = (int) $requeteArticles->fetchColumn();

            if ($nombreArticles > 0) {
                $pdo->rollBack();

                header(
                    "Location: ges_blog-categories.php?erreur=suppression_articles"
                );
                exit;
            }

            $suppression = $pdo->prepare(
                "DELETE FROM categories WHERE id = :id"
            );
            $suppression->execute(["id" => $categorieId]);

            enregistrerActivite(
                $pdo,
                "suppression",
                "Suppression de la catégorie : " . $categorie["nom"],
                "categorie",
                $categorieId
            );

            $pdo->commit();

            header("Location: ges_blog-categories.php?succes=suppression");
            exit;
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            error_log("Suppression catégorie : " . $e->getMessage());

            header("Location: ges_blog-categories.php?erreur=suppression");
            exit;
        }
    }
}

if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["creer_categorie"] ?? "") === "1"
) {

    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Requête refusée : jeton de sécurité invalide.");
    }

    $nom = trim($_POST["nom"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if ($nom === "") {
        $erreur = "Le nom de la catégorie est obligatoire.";
    } elseif (mb_strlen($nom) > 100) {
        $erreur = "Le nom ne doit pas dépasser 100 caractères.";
    } else {
        // Génération du slug à partir du nom
        $slug = mb_strtolower($nom, "UTF-8");

$transliterator = Transliterator::create(
    "NFD; [:Nonspacing Mark:] Remove; NFC; Latin-ASCII"
);

if ($transliterator !== null) {
    $slug = $transliterator->transliterate($slug);
}

$slug = str_replace(["œ", "æ"], ["oe", "ae"], $slug);
$slug = strtolower($slug);
$slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
$slug = trim($slug, "-");

        if ($slug === "") {
            $erreur = "Impossible de générer un slug à partir de ce nom.";
        } else {
            try {
                // Vérifier si le slug existe déjà
                $verification = $pdo->prepare(
                    "SELECT id FROM categories WHERE slug = :slug LIMIT 1"
                );
                $verification->execute(["slug" => $slug]);

                if ($verification->fetch()) {
                    $erreur = "Une catégorie avec ce nom ou ce slug existe déjà.";
                } else {
                    $pdo->beginTransaction();

                    $sqlInsertion = "INSERT INTO categories (nom, slug, description)
                                     VALUES (:nom, :slug, :description)";
                    $insertion = $pdo->prepare($sqlInsertion);
                    $insertion->execute([
                        "nom" => $nom,
                        "slug" => $slug,
                        "description" => $description !== "" ? $description : null
                    ]);

                    $categorieId = (int) $pdo->lastInsertId();

                    enregistrerActivite(
                        $pdo,
                        "creation",
                        "Création de la catégorie : " . $nom,
                        "categorie",
                        $categorieId
                    );

                    $pdo->commit();

                    header("Location: ges_blog-categories.php?succes=creation");
                    exit;
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log("Création catégorie : " . $e->getMessage());
                $erreur = "Une erreur est survenue lors de l'enregistrement.";
            }
        }
    }
}




if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["modifier_categorie"] ?? "") === "1"
) {

      if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Requête refusée : jeton de sécurité invalide.");
    }

    $categorieId = filter_var(
        $_POST["categorie_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    $nom = trim($_POST["nom"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if (!$categorieId || $categorieId < 1) {
        $erreur = "Catégorie invalide.";
    } elseif ($nom === "") {
        $erreur = "Le nom de la catégorie est obligatoire.";
    } elseif (mb_strlen($nom) > 100) {
        $erreur = "Le nom ne doit pas dépasser 100 caractères.";
    } else {
        $slug = mb_strtolower($nom, "UTF-8");
$slug = str_replace(
    ["œ", "æ"],
    ["oe", "ae"],
    $slug
);

$transliterator = Transliterator::create(
    "NFD; [:Nonspacing Mark:] Remove; NFC; Latin-ASCII"
);

if ($transliterator !== null) {
    $slug = $transliterator->transliterate($slug);
}

$slug = str_replace(["œ", "æ"], ["oe", "ae"], $slug);
$slug = strtolower($slug);
$slug = preg_replace("/[^a-z0-9]+/", "-", $slug);
$slug = trim($slug, "-");

        if ($slug === "") {
            $erreur = "Impossible de générer un slug à partir de ce nom.";
        } else {
            try {
                $verification = $pdo->prepare(
                    "SELECT id FROM categories
                     WHERE slug = :slug AND id != :id
                     LIMIT 1"
                );
                $verification->execute([
                    "slug" => $slug,
                    "id" => $categorieId
                ]);

                if ($verification->fetch()) {
                    $erreur = "Une autre catégorie utilise déjà ce nom ou ce slug.";
                } else {
                    $pdo->beginTransaction();

                    $verificationCategorie = $pdo->prepare(
                        "SELECT nom FROM categories WHERE id = :id FOR UPDATE"
                    );
                    $verificationCategorie->execute(["id" => $categorieId]);
                    $ancienneCategorie = $verificationCategorie->fetch(PDO::FETCH_ASSOC);

                    if (!$ancienneCategorie) {
                        throw new RuntimeException("Catégorie introuvable.");
                    }

                    $sqlModification = "UPDATE categories
                                        SET nom = :nom,
                                            slug = :slug,
                                            description = :description
                                        WHERE id = :id";

                    $modification = $pdo->prepare($sqlModification);
                    $modification->execute([
                        "nom" => $nom,
                        "slug" => $slug,
                        "description" => $description !== "" ? $description : null,
                        "id" => $categorieId
                    ]);

                    enregistrerActivite(
                        $pdo,
                        "modification",
                        "Modification de la catégorie : "
                            . $ancienneCategorie["nom"] . " → " . $nom,
                        "categorie",
                        $categorieId
                    );

                    $pdo->commit();

                    header("Location: ges_blog-categories.php?succes=modification");
                    exit;
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log("Modification catégorie : " . $e->getMessage());
                $erreur = "Une erreur est survenue lors de la modification.";
            }
        }
    }
}


if (isset($_GET["succes"])) {
    switch ($_GET["succes"]) {
        case "creation":
            $message = "La catégorie a été créée avec succès.";
            break;

        case "modification":
            $message = "La catégorie a été modifiée avec succès.";
            break;

        case "suppression":
            $message = "La catégorie a été supprimée avec succès.";
            break;
    }
}

if (isset($_GET["erreur"])) {
    switch ($_GET["erreur"]) {
        case "suppression_articles":
            $erreur = "Suppression impossible : cette catégorie contient encore des articles. Réaffectez-les à une autre catégorie avant de la supprimer.";
            break;

        case "suppression":
            $erreur = "Une erreur est survenue lors de la suppression de la catégorie.";
            break;
    }
}

$sql = "SELECT
            c.id,
            c.nom,
            c.slug,
            c.description,
            c.created_at,
            COUNT(a.id) AS nombre_articles
        FROM categories AS c
        LEFT JOIN articles AS a ON a.categorie_id = c.id
        GROUP BY
            c.id,
            c.nom,
            c.slug,
            c.description,
            c.created_at
        ORDER BY c.nom ASC";

$stmt = $pdo->query($sql);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>





<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catégories du blog — AGZ Solutions</title>
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app-shell">
  <aside class="sidebar" id="agz-sidebar"></aside>

  <div>
    <header class="topbar" id="agz-topbar"></header>

    <main class="content">
      <div class="page-head">
        <div class="page-head-row">
          <div>
            <h1>Réglages du blog — Catégories</h1>
            <p>Gérez les catégories utilisées pour classer vos articles.</p>
          </div>
          <a href="#" class="btn btn-primary" id="newCatBtn">Ajouter une catégorie</a>
        </div>
      </div>


<?php if ($message !== ""): ?>
    <p id="successNotification" class="alert-success" role="status">
        <?= htmlspecialchars($message, ENT_QUOTES, "UTF-8") ?>
    </p>
<?php endif; ?>

<?php if ($erreur !== ""): ?>
    <p class="alert-error">
        <?= htmlspecialchars($erreur, ENT_QUOTES, "UTF-8") ?>
    </p>
<?php endif; ?>

      <div class="card">
        <div class="card-header">
          <div class="topbar-search" style="width:280px">
            <input
    type="text"
    id="searchCategory"
    placeholder="Rechercher une catégorie…"
    aria-label="Rechercher une catégorie"
>
        </div>
           <span class="muted" id="categoryCount"><?= count($categories) ?> catégorie(s)</span>

        <div class="table-wrap">
          <table class="data-table">
            <thead><tr><th>Nom</th><th>Slug</th><th>Articles</th><th>Statut</th><th>Actions</th></tr></thead>
            <tbody id="catBody"></tbody>
          </table>
        </div>

      </div>

      <!-- Fenêtre modale : ajout d'une catégorie -->
      <div id="catModal" class="modal" style="display:none;">
        <div class="modal-content">
          <h2 id="catModalTitle">Ajouter une catégorie</h2>

          <form id="catForm" method="POST" action="ges_blog-categories.php">
              <input type="hidden" name="creer_categorie" id="creerCategorie" value="1">
              <input type="hidden" name="modifier_categorie" id="modifierCategorie" value="0">
              <input type="hidden" name="categorie_id" id="categorieId" value="">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <div>
              <label for="catNom">Nom de la catégorie</label>
              <input
                type="text"
                id="catNom"
                name="nom"
                required
                maxlength="100"
                placeholder="Ex. : Technologie"
              >
            </div>

            <div>
              <label for="catDescription">Description (facultatif)</label>
              <textarea
                id="catDescription"
                name="description"
                rows="3"
                placeholder="Décrivez brièvement cette catégorie"
              ></textarea>
            </div>

            <div class="actions-cell">
              <button type="button" id="cancelCatBtn">Annuler</button>
              <button type="submit" class="btn btn-primary" id="saveCatBtn">Enregistrer</button>
            </div>

            <p id="catMessage" role="status"></p>
          </form>
        </div>
      </div>

<!-- Fenêtre modale : confirmation de suppression -->
<div id="deleteCatModal" class="modal" style="display:none;">
    <div class="modal-content">
        <h2>Supprimer la catégorie</h2>

        <p id="deleteCatMessage">
            Voulez-vous vraiment supprimer cette catégorie ?
        </p>

        <p class="muted">
            Cette action est définitive. Une catégorie contenant des articles ne pourra pas être supprimée.
        </p>

        <div class="actions-cell">
            <button type="button" class="btn btn-secondary" id="cancelDeleteCatBtn"> Annuler</button>

            <button type="button" class="btn btn-primary" id="confirmDeleteCatBtn">
                Supprimer
            </button>
        </div>
    </div>
</div>


    </main>

  </div>
</div>

<script src="assets/js/app.js"></script>
<script>
  AGZ.initLayout('categories', { searchPlaceholder:'Rechercher…' });


 const CATS = <?php echo json_encode($categories, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;


  document.addEventListener('DOMContentLoaded', () => {
    document.getElementById('newCatBtn').innerHTML = AGZ.icon('plus') + ' Ajouter une catégorie';
    const catModal = document.getElementById('catModal');
    const catForm = document.getElementById('catForm');
    const catMessage = document.getElementById('catMessage');

    const creerCategorie = document.getElementById('creerCategorie');
    const modifierCategorie = document.getElementById('modifierCategorie');
    const categorieId = document.getElementById('categorieId');
    const catNom = document.getElementById('catNom');
    const catDescription = document.getElementById('catDescription');
    const catModalTitle = document.getElementById('catModalTitle');
    const saveCatBtn = document.getElementById('saveCatBtn');



document.getElementById('newCatBtn').addEventListener('click', (event) => {
    event.preventDefault();

    catForm.reset();
    catMessage.textContent = '';

    creerCategorie.value = '1';
    modifierCategorie.value = '0';
    categorieId.value = '';

    catModalTitle.textContent = 'Ajouter une catégorie';
    saveCatBtn.textContent = 'Enregistrer';

    catModal.style.display = 'flex';
});


const successNotification = document.getElementById('successNotification');

if (successNotification) {
    // Retirer le paramètre de succès de l'URL
    const nouvelleURL = window.location.pathname + window.location.hash;
    window.history.replaceState({}, document.title, nouvelleURL);

    // Faire disparaître la notification après 5 secondes
    setTimeout(() => {
        successNotification.style.transition = 'opacity 0.4s ease';
        successNotification.style.opacity = '0';

        setTimeout(() => {
            successNotification.remove();
        }, 400);
    }, 5000);
}



document.getElementById('cancelCatBtn').addEventListener('click', () => {
    catModal.style.display = 'none';
    catForm.reset();
    catMessage.textContent = '';

    creerCategorie.value = '1';
    modifierCategorie.value = '0';
    categorieId.value = '';

    catModalTitle.textContent = 'Ajouter une catégorie';
    saveCatBtn.textContent = 'Enregistrer';
});


document.getElementById('catBody').innerHTML = CATS.length
    ? CATS.map(c => `
      <tr>
        <td class="cell-title">${c.nom}</td>
        <td>${c.slug}</td>
        <td>${c.nombre_articles}</td>
        <td><span class="badge badge-green">Actif</span></td>
        <td>
          <div class="actions-cell">
            <a class="icon-action edit-cat-btn" href="#" title="Modifier" data-id="${c.id}">${AGZ.icon('edit')}</a>
            <a class="icon-action danger delete-cat-btn" href="#" title="Supprimer" data-id="${c.id}">${AGZ.icon('trash')}</a>
          </div>
        </td>
      </tr>`).join('')
    : '<tr><td colspan="5" class="muted">Aucune catégorie enregistrée.</td></tr>';


document.getElementById('catBody').addEventListener('click', (event) => {
    const bouton = event.target.closest('.edit-cat-btn');

    if (!bouton) {
        return;
    }

    event.preventDefault();

    const id = Number(bouton.dataset.id);
    const categorie = CATS.find(c => Number(c.id) === id);

    if (!categorie) {
        return;
    }

    catForm.reset();
    catMessage.textContent = '';

    creerCategorie.value = '0';
    modifierCategorie.value = '1';
    categorieId.value = categorie.id;

    catNom.value = categorie.nom;
    catDescription.value = categorie.description || '';

    catModalTitle.textContent = 'Modifier une catégorie';
    saveCatBtn.textContent = 'Enregistrer les modifications';

    catModal.style.display = 'flex';
});

const deleteCatModal = document.getElementById('deleteCatModal');
const deleteCatMessage = document.getElementById('deleteCatMessage');
const cancelDeleteCatBtn = document.getElementById('cancelDeleteCatBtn');
const confirmDeleteCatBtn = document.getElementById('confirmDeleteCatBtn');

let categorieASupprimer = null;

document.getElementById('catBody').addEventListener('click', (event) => {
    const bouton = event.target.closest('.delete-cat-btn');

    if (!bouton) {
        return;
    }

    event.preventDefault();

    const id = bouton.dataset.id;
    const categorie = CATS.find(c => String(c.id) === String(id));

    if (!categorie) {
        return;
    }

    categorieASupprimer = categorie;

    deleteCatMessage.textContent =
        `Voulez-vous vraiment supprimer la catégorie « ${categorie.nom} » ?`;

    deleteCatModal.style.display = 'flex';
});

cancelDeleteCatBtn.addEventListener('click', () => {
    deleteCatModal.style.display = 'none';
    categorieASupprimer = null;
});

deleteCatModal.addEventListener('click', (event) => {
    if (event.target === deleteCatModal) {
        deleteCatModal.style.display = 'none';
        categorieASupprimer = null;
    }
});

confirmDeleteCatBtn.addEventListener('click', () => {
    if (!categorieASupprimer) {
        return;
    }

    const formulaire = document.createElement('form');
    formulaire.method = 'POST';
    formulaire.action = 'ges_blog-categories.php';

    const champAction = document.createElement('input');
    champAction.type = 'hidden';
    champAction.name = 'supprimer_categorie';
    champAction.value = '1';

    const champId = document.createElement('input');
    champId.type = 'hidden';
    champId.name = 'categorie_id';
    champId.value = categorieASupprimer.id;

    const champCsrf = document.createElement('input');
    champCsrf.type = 'hidden';
    champCsrf.name = 'csrf_token';
    champCsrf.value = <?= json_encode($csrfToken) ?>;

    formulaire.appendChild(champAction);
    formulaire.appendChild(champId);
    formulaire.appendChild(champCsrf);

    document.body.appendChild(formulaire);
    formulaire.submit();
});



  });
  const searchCategory = document.getElementById('searchCategory');

searchCategory.addEventListener('input', () => {
    const recherche = searchCategory.value
        .trim()
        .toLocaleLowerCase('fr');

    const categoriesFiltrees = CATS.filter(c =>
        c.nom.toLocaleLowerCase('fr').includes(recherche) ||
        c.slug.toLocaleLowerCase('fr').includes(recherche)
    );
    document.getElementById('categoryCount').textContent =
    `${categoriesFiltrees.length} catégorie(s)`;

    document.getElementById('catBody').innerHTML = categoriesFiltrees.length
        ? categoriesFiltrees.map(c => `
            <tr data-id="${c.id}">
                <td class="cell-title">${c.nom}</td>
                <td>${c.slug}</td>
                <td>${c.nombre_articles}</td>
                <td><span class="badge badge-green">Actif</span></td>
                <td>
                    <div class="actions-cell">
                        <a class="icon-action edit-cat-btn" href="#" title="Modifier" data-id="${c.id}">${AGZ.icon('edit')}</a>
                       <a class="icon-action danger delete-cat-btn" href="#" title="Supprimer" data-id="${c.id}">${AGZ.icon('trash')}</a>
                    </div>
                </td>
            </tr>
        `).join('')
        : '<tr><td colspan="5" class="muted">Aucune catégorie trouvée.</td></tr>';
});
</script>
</body>
</html>
