
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/activity.php";

// Vérifier l'identifiant de l'article
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: ges_articles.php?erreur=article_invalide");
    exit;
}

// Récupérer l'article
$stmt = $pdo->prepare(
    "SELECT id, titre, image_couverture
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

// Créer le jeton CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$erreur = null;

// Traiter la confirmation de suppression
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $jeton = $_POST["csrf_token"] ?? "";

    if (
        !is_string($jeton)
        || !hash_equals($_SESSION["csrf_token"], $jeton)
    ) {
        http_response_code(403);
        exit("Requête invalide. Rechargez la page et réessayez.");
    }

    $imageActuelle = $article["image_couverture"] ?? null;

    try {
        $pdo->beginTransaction();

        // Supprimer l'article ; les commentaires sont supprimés en CASCADE
        $stmt = $pdo->prepare(
            "DELETE FROM articles WHERE id = :id"
        );
        $stmt->execute(["id" => $id]);

        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(
                "L'article n'a pas pu être supprimé."
            );
        }

        // Journaliser l'action dans la même transaction
        enregistrerActivite(
            $pdo,
            "suppression",
            "Suppression de l'article : " . $article["titre"],
            "article",
            (int) $id
        );

        $pdo->commit();

        // Invalider le jeton après la suppression
        unset($_SESSION["csrf_token"]);

        // Supprimer l'image uniquement si elle appartient au dossier prévu
        $dossierImages = realpath(
            __DIR__ . "/uploads/articles"
        );

        if (
            $dossierImages !== false
            && is_string($imageActuelle)
            && $imageActuelle !== ""
        ) {
            $cheminImage = __DIR__ . "/" . ltrim(
                $imageActuelle,
                "/"
            );

            $fichierImage = realpath($cheminImage);

            if (
                $fichierImage !== false
                && is_file($fichierImage)
                && str_starts_with(
                    $fichierImage,
                    $dossierImages . DIRECTORY_SEPARATOR
                )
            ) {
                if (!unlink($fichierImage)) {
                    error_log(
                        "Article supprimé, mais impossible de supprimer l'image : "
                        . $fichierImage
                    );
                }
            }
        }

        header("Location: ges_articles.php?succes=article_supprime");
        exit;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        error_log(
            "Erreur lors de la suppression d'un article : "
            . $e->getMessage()
        );

        $erreur =
            "La suppression a échoué. L'article n'a pas été supprimé.";
    }
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmer la suppression | AGZ Solutions</title>
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        .delete-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 32px 20px;
            background: #f5f7fb;
        }

        .delete-card {
            width: 100%;
            max-width: 540px;
            padding: 38px;
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 18px;
            box-shadow: 0 15px 45px rgba(15, 23, 42, .08);
            text-align: center;
        }

        .delete-icon {
            width: 76px;
            height: 76px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 22px;
            color: #dc2626;
            background: #fef2f2;
            border: 1px solid #fee2e2;
            border-radius: 22px;
        }

        .delete-icon svg {
            width: 34px;
            height: 34px;
        }

        .delete-eyebrow {
            margin: 0 0 10px;
            color: #dc2626;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }

        .delete-card h1 {
            margin: 0 0 14px;
            color: #111827;
            font-size: 27px;
            line-height: 1.3;
        }

        .delete-description {
            margin: 0 auto 24px;
            max-width: 410px;
            color: #64748b;
            font-size: 15px;
            line-height: 1.7;
        }

        .delete-article-title {
            margin-bottom: 22px;
            padding: 16px;
            color: #1e293b;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-weight: 600;
            overflow-wrap: anywhere;
        }

        .delete-warning {
            display: flex;
            gap: 12px;
            margin-bottom: 28px;
            padding: 14px 16px;
            color: #92400e;
            background: #fffbeb;
            border: 1px solid #fde68a;
            border-radius: 10px;
            text-align: left;
            font-size: 13px;
            line-height: 1.6;
        }

        .delete-warning strong {
            display: block;
            margin-bottom: 2px;
        }

        .delete-actions {
            display: flex;
            justify-content: center;
            gap: 12px;
            flex-wrap: wrap;
        }

        .delete-btn {
            min-height: 46px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 9px;
            padding: 0 20px;
            border: 1px solid transparent;
            border-radius: 9px;
            font: inherit;
            font-size: 14px;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background .2s ease, transform .2s ease;
        }

        .delete-btn:hover {
            transform: translateY(-1px);
        }

        .delete-btn-cancel {
            color: #334155;
            background: #fff;
            border-color: #cbd5e1;
        }

        .delete-btn-cancel:hover {
            background: #f8fafc;
        }

        .delete-btn-confirm {
            color: #fff;
            background: #dc2626;
        }

        .delete-btn-confirm:hover {
            background: #b91c1c;
        }

        .delete-btn svg {
            width: 17px;
            height: 17px;
        }

        .delete-footer {
            margin: 24px 0 0;
            color: #94a3b8;
            font-size: 12px;
        }

        @media (max-width: 520px) {
            .delete-card {
                padding: 28px 20px;
            }

            .delete-card h1 {
                font-size: 23px;
            }

            .delete-actions {
                flex-direction: column-reverse;
            }

            .delete-btn {
                width: 100%;
                box-sizing: border-box;
            }
        }
    </style>
</head>
<body>
    <main class="delete-page">
        <section class="delete-card">

            <div class="delete-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 6h18"/>
                    <path d="M8 6V4h8v2"/>
                    <path d="m19 6-1 14H6L5 6"/>
                    <path d="M10 11v5M14 11v5"/>
                </svg>
            </div>

            <p class="delete-eyebrow">Action irréversible</p>
            <h1>Supprimer cet article ?</h1>

            <p class="delete-description">
                Vous êtes sur le point de supprimer définitivement
                cet article de votre blog AGZ Solutions.
            </p>

            <div class="delete-article-title">
                <?= htmlspecialchars(
                    $article["titre"],
                    ENT_QUOTES,
                    "UTF-8"
                ) ?>
            </div>

            <div class="delete-warning" role="note">
                <span aria-hidden="true">⚠</span>
                <div>
                    <strong>Attention, cette action est définitive.</strong>
                    L’image de couverture et les commentaires associés
                    seront également supprimés.
                </div>
            </div>

            <?php if ($erreur !== null): ?>
                <p role="alert" style="
                    margin-bottom: 20px;
                    padding: 12px;
                    color: #b91c1c;
                    background: #fef2f2;
                    border: 1px solid #fecaca;
                    border-radius: 8px;
                ">
                    <?= htmlspecialchars($erreur, ENT_QUOTES, "UTF-8") ?>
                </p>
            <?php endif; ?>

            <form method="POST">
                <input
                    type="hidden"
                    name="csrf_token"
                    value="<?= htmlspecialchars(
                        $_SESSION["csrf_token"],
                        ENT_QUOTES,
                        "UTF-8"
                    ) ?>"
                >

                <div class="delete-actions">
                    <a href="ges_articles.php" class="delete-btn delete-btn-cancel">
                        Annuler
                    </a>

                    <button type="submit" class="delete-btn delete-btn-confirm">
                        <svg viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="1.8"
                             stroke-linecap="round" stroke-linejoin="round"
                             aria-hidden="true">
                            <path d="M3 6h18"/>
                            <path d="M8 6V4h8v2"/>
                            <path d="m19 6-1 14H6L5 6"/>
                        </svg>
                        Confirmer la suppression
                    </button>
                </div>
            </form>

            <p class="delete-footer">
                AGZ Solutions · Gestion du contenu
            </p>
        </section>
    </main>
</body>
</html>
