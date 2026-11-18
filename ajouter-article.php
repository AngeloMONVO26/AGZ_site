
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/activity.php";

// Création du jeton CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// Récupération des catégories
$stmt = $pdo->query(
    "SELECT id, nom FROM categories ORDER BY nom ASC"
);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$erreurs = [];
$titre = "";
$extrait = "";
$contenu = "";
$categorie_id = "";
$statut = "brouillon";

// Traitement du formulaire
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
    $statut = $_POST["statut"] ?? "brouillon";

    // Validation des champs
    if ($titre === "") {
        $erreurs[] = "Le titre est obligatoire.";
    } elseif (mb_strlen($titre) > 255) {
        $erreurs[] = "Le titre ne doit pas dépasser 255 caractères.";
    }

    if ($contenu === "") {
        $erreurs[] = "Le contenu de l'article est obligatoire.";
    }

    if (!in_array($statut, ["brouillon", "publie"], true)) {
        $erreurs[] = "Le statut sélectionné est invalide.";
    }

    if (
        $categorie_id !== ""
        && (
            !is_string($categorie_id)
            || !ctype_digit($categorie_id)
        )
    ) {
        $erreurs[] = "La catégorie sélectionnée est invalide.";
    }

    if ($categorie_id !== "" && ctype_digit((string) $categorie_id)) {
        $verification = $pdo->prepare(
            "SELECT COUNT(*) FROM categories WHERE id = :id"
        );
        $verification->execute(["id" => (int) $categorie_id]);

        if ((int) $verification->fetchColumn() === 0) {
            $erreurs[] = "La catégorie sélectionnée n'existe pas.";
        }
    }


    // Validation de l'image de couverture

$imageCouverture = null;
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

    // Enregistrement de l'article
    if (empty($erreurs)) {
        $slugBase = iconv("UTF-8", "ASCII//TRANSLIT//IGNORE", $titre);
        $slugBase = strtolower($slugBase);
        $slugBase = preg_replace("/[^a-z0-9]+/", "-", $slugBase);
        $slugBase = trim($slugBase, "-");

        if ($slugBase === "") {
            $slugBase = "article";
        }

        $slug = $slugBase;
        $suffixe = 2;

        $verificationSlug = $pdo->prepare(
            "SELECT COUNT(*) FROM articles WHERE slug = :slug"
        );

        while (true) {
            $verificationSlug->execute(["slug" => $slug]);

            if ((int) $verificationSlug->fetchColumn() === 0) {
                break;
            }

            $slug = $slugBase . "-" . $suffixe;
            $suffixe++;
        }

        $datePublication = $statut === "publie" ? date("Y-m-d H:i:s") : null;

        // Préparation de l'image de couverture
$cheminImage = null;

if ($imageTemporaire !== null && $extensionImage !== null) {
    $dossierImages = __DIR__ . "/uploads/articles/";

    $nomImage = bin2hex(random_bytes(16)) . "." . $extensionImage;

    $cheminImage = "uploads/articles/" . $nomImage;
}

        try {
            $pdo->beginTransaction();

            // Enregistrement physique de l'image
if ($imageTemporaire !== null && $cheminImage !== null) {
    $cheminAbsolu = __DIR__ . "/" . $cheminImage;

    if (!move_uploaded_file($imageTemporaire, $cheminAbsolu)) {
        throw new RuntimeException(
            "Impossible d'enregistrer l'image de couverture."
        );
    }
}

            $sql = "INSERT INTO articles (
            categorie_id,
            admin_id,
            titre,
            slug,
            extrait,
            contenu,
            image_couverture,
            statut,
            date_publication
        ) VALUES (
            :categorie_id,
            :admin_id,
            :titre,
            :slug,
            :extrait,
            :contenu,
            :image_couverture,
            :statut,
            :date_publication
        )";

            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                "categorie_id" => $categorie_id === "" ? null : (int) $categorie_id,
                "admin_id" => (int) $_SESSION["admin_id"],
                "titre" => $titre,
                "slug" => $slug,
                "extrait" => $extrait === "" ? null : $extrait,
                "contenu" => $contenu,
                "image_couverture" => $cheminImage,
                "statut" => $statut,
                "date_publication" => $datePublication
            ]);

            $articleId = (int) $pdo->lastInsertId();

            enregistrerActivite(
                $pdo,
                "creation",
                "Création de l'article : " . $titre,
                "article",
                $articleId
            );

            $pdo->commit();

            header("Location: ges_articles.php?succes=article_cree");
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Supprimer l'image si l'enregistrement de l'article échoue
             if ($cheminImage !== null) {
            $cheminAbsolu = __DIR__ . "/" . $cheminImage;

           if (is_file($cheminAbsolu)) {
                unlink($cheminAbsolu);
               }
            }

            error_log("Erreur lors de la création d'un article : " . $e->getMessage());
            $erreurs[] = "Une erreur est survenue lors de l'enregistrement de l'article.";
        }
    }
}
?>



