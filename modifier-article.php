<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/activity.php";

// Vérification de l'identifiant de l'article
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: ges_articles.php?erreur=article_invalide");
    exit;
}

// Récupération de l'article
$stmt = $pdo->prepare(
    "SELECT *
     FROM articles
     WHERE id = :id
     LIMIT 1"
);

$stmt->execute(["id" => $id]);
$article = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$article) {
    header("Location: ges_articles.php?erreur=article_introuvable");
    exit;
}

// Préparation des données du formulaire
$titre = $article["titre"];
$extrait = $article["extrait"] ?? "";
$contenu = $article["contenu"];
$categorie_id = $article["categorie_id"] ?? "";
$statut = $article["statut"];
$imageActuelle = $article["image_couverture"] ?? null;

// Création du jeton CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// Récupération des catégories
$stmt = $pdo->query(
    "SELECT id, nom
     FROM categories
     ORDER BY nom ASC"
);

$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erreurs = [];

// Traitement du formulaire de modification
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Vérification du jeton CSRF
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !isset($_SESSION["csrf_token"])
        || !is_string($_SESSION["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Requête refusée : jeton de sécurité invalide.");
    }

    $titre = trim($_POST["titre"] ?? "");
    $extrait = trim($_POST["extrait"] ?? "");
    $contenu = trim($_POST["contenu"] ?? "");
    $categorie_id = $_POST["categorie_id"] ?? "";
    $statut = $_POST["statut"] ?? "";

    // Validation du titre
    if ($titre === "") {
        $erreurs[] = "Le titre est obligatoire.";
    } elseif (mb_strlen($titre) > 255) {
        $erreurs[] = "Le titre ne doit pas dépasser 255 caractères.";
    }

    // Validation du contenu
    if ($contenu === "") {
        $erreurs[] = "Le contenu de l'article est obligatoire.";
    }

    // Validation du statut
    if (!in_array($statut, ["brouillon", "publie", "archive"], true)) {
        $erreurs[] = "Le statut sélectionné est invalide.";
    }

    // Validation de la catégorie
    if (
        $categorie_id !== ""
        && (
            !is_string($categorie_id)
            || !ctype_digit($categorie_id)
        )
    ) {
        $erreurs[] = "La catégorie sélectionnée est invalide.";
    }

    if (
        $categorie_id !== ""
        && is_string($categorie_id)
        && ctype_digit($categorie_id)
    ) {
        $verification = $pdo->prepare(
            "SELECT COUNT(*) FROM categories WHERE id = :id"
        );

        $verification->execute([
            "id" => (int) $categorie_id
        ]);

        if ((int) $verification->fetchColumn() === 0) {
            $erreurs[] = "La catégorie sélectionnée n'existe pas.";
        }
       }


    // Validation de la nouvelle image de couverture
    $imageTemporaire = null;
    $extensionImage = null;

    if (
        isset($_FILES["image_couverture"])
        && $_FILES["image_couverture"]["error"] !== UPLOAD_ERR_NO_FILE
    ) {
        $fichier = $_FILES["image_couverture"];
        $tailleMaximale = 2 * 1024 * 1024; // 2 Mo

        if ($fichier["error"] !== UPLOAD_ERR_OK) {
            $erreurs[] = "Une erreur est survenue lors du transfert de l'image.";
        } elseif ($fichier["size"] > $tailleMaximale) {
            $erreurs[] = "L'image ne doit pas dépasser 2 Mo.";
        } elseif (!is_uploaded_file($fichier["tmp_name"])) {
            $erreurs[] = "Le fichier envoyé n'est pas valide.";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $typeMime = $finfo->file($fichier["tmp_name"]);

            $typesAutorises = [
                "image/jpeg" => "jpg",
                "image/png"  => "png",
                "image/webp" => "webp"
            ];

            if (!isset($typesAutorises[$typeMime])) {
                $erreurs[] = "Format non autorisé. Utilisez une image JPG, PNG ou WebP.";
            } else {
                $imageTemporaire = $fichier["tmp_name"];
                $extensionImage = $typesAutorises[$typeMime];
            }
        }
    }


    // Enregistrement des modifications si aucune erreur
    if (empty($erreurs)) {
    $categorieId = $categorie_id === ""
        ? null
        : (int) $categorie_id;

    // Gestion de la date de publication
$datePublication = $article["date_publication"];

if (
    $statut === "publie"
    && (
        $article["statut"] !== "publie"
        || empty($datePublication)
    )
) {
    $datePublication = date("Y-m-d H:i:s");
}

    $imageCouverture = $imageActuelle;
    $imageNouvelle = null;
    $cheminImage = null;

    try {
        // Préparer et enregistrer la nouvelle image, si nécessaire
        if ($imageTemporaire !== null) {
            $nomImage = bin2hex(random_bytes(16))
                . "."
                . $extensionImage;

            $cheminImage = "uploads/articles/" . $nomImage;
            $cheminAbsolu = __DIR__ . "/" . $cheminImage;

            if (!move_uploaded_file(
                $imageTemporaire,
                $cheminAbsolu
            )) {
                throw new RuntimeException(
                    "Impossible d'enregistrer la nouvelle image."
                );
            }

            $imageNouvelle = $cheminAbsolu;
            $imageCouverture = $cheminImage;
        }

        // Démarrer la transaction SQL
        $pdo->beginTransaction();

        // Modifier l'article, y compris son image de couverture
        $stmt = $pdo->prepare(
            "UPDATE articles
             SET titre = :titre,
                 extrait = :extrait,
                 contenu = :contenu,
                 categorie_id = :categorie_id,
                 statut = :statut,
                 date_publication = :date_publication,
                 image_couverture = :image_couverture,
                 updated_at = NOW()
             WHERE id = :id"
        );

        $stmt->execute([
            "titre" => $titre,
            "extrait" => $extrait,
            "contenu" => $contenu,
            "categorie_id" => $categorieId,
            "statut" => $statut,
            "date_publication" => $datePublication,
            "image_couverture" => $imageCouverture,
            "id" => $id
        ]);

        // Enregistrer l'activité administrative
        enregistrerActivite(
            $pdo,
            "modification",
            "Modification de l'article : " . $titre,
            "article",
            (int) $id
        );

        // Confirmer la modification en base de données
        $pdo->commit();

    } catch (Throwable $e) {
        // Annuler les changements SQL en cas d'erreur
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        // Supprimer uniquement la nouvelle image créée
        if (
            $imageNouvelle !== null
            && is_file($imageNouvelle)
        ) {
            unlink($imageNouvelle);
        }

        error_log(
            "Erreur lors de la modification de l'article : "
            . $e->getMessage()
        );

        $erreurs[] =
            "Une erreur est survenue lors de la modification de l'article.";

    }

    // Supprimer l'ancienne image seulement après validation SQL
    if (empty($erreurs) && $imageNouvelle !== null) {
        $ancienDossier = realpath(
            __DIR__ . "/uploads/articles"
        );

        $ancienFichier = $imageActuelle !== null
            ? realpath(__DIR__ . "/" . $imageActuelle)
            : false;

        if (
            $ancienDossier !== false
            && $ancienFichier !== false
            && is_file($ancienFichier)
            && str_starts_with(
                $ancienFichier,
                $ancienDossier . DIRECTORY_SEPARATOR
            )
        ) {
            unlink($ancienFichier);
        }
    }

    // Rediriger seulement si l'enregistrement a réussi
    if (empty($erreurs)) {
        header("Location: ges_articles.php?succes=article_modifie");
        exit;
    }
}
}

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un article - AGZ Dashboard</title>
    <link rel="stylesheet" href="assets/css/modifier-article.css">
