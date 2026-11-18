
<?php
require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/activity.php";

/* Protection CSRF */
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];
$message = "";
$erreur = "";

/* Traitement : archivage ou restauration */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $token = $_POST["csrf_token"] ?? "";

    if (!is_string($token) || !hash_equals($csrfToken, $token)) {
        $erreur = "La session de sécurité a expiré. Rechargez la page.";
    } else {
        $commentaireId = filter_var(
            $_POST["commentaire_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        $action = $_POST["action"] ?? "";

        if (!$commentaireId || $commentaireId < 1) {
            $erreur = "Identifiant de commentaire invalide.";
        } elseif (!in_array($action, ["archiver", "restaurer"], true)) {
            $erreur = "Action non autorisée.";
        } else {
            try {
                $pdo->beginTransaction();

                $requete = $pdo->prepare(
                    "SELECT id, nom, statut
                     FROM commentaires
                     WHERE id = :id
                     FOR UPDATE"
                );

                $requete->execute(["id" => $commentaireId]);
                $commentaire = $requete->fetch(PDO::FETCH_ASSOC);

                if (!$commentaire) {
                    throw new RuntimeException("Commentaire introuvable.");
                }

                if ($action === "archiver") {
                    if ($commentaire["statut"] !== "approuve") {
                        throw new RuntimeException(
                            "Seuls les commentaires publiés peuvent être archivés."
                        );
                    }

                    $nouveauStatut = "archive";
                    $actionJournal = "archivage";
                    $description = "Archivage du commentaire de "
                        . $commentaire["nom"];
                    $messageSucces = "Le commentaire a été archivé.";
                } else {
                    if ($commentaire["statut"] !== "archive") {
                        throw new RuntimeException(
                            "Ce commentaire n'est pas archivé."
                        );
                    }

                    $nouveauStatut = "approuve";
                    $actionJournal = "restauration";
                    $description = "Restauration du commentaire de "
                        . $commentaire["nom"];
                    $messageSucces = "Le commentaire a été restauré et republié.";
                }

                $miseAJour = $pdo->prepare(
                    "UPDATE commentaires
                     SET statut = :statut
                     WHERE id = :id"
                );

                $miseAJour->execute([
                    "statut" => $nouveauStatut,
                    "id" => $commentaireId
                ]);

                enregistrerActivite(
                    $pdo,
                    $actionJournal,
                    $description,
                    "commentaire",
                    $commentaireId
                );

                $pdo->commit();

                header(
                    "Location: ges_commentaires.php?succes="
                    . ($action === "archiver" ? "archivage" : "restauration")
                );
                exit;

            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log("Gestion des commentaires : " . $e->getMessage());

                $erreur = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Une erreur est survenue. Veuillez réessayer.";
            }
        }
    }
}

/* Notifications après redirection */
if (isset($_GET["succes"])) {
    if ($_GET["succes"] === "archivage") {
        $message = "Le commentaire a été archivé avec succès.";
    } elseif ($_GET["succes"] === "restauration") {
        $message = "Le commentaire a été restauré avec succès.";
    }
}

/* Récupération des commentaires */
$sql = "SELECT
            c.id,
            c.nom,
            c.email,
            c.contenu,
            c.statut,
            c.date_commentaire,
            a.titre AS article
        FROM commentaires AS c
        LEFT JOIN articles AS a ON a.id = c.article_id
        ORDER BY c.date_commentaire DESC, c.id DESC";

$stmt = $pdo->query($sql);
$commentaires = $stmt->fetchAll(PDO::FETCH_ASSOC);

function echapper($valeur): string
{
    return htmlspecialchars(
        (string) ($valeur ?? ""),
        ENT_QUOTES,
        "UTF-8"
    );
}

function libelleStatut(string $statut): string
{
    return match ($statut) {
        "approuve" => "Publié",
        "archive" => "Archivé",
        "en_attente" => "En attente",
        "rejete" => "Rejeté",
        default => "Inconnu"
    };
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des commentaires — AGZ Solutions</title>
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
                        <h1>Gestion des commentaires</h1>
                        <p>Consultez, archivez et restaurez les commentaires du blog.</p>
                    </div>
                </div>
            </div>

            <?php if ($message !== ""): ?>
                <p class="alert-success" role="status">
                    <?= echapper($message) ?>
                </p>
            <?php endif; ?>

            <?php if ($erreur !== ""): ?>
                <p class="alert-error" role="alert">
                    <?= echapper($erreur) ?>
                </p>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="topbar-search" style="width:280px">
                        <input
                            type="text"
                            id="searchCommentaire"
                            placeholder="Rechercher un commentaire…"
                            aria-label="Rechercher un commentaire"
                        >
                    </div>
                    <span class="muted" id="commentaireCount">
                        <?= count($commentaires) ?> commentaire(s)
                    </span>
                </div>

                <div class="table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Auteur</th>
                                <th>Commentaire</th>
                                <th>Article</th>
                                <th>Date</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="commentairesBody">
                        <?php foreach ($commentaires as $commentaire): ?>
                            <tr>
                                <td>
                                    <strong><?= echapper($commentaire["nom"]) ?></strong>
                                    <br>
                                    <small><?= echapper($commentaire["email"]) ?></small>
                                </td>
                                <td class="commentaire-contenu">
                                    <?= nl2br(echapper($commentaire["contenu"])) ?>
                                </td>
                                <td><?= echapper($commentaire["article"] ?? "Article introuvable") ?></td>
                                <td>
                                    <?= date(
                                        "d/m/Y H:i",
                                        strtotime($commentaire["date_commentaire"])
                                    ) ?>
                                </td>
                                <td>
                                    <span class="badge-statut">
                                        <?= echapper(libelleStatut($commentaire["statut"])) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($commentaire["statut"] === "approuve"): ?>
                                        <form method="POST" action="ges_commentaires.php"
                                              onsubmit="return confirm('Archiver ce commentaire ? Il ne sera plus visible sur le site public.');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= echapper($csrfToken) ?>">
                                            <input type="hidden" name="commentaire_id"
                                                   value="<?= (int) $commentaire["id"] ?>">
                                            <input type="hidden" name="action" value="archiver">
                                            <button type="submit" class="btn btn-secondary">
                                                Archiver
                                            </button>
                                        </form>
                                    <?php elseif ($commentaire["statut"] === "archive"): ?>
                                        <form method="POST" action="ges_commentaires.php"
                                              onsubmit="return confirm('Restaurer et republier ce commentaire ?');">
                                            <input type="hidden" name="csrf_token"
                                                   value="<?= echapper($csrfToken) ?>">
                                            <input type="hidden" name="commentaire_id"
                                                   value="<?= (int) $commentaire["id"] ?>">
                                            <input type="hidden" name="action" value="restaurer">
                                            <button type="submit" class="btn btn-primary">
                                                Restaurer
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="muted">Aucune action</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                        <?php if (count($commentaires) === 0): ?>
                            <tr>
                                <td colspan="6">Aucun commentaire pour le moment.</td>
                            </tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<script src="assets/js/app.js"></script>
<script>
AGZ.initLayout('commentaires', {
    searchPlaceholder: 'Rechercher un commentaire…'
});

document.addEventListener('DOMContentLoaded', () => {
    const recherche = document.getElementById('searchCommentaire');
    const lignes = Array.from(
        document.querySelectorAll('#commentairesBody tr')
    );
    const compteur = document.getElementById('commentaireCount');

    recherche.addEventListener('input', () => {
        const terme = recherche.value.trim().toLocaleLowerCase('fr');
        let visibles = 0;

        lignes.forEach((ligne) => {
            const correspond = ligne.textContent
                .toLocaleLowerCase('fr')
                .includes(terme);

            ligne.hidden = !correspond;

            if (correspond) visibles++;
        });

        compteur.textContent = visibles + ' commentaire(s)';
    });
});
</script>
</body>
</html>