<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvel article — AGZ Solutions</title>
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
                        <h1>Nouvel article</h1>
                        <p>Rédigez et gérez votre publication.</p>
                    </div>
                    <a href="ges_articles.php" class="btn btn-secondary">
                        Annuler
                    </a>
                </div>
            </div>

            <?php if (!empty($erreurs)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur, ENT_QUOTES, 'UTF-8') ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" action="ajouter-article.php" enctype="multipart/form-data">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>"
                >

                <div class="card">
                    <div class="card-header">
                        <h2>Informations de l'article</h2>
                    </div>

                    <div class="form-group">
                        <label for="titre">Titre de l'article *</label>
                        <input
                            type="text"
                            id="titre"
                            name="titre"
                            maxlength="255"
                            required
                            value="<?= htmlspecialchars($titre, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex. : Pourquoi automatiser ses processus ?"
                        >
                    </div>

                    <div class="form-group">
                        <label for="categorie_id">Catégorie</label>
                        <select id="categorie_id" name="categorie_id">
                            <option value="">Sans catégorie</option>
                            <?php foreach ($categories as $categorie): ?>
                                <option
                                    value="<?= (int) $categorie['id'] ?>"
                                    <?= (string) $categorie_id === (string) $categorie['id'] ? 'selected' : '' ?>
                                >
                                    <?= htmlspecialchars($categorie['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="extrait">Extrait</label>
                        <textarea
                            id="extrait"
                            name="extrait"
                            rows="3"
                            placeholder="Résumé court de l'article..."
                        ><?= htmlspecialchars($extrait, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="image_couverture">Image de couverture</label>
                        <input
                            type="file"
                            id="image_couverture"
                            name="image_couverture"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <small class="muted">
                            Formats acceptés : JPG, PNG et WebP. La taille maximale sera définie côté serveur.
                        </small>
                    </div>


                    <div class="form-group">
                        <label for="contenu">Contenu de l'article *</label>
                        <textarea
                            id="contenu"
                            name="contenu"
                            rows="12"
                            required
                            placeholder="Rédigez le contenu de votre article..."
                        ><?= htmlspecialchars($contenu, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="statut">Statut</label>
                        <select id="statut" name="statut">
                            <option value="brouillon" <?= $statut === 'brouillon' ? 'selected' : '' ?>>
                                Brouillon
                            </option>
                            <option value="publie" <?= $statut === 'publie' ? 'selected' : '' ?>>
                                Publier
                            </option>
                        </select>
                    </div>

                    <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:24px">
                        <a href="ges_articles.php" class="btn btn-secondary">Annuler</a>
                        <button type="submit" class="btn btn-primary">
                            Enregistrer l'article
                        </button>
                    </div>
                </div>
            </form>
        </main>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
    AGZ.initLayout('articles', { searchPlaceholder: 'Rechercher…' });
</script>
</body>
</html>