</head>
<body>

    <main class="article-edit-page">
        <div class="article-edit-container">

            <header class="article-edit-header">
                <div>
                    <h1>Modifier l'article</h1>
                    <p>Mettez à jour le contenu et les paramètres de votre article.</p>
                </div>
            </header>

            <?php if (!empty($erreurs)): ?>
                <div class="article-edit-errors">
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <section class="article-edit-card">
                <form method="POST" enctype="multipart/form-data"
                      class="article-edit-form">

                    <input type="hidden" name="csrf_token"
                           value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

                    <div class="article-edit-field">
                        <label for="titre">Titre de l'article *</label>
                        <input type="text" id="titre" name="titre"
                               maxlength="255" required
                               value="<?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="article-edit-field">
                        <label for="extrait">Extrait</label>
                        <textarea id="extrait" name="extrait" rows="4"><?= htmlspecialchars($extrait, ENT_QUOTES, 'UTF-8') ?></textarea>
                        <p class="article-edit-help">Courte présentation destinée à résumer l'article.</p>
                    </div>

                    <div class="article-edit-field">
                        <label for="contenu">Contenu de l'article *</label>
                        <textarea id="contenu" name="contenu" rows="12" required><?= htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="article-edit-field">
                        <label for="categorie_id">Catégorie</label>
                        <select id="categorie_id" name="categorie_id">
                            <option value="">Sans catégorie</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option value="<?= (int) $categorie['id'] ?>"
                                    <?= (string) $categorie_id === (string) $categorie['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categorie['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="article-edit-field">
                        <label for="statut">Statut de publication *</label>
                        <select id="statut" name="statut" required>
                            <option value="brouillon" <?= $statut === 'brouillon' ? 'selected' : '' ?>>Brouillon</option>
                            <option value="publie" <?= $statut === 'publie' ? 'selected' : '' ?>>Publié</option>
                            <option value="archive" <?= $statut === 'archive' ? 'selected' : '' ?>>Archivé</option>
                        </select>
                    </div>

                    <div class="article-edit-field">
                        <label for="image_couverture">Image de couverture</label>
                        <input type="file" id="image_couverture"
                               name="image_couverture"
                               accept=".jpg,.jpeg,.png,.webp">
                        <p class="article-edit-help">
                            Formats acceptés : JPG, PNG ou WebP. Taille maximale : 2 Mo.
                        </p>

                        <?php if (!empty($imageActuelle)): ?>
                            <p class="article-edit-help">Image actuelle</p>
                            <img
                                class="article-edit-image"
                                src="<?= htmlspecialchars($imageActuelle, ENT_QUOTES, 'UTF-8') ?>"
                                alt="Image de couverture actuelle">
                        <?php endif; ?>
                    </div>

                    <div class="article-edit-actions">
                        <button type="submit"
                                class="article-edit-btn article-edit-btn-primary">
                            Enregistrer les modifications
                        </button>

                        <a href="ges_articles.php"
                           class="article-edit-btn article-edit-btn-secondary">
                            Annuler
                        </a>
                    </div>

                </form>
            </section>

        </div>
    </main>

</body>
</html>
