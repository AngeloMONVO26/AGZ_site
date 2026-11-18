<?php
require_once "auth.php";
require_once "db.php";
require_once "activity.php";

// Initialisation du jeton CSRF
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrfToken = $_SESSION["csrf_token"];

// Marquer un message comme lu
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["marquer_lu"])) {

    $estAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    $messageId = filter_input(INPUT_POST, "message_id", FILTER_VALIDATE_INT);

    // Vérification du jeton CSRF
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Jeton de sécurité invalide.'
            ]);
            exit;
        }

        exit("Requête refusée : jeton de sécurité invalide.");
    }

    if ($messageId) {

        $stmt = $pdo->prepare("
            UPDATE messages
            SET statut = 'lu'
            WHERE id = :id AND statut = 'nouveau'
        ");

        $stmt->execute(["id" => $messageId]);

        if ($stmt->rowCount() > 0) {
            enregistrerActivite(
                $pdo,
                "lecture_message",
                "Consultation du message n°" . $messageId,
                "messages",
                $messageId
            );
        }

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => true,
                'id' => $messageId,
                'statut' => 'lu'
            ]);
            exit;
        }

        header("Location: ges_messages.php?ouvrir=" . $messageId);
        exit;
    }
}

// Mettre un message en attente

// Mettre un message en attente
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["mettre_en_attente"])) {

    $estAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    $messageId = filter_input(INPUT_POST, "message_id", FILTER_VALIDATE_INT);

    // Vérification du jeton CSRF
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Jeton de sécurité invalide.'
            ]);
            exit;
        }

        exit("Requête refusée : jeton de sécurité invalide.");
    }

    if ($messageId) {

        $stmt = $pdo->prepare("
            UPDATE messages
            SET statut = 'en_attente'
            WHERE id = :id AND statut = 'lu'
        ");

        $stmt->execute(["id" => $messageId]);

        if ($stmt->rowCount() > 0) {
            enregistrerActivite(
                $pdo,
                "mise_en_attente_message",
                "Message n°" . $messageId . " mis en attente",
                "messages",
                $messageId
            );
        }

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => true,
                'id' => $messageId,
                'statut' => 'en_attente'
            ]);
            exit;
        }

        header("Location: ges_messages.php?ouvrir=" . $messageId);
        exit;
    }
}


// Marquer un message comme traité

// Marquer un message comme traité
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["marquer_traite"])) {

    $estAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    $messageId = filter_input(INPUT_POST, "message_id", FILTER_VALIDATE_INT);

    // Vérification du jeton CSRF
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Jeton de sécurité invalide.'
            ]);
            exit;
        }

        exit("Requête refusée : jeton de sécurité invalide.");
    }

    if ($messageId) {

        $stmt = $pdo->prepare("
            UPDATE messages
            SET statut = 'traite'
            WHERE id = :id AND statut IN ('lu', 'en_attente')
        ");

        $stmt->execute(["id" => $messageId]);

        if ($stmt->rowCount() > 0) {
            enregistrerActivite(
                $pdo,
                "traitement_message",
                "Message n°" . $messageId . " marqué comme traité",
                "messages",
                $messageId
            );
        }

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => true,
                'id' => $messageId,
                'statut' => 'traite'
            ]);
            exit;
        }

        header("Location: ges_messages.php?ouvrir=" . $messageId);
        exit;
    }
}



// Archiver un message traité
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["archiver_message"])) {

    $estAjax = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';

    $messageId = filter_input(INPUT_POST, "message_id", FILTER_VALIDATE_INT);

    // Vérification du jeton CSRF
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'message' => 'Jeton de sécurité invalide.'
            ]);
            exit;
        }

        exit("Requête refusée : jeton de sécurité invalide.");
    }

    if ($messageId) {

        $stmt = $pdo->prepare("
            UPDATE messages
            SET statut = 'archive'
            WHERE id = :id AND statut = 'traite'
        ");

        $stmt->execute(["id" => $messageId]);

        if ($stmt->rowCount() > 0) {
            enregistrerActivite(
                $pdo,
                "archivage_message",
                "Message n°" . $messageId . " archivé",
                "messages",
                $messageId
            );
        }

        if ($estAjax) {
            header('Content-Type: application/json; charset=utf-8');

            echo json_encode([
                'success' => true,
                'id' => $messageId,
                'statut' => 'archive'
            ]);
            exit;
        }

        header("Location: ges_messages.php?onglet=traite");
        exit;
    }
}



 // Supprimer définitivement un message archivé
if (
    $_SERVER["REQUEST_METHOD"] === "POST"
    && ($_POST["supprimer_message"] ?? "") === "1"
) {

    // Vérification du jeton CSRF
    if (
        !isset($_POST["csrf_token"])
        || !is_string($_POST["csrf_token"])
        || !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        http_response_code(403);
        exit("Requête refusée : jeton de sécurité invalide.");
    }

    $messageId = filter_input(INPUT_POST, "message_id", FILTER_VALIDATE_INT);

    if ($messageId) {

        // Suppression autorisée uniquement pour un message archivé
        $stmt = $pdo->prepare("
            DELETE FROM messages
            WHERE id = :id AND statut = 'archive'
        ");

        $stmt->execute(["id" => $messageId]);

        if ($stmt->rowCount() > 0) {
            enregistrerActivite(
                $pdo,
                "suppression_message",
                "Message n°" . $messageId . " supprimé définitivement",
                "messages",
                $messageId
            );
        }
    }

      header("Location: ges_messages.php?onglet=archive");
      exit;
}



// Récupération des statistiques des messages
// Récupération des statistiques des messages
$stmt = $pdo->query("
    SELECT
    COUNT(*) AS total,
    SUM(statut = 'nouveau') AS nouveaux,
    SUM(statut = 'lu') AS lus,
    SUM(statut = 'en_attente') AS en_attente,
    SUM(statut = 'traite') AS traites,
    SUM(statut = 'archive') AS archives
FROM messages
");

$stats = $stmt->fetch(PDO::FETCH_ASSOC);

$totalMessages = (int) ($stats['total'] ?? 0);
$nouveauxMessages = (int) ($stats['nouveaux'] ?? 0);
$messagesLus = (int) ($stats['lus'] ?? 0);
$messagesEnAttente = (int) ($stats['en_attente'] ?? 0);
$messagesTraites = (int) ($stats['traites'] ?? 0);
$messagesArchives = (int) ($stats['archives'] ?? 0);

// Récupération de la liste des messages
$stmt = $pdo->query("
    SELECT
        id,
        nom,
        email,
        telephone,
        sujet,
        message,
        statut,
        date_reception
    FROM messages
    ORDER BY date_reception DESC
");

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>




<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des messages — AGZ Solutions</title>
    <script src="https://unpkg.com/lucide@latest"></script>

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
                        <h1>Gestion des messages</h1>
                        <p>Consultez et gérez les messages reçus depuis votre site web.</p>
                    </div>
                </div>
            </div>

            <div class="modal" id="modal-message">

                <div class="modal-content">

                    <div class="card-header"><h2>Détail du message</h2> <button type="button" id="fermer-modal" class="btn" aria-label="Fermer">×</button></div>
                    <div class="message-details">
                        <p><strong>Expéditeur :</strong> <span id="detail-nom"></span></p>

                        <p><strong>Email :</strong> <span id="detail-email"></span></p>

                        <p><strong>Téléphone :</strong> <span id="detail-telephone"></span></p>

                        <p><strong>Sujet :</strong> <span id="detail-sujet"></span></p>

                        <p><strong>Date de réception :</strong> <span id="detail-date"></span></p>
                        <div> <strong>Message :</strong> <p id="detail-message"></p></div>

                        <div class="message-actions">

                            <form method="POST" action="ges_messages.php" id="form-attente">
                                <input type="hidden" name="message_id" id="attente-message-id">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="mettre_en_attente" value="1"
                                        class="btn"
                                        title="Mettre en attente"
                                        aria-label="Mettre en attente">
                                        <i data-lucide="clock" style="color: #D97706;"></i>
                                </button>
                            </form>

                            <form method="POST" action="ges_messages.php" id="form-traiter">
                                <input type="hidden" name="message_id" id="traiter-message-id">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="marquer_traite" value="1"
                                    class="btn btn-primary"
                                    title="Marquer comme traité"
                                    aria-label="Marquer comme traité">
                                    <i data-lucide="check-circle" style="color: #FFFFFF;"></i>
                                </button>
                            </form>

                            <form method="POST" action="ges_messages.php" id="form-archiver">
                                <input type="hidden" name="message_id" id="archiver-message-id">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="archiver_message"
                                    value="1"
                                    class="btn"
                                    title="Archiver le message"
                                    aria-label="Archiver le message">
                                    <i data-lucide="archive" style="color: #64748B;"></i>
                                </button>
                            </form>

                            <form method="POST" action="ges_messages.php" id="form-supprimer">
                                <input type="hidden" name="message_id" id="supprimer-message-id">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" name="supprimer_message" value="1"
                                    class="btn btn-danger"
                                    title="Supprimer définitivement le message"
                                    aria-label="Supprimer définitivement le message">
                                    <i data-lucide="trash-2" style="color: #FCA5A5;"></i>
                                </button>
                            </form>

                        </div>
                    </div>

            </div>

</div>

<!-- Fenêtre de confirmation des actions -->
<div class="modal" id="modal-confirmation">

    <div class="modal-content">

        <div class="card-header">
            <h2 id="confirmation-titre">Confirmation</h2>
        </div>

        <p id="confirmation-message"></p>

        <div class="confirmation-actions">
            <button type="button" id="annuler-confirmation" class="btn">
                Annuler
            </button>

            <button type="button" id="valider-confirmation" class="btn btn-primary">
                Confirmer
            </button>
        </div>

    </div>

</div>

</main>
    <section style="
    display: grid;
    grid-template-columns: repeat(5, minmax(0, 1fr));
    gap: 20px;
    margin-bottom: 25px;
">

    <div class="card">
        <div class="card-header">
            <h3>Total des messages</h3>
        </div>
        <h2><?= $totalMessages ?></h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Nouveaux messages</h3>
        </div>
        <h2><?= $nouveauxMessages ?></h2>
    </div>

    <div class="card">
        <div class="card-header">
            <h3>Messages lus</h3>
        </div>
        <h2><?= $messagesLus ?></h2>
    </div>

    <div class="card">
    <div class="card-header">
        <h3>Messages en attente</h3>
    </div>
    <h2><?= $messagesEnAttente ?></h2>
 </div>

    <div class="card">
        <div class="card-header">
            <h3>Messages traités</h3>
        </div>
        <h2><?= $messagesTraites ?></h2>
    </div>

 </section>
 <section class="card">

    <div class="card-header">
        <h2>Messages reçus</h2>
        <span><?= count($messages) ?> message(s)</span>
    </div>

    <div class="table-responsive">

 <div class="message-tabs">
    <button type="button" class="btn btn-primary message-tab active"
            data-filter="nouveau">
        Nouveaux (<span class="compteur-message"><?= $nouveauxMessages ?></span>)
    </button>

    <button type="button" class="btn message-tab"
            data-filter="suivi">
        À suivre (<span class="compteur-message"><?= $messagesLus + $messagesEnAttente ?></span>)
    </button>

    <button type="button" class="btn message-tab"
            data-filter="traite">
        Traités (<span class="compteur-message"><?= $messagesTraites ?></span>)
    </button>

    <button type="button" class="btn message-tab"
            data-filter="archive">
        Archives (<span class="compteur-message"><?= $messagesArchives ?></span>)
    </button>
 </div>

        <table class="data-table">
            <thead>
                <tr>
                    <th>Expéditeur</th>
                    <th>Sujet</th>
                    <th>Date de réception</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>
                <?php if (empty($messages)): ?>

                    <tr>
                        <td colspan="5">
                            Aucun message reçu pour le moment.
                        </td>
                    </tr>

                <?php else: ?>

                    <?php foreach ($messages as $msg): ?>
<tr data-statut="<?= htmlspecialchars($msg['statut'], ENT_QUOTES, 'UTF-8') ?>"><tr
    data-id="<?= (int) $msg['id'] ?>"
    data-statut="<?= htmlspecialchars($msg['statut'], ENT_QUOTES, 'UTF-8') ?>"
>                            <td>
                                <strong>
                                    <?= htmlspecialchars($msg['nom'], ENT_QUOTES, 'UTF-8') ?>
                                </strong>
                                <br>
                                <small>
                                    <?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?>
                                </small>
                            </td>

                            <td>
                                <?= htmlspecialchars($msg['sujet'], ENT_QUOTES, 'UTF-8') ?>
                            </td>

                            <td>
                                <?= date('d/m/Y à H:i', strtotime($msg['date_reception'])) ?>
                            </td>

                            <td>
    <?php
    $statuts = [
        'nouveau' => [
            'label' => 'Nouveau',
            'classe' => 'badge-info'
        ],
        'lu' => [
            'label' => 'Lu',
            'classe' => 'badge-warning'
        ],

        'en_attente' => [
            'label' => 'En attente',
            'classe' => 'badge-warning'
        ],
        'traite' => [
            'label' => 'Traité',
            'classe' => 'badge-success'
        ],
        'archive' => [
           'label' => 'Archivé',
           'classe' => 'badge-secondary'
       ]
    ];

    $statut = $statuts[$msg['statut']] ?? [
        'label' => 'Inconnu',
        'classe' => ''
    ];
    ?>

    <span class="badge statut-message <?= $statut['classe'] ?>">
        <?= htmlspecialchars($statut['label'], ENT_QUOTES, 'UTF-8') ?>
    </span>
 </td>

    <td class="actions-tableau">
        <!-- Bouton Consulter -->
        <form method="POST" action="ges_messages.php" class="form-consulter">
            <input type="hidden" name="message_id" value="<?= (int) $msg['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <button
                type="button"
                class="btn btn-primary btn-consulter"
                data-id="<?= (int) $msg['id'] ?>"
                data-statut="<?= htmlspecialchars($msg['statut'], ENT_QUOTES, 'UTF-8') ?>"
                data-nom="<?= htmlspecialchars($msg['nom'], ENT_QUOTES, 'UTF-8') ?>"
                data-email="<?= htmlspecialchars($msg['email'], ENT_QUOTES, 'UTF-8') ?>"
                data-telephone="<?= htmlspecialchars($msg['telephone'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                data-sujet="<?= htmlspecialchars($msg['sujet'], ENT_QUOTES, 'UTF-8') ?>"
                data-message="<?= htmlspecialchars($msg['message'], ENT_QUOTES, 'UTF-8') ?>"
                data-date="<?= date('d/m/Y à H:i', strtotime($msg['date_reception'])) ?>"
                    title="Consulter le message"
                    aria-label="Consulter le message">
            <i data-lucide="eye" style="color: #FFFFFF;"></i>
            </button>
        </form>


    <!-- Bouton Archiver -->
    <?php if ($msg['statut'] === 'traite'): ?>
        <form method="POST" action="ges_messages.php" class="form-archiver-tableau">

            <input type="hidden" name="message_id"
                value="<?= (int) $msg['id'] ?>">

            <input type="hidden" name="csrf_token"
                value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

            <button type="submit" name="archiver_message"
                    value="1" class="btn">
                <i data-lucide="archive" style="color: #64748B;"></i>
            </button>

        </form>
    <?php endif; ?>

    <!-- Bouton Supprimer -->
    <?php if ($msg['statut'] === 'archive'): ?>
        <form method="POST" action="ges_messages.php" class="form-supprimer-tableau">
          <input type="hidden" name="message_id"
           value="<?= (int) $msg['id'] ?>">

         <input type="hidden" name="csrf_token"
           value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

           <button type="submit" name="supprimer_message"
            value="1" class="btn">
               <i data-lucide="trash-2" style="color: #FCA5A5;"></i>
          </button>
       </form>
      <?php endif; ?>
    </td>
</tr>
     <?php endforeach; ?>

     <?php endif; ?>
        </tbody>
        </table>
    </div>

</section>
    </div>

</div>

<script src="assets/js/app.js"></script>

<script>
    AGZ.initLayout('messages', {
        searchPlaceholder: 'Rechercher un message…',
        messagesCount: <?= (int) $nouveauxMessages ?>
    });

document.addEventListener('DOMContentLoaded', function () {
    const onglets = document.querySelectorAll('.message-tab');
    const lignes = document.querySelectorAll(
    '.data-table tbody tr[data-statut]'
);
    const tbody = document.querySelector('.data-table tbody');

    if (!tbody || onglets.length === 0) {
        return;
    }

    // Ligne affichée lorsqu'aucun message ne correspond au filtre
    const ligneVide = document.createElement('tr');
    ligneVide.innerHTML = `
        <td colspan="5">Aucun message dans cette section.</td>
    `;
    ligneVide.style.display = 'none';
    tbody.appendChild(ligneVide);

    function filtrerMessages(filtre) {
        let nombreVisible = 0;

        lignes.forEach(function (ligne) {
            const statut = ligne.dataset.statut;

            const afficher =
                filtre === 'suivi'
                    ? ['lu', 'en_attente'].includes(statut)
                    : statut === filtre;

            ligne.style.display = afficher ? '' : 'none';

            if (afficher) {
                nombreVisible++;
            }
        });

        ligneVide.style.display = nombreVisible === 0 ? '' : 'none';

        onglets.forEach(function (onglet) {
            const actif = onglet.dataset.filter === filtre;

            onglet.classList.toggle('active', actif);
            onglet.classList.toggle('btn-primary', actif);
        });
    }

    onglets.forEach(function (onglet) {
        onglet.addEventListener('click', function () {
            filtrerMessages(onglet.dataset.filter);
        });
    });

    // Restaurer l'onglet indiqué dans l'URL
const parametresURL = new URLSearchParams(window.location.search);
const ongletURL = parametresURL.get('onglet');

const ongletsDisponibles = ['nouveau', 'suivi', 'traite', 'archive'];

const ongletInitial = ongletsDisponibles.includes(ongletURL)
    ? ongletURL
    : 'nouveau';

filtrerMessages(ongletInitial);
});

</script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        lucide.createIcons();
    });
</script>
</body>

<script>

document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('modal-message');
    const boutonFermer = document.getElementById('fermer-modal');
    const boutonsConsulter = document.querySelectorAll('.btn-consulter');
    const formulaireAttente = document.getElementById('form-attente');
    const formulaireTraiter = document.getElementById('form-traiter');
    const formulaireArchiver = document.getElementById('form-archiver');

   const formulaireSupprimer =
    document.getElementById('form-supprimer');


  // Actualiser les boutons d'action selon le statut
function actualiserActionsMessage(statut) {

    const formulaireAttente =
        document.getElementById('form-attente');

    const formulaireTraiter =
        document.getElementById('form-traiter');

    const formulaireArchiver =
        document.getElementById('form-archiver');

    const formulaireSupprimer =
        document.getElementById('form-supprimer');


    // Exécuter l'action après confirmation
boutonValiderConfirmation.addEventListener('click', function () {

    if (typeof actionAConfirmer === 'function') {

        const action = actionAConfirmer;

        actionAConfirmer = null;

        modalConfirmation.style.display = 'none';

        action();

    }

});

    const actionsMessage =
        document.querySelector('.message-actions');

    formulaireAttente.style.display =
        ['nouveau', 'lu'].includes(statut) ? 'block' : 'none';

    formulaireTraiter.style.display =
        ['nouveau', 'lu', 'en_attente'].includes(statut)
            ? 'block'
            : 'none';

    formulaireArchiver.style.display =
        statut === 'traite' ? 'block' : 'none';

    formulaireSupprimer.style.display =
        statut === 'archive' ? 'block' : 'none';

    actionsMessage.style.display = 'block';
}


// Éléments de la fenêtre de confirmation
const modalConfirmation =
    document.getElementById('modal-confirmation');

const titreConfirmation =
    document.getElementById('confirmation-titre');

const messageConfirmation =
    document.getElementById('confirmation-message');

const boutonAnnulerConfirmation =
    document.getElementById('annuler-confirmation');

const boutonValiderConfirmation =
    document.getElementById('valider-confirmation');

// Action en attente de confirmation
let actionAConfirmer = null;

// Ouvrir la fenêtre de confirmation
function demanderConfirmation(titre, message, action) {

    titreConfirmation.textContent = titre;
    messageConfirmation.textContent = message;

    actionAConfirmer = action;

    modalConfirmation.style.display = 'flex';
}

// Annuler l'action
boutonAnnulerConfirmation.addEventListener('click', function () {

    modalConfirmation.style.display = 'none';

    actionAConfirmer = null;

});

// Exécuter l'action après confirmation
boutonValiderConfirmation.addEventListener('click', function () {

   if (typeof actionAConfirmer === 'function') {

       const action = actionAConfirmer;

       actionAConfirmer = null;

       modalConfirmation.style.display = 'none';

       action();

   }

});


// Actualiser automatiquement les compteurs des onglets
function actualiserCompteursMessages() {

    const lignes = document.querySelectorAll(
    '.data-table tbody tr[data-id][data-statut]'
);

    let compteurs = {
        nouveau: 0,
        suivi: 0,
        traite: 0,
        archive: 0
    };

    lignes.forEach(function (ligne) {

        const statut = ligne.dataset.statut;

        if (statut === 'nouveau') {
            compteurs.nouveau++;
        } else if (['lu', 'en_attente'].includes(statut)) {
            compteurs.suivi++;
        } else if (statut === 'traite') {
            compteurs.traite++;
        } else if (statut === 'archive') {
            compteurs.archive++;
        }
    });

    document.querySelector('[data-filter="nouveau"] .compteur-message').textContent =
        compteurs.nouveau;

    document.querySelector('[data-filter="suivi"] .compteur-message').textContent =
        compteurs.suivi;

    document.querySelector('[data-filter="traite"] .compteur-message').textContent =
        compteurs.traite;

    document.querySelector('[data-filter="archive"] .compteur-message').textContent =
        compteurs.archive;
}

// Ajouter une confirmation aux formulaires créés dynamiquement
function ajouterConfirmationFormulaireTableau(formulaire, type) {

    let confirmationAcceptee = false;

    formulaire.addEventListener('submit', function (event) {

        if (confirmationAcceptee) {
            confirmationAcceptee = false;
            return;
        }

        event.preventDefault();

        const estArchivage = type === 'archive';

        demanderConfirmation(
            estArchivage
                ? 'Confirmer l’archivage'
                : 'Confirmer la suppression',

            estArchivage
                ? 'Voulez-vous vraiment archiver ce message ?'
                : 'Attention ! Voulez-vous vraiment supprimer définitivement ce message ? Cette action est irréversible.',

            function () {

                confirmationAcceptee = true;

                const bouton = formulaire.querySelector(
                    'button[type="submit"]'
                );

                formulaire.requestSubmit(bouton);
            }
        );

    });
}

// Actualiser les boutons d'action dans le tableau
function actualiserBoutonsTableau(ligne, statut) {

    const zoneActions = ligne.querySelector('.actions-tableau');

    if (!zoneActions) {
        return;
    }

    // Supprimer les anciens boutons Archiver et Supprimer
    zoneActions.querySelectorAll(
        '.form-archiver-tableau, .form-supprimer-tableau'
    ).forEach(function (formulaire) {
        formulaire.remove();
    });

    // Créer le bouton Archiver si le message est traité
    if (statut === 'traite') {

        const formulaire = document.createElement('form');
        formulaire.method = 'POST';
        formulaire.action = 'ges_messages.php';
        formulaire.className = 'form-archiver-tableau';

        formulaire.innerHTML = `
            <input type="hidden" name="message_id"
                   value="${ligne.dataset.id}">
            <input type="hidden" name="onglet" value="traite">

            <input type="hidden" name="csrf_token"
                   value="${document.querySelector('#form-archiver input[name="csrf_token"]').value}">

            <button type="submit" name="archiver_message"
                    value="1" class="btn">
                <i data-lucide="archive" style="color: #64748B;"></i>
            </button>
        `;

        zoneActions.appendChild(formulaire);
        ajouterConfirmationFormulaireTableau(formulaire, 'archive');
    }

    // Créer le bouton Supprimer si le message est archivé
    if (statut === 'archive') {

        const formulaire = document.createElement('form');
        formulaire.method = 'POST';
        formulaire.action = 'ges_messages.php';
        formulaire.className = 'form-supprimer-tableau';

        formulaire.innerHTML = `
            <input type="hidden" name="message_id"
                   value="${ligne.dataset.id}">
            <input type="hidden" name="onglet" value="archive">

            <input type="hidden" name="csrf_token"
                   value="${document.querySelector('#form-supprimer input[name="csrf_token"]').value}">

            <button type="submit" name="supprimer_message"
                    value="1" class="btn">
                <i data-lucide="trash-2" style="color: #FCA5A5;"></i>
            </button>
        `;

        zoneActions.appendChild(formulaire);
        ajouterConfirmationFormulaireTableau(formulaire, 'supprimer');
    }

    // Recréer les icônes Lucide
    lucide.createIcons();
}

    // Envoyer les actions de statut sans recharger la page
    async function envoyerActionStatut(formulaire, nouveauStatut) {
        const donnees = new FormData(formulaire);
            donnees.append(
            nouveauStatut === 'en_attente' ? 'mettre_en_attente' : 'marquer_traite', '1');
        const bouton = formulaire.querySelector('button[type="submit"]');

        if (bouton) {
            bouton.disabled = true;
        }

        try {
            const reponse = await fetch(formulaire.action, {
                method: 'POST',
                body: donnees,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const resultat = await reponse.json();

            if (!reponse.ok || !resultat.success) {
                throw new Error(resultat.message || 'La mise à jour a échoué.');
            }

            const ligne = document.querySelector(
              '.data-table tbody tr[data-id="' + resultat.id + '"]'
            );

            if (ligne) {
                ligne.dataset.statut = nouveauStatut;
                actualiserBoutonsTableau(ligne, nouveauStatut);
                actualiserCompteursMessages();

                const badge = ligne.querySelector('.statut-message');

                const statuts = {
                    en_attente: {
                        label: 'En attente',
                        classe: 'badge-warning'
                    },
                    traite: {
                        label: 'Traité',
                        classe: 'badge-success'
                    }
                };

                const statut = statuts[nouveauStatut];

                if (badge && statut) {
                    badge.textContent = statut.label;
                    badge.className = 'badge statut-message ' + statut.classe;
                }

                // Actualiser le statut du bouton Consulter
                const boutonConsulter = ligne.querySelector('.btn-consulter');

                if (boutonConsulter) {
                    boutonConsulter.dataset.statut = nouveauStatut;
                }

                // Adapter l'affichage de la ligne à l'onglet actif
                const filtreActif = document.querySelector('.message-tab.active')?.dataset.filter;

                const doitAfficher =
                    filtreActif === 'suivi'
                        ? ['lu', 'en_attente'].includes(nouveauStatut)
                        : filtreActif === nouveauStatut;

                ligne.style.display = doitAfficher ? '' : 'none';
            }

            // Actualiser les boutons de la fenêtre de consultation
     actualiserActionsMessage(nouveauStatut);

            // Fermer automatiquement la fenêtre après toute action réussie
                modal.style.display = 'none';

        } catch (erreur) {
            alert(erreur.message);
        } finally {
            if (bouton) {
                bouton.disabled = false;
            }
        }
    }

    // Archiver un message sans recharger la page
async function envoyerActionArchivage(formulaire) {
    const donnees = new FormData(formulaire);
    donnees.append('archiver_message', '1');

    const bouton = formulaire.querySelector('button[type="submit"]');

    if (bouton) {
        bouton.disabled = true;
    }

    try {
        const reponse = await fetch(formulaire.action, {
            method: 'POST',
            body: donnees,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const resultat = await reponse.json();

        if (!reponse.ok || !resultat.success) {
            throw new Error(resultat.message || "L'archivage a échoué.");
        }

        const ligne = document.querySelector(
           '.data-table tbody tr[data-id="' + resultat.id + '"]'
          );

        if (ligne) {
            ligne.dataset.statut = 'archive';
            actualiserBoutonsTableau(ligne, 'archive');

            const badge = ligne.querySelector('.statut-message');

            if (badge) {
                badge.textContent = 'Archivé';
                badge.className = 'badge statut-message badge-secondary';
            }

            const boutonConsulter = ligne.querySelector('.btn-consulter');

            if (boutonConsulter) {
                boutonConsulter.dataset.statut = 'archive';
            }

            const filtreActif = document.querySelector(
                '.message-tab.active'
            )?.dataset.filter;

            ligne.style.display =
                filtreActif === 'archive' ? '' : 'none';
        }

        actualiserCompteursMessages();
        actualiserActionsMessage('archive');

        // Fermer automatiquement la fenêtre après archivage
        modal.style.display = 'none';

    } catch (erreur) {
        alert(erreur.message);
    } finally {
        if (bouton) {
            bouton.disabled = false;
        }
    }
}

    formulaireAttente.addEventListener('submit', function (event) {
        event.preventDefault();
        envoyerActionStatut(formulaireAttente, 'en_attente');
    });

    formulaireTraiter.addEventListener('submit', function (event) {
        event.preventDefault();
        envoyerActionStatut(formulaireTraiter, 'traite');
    });

   formulaireArchiver.addEventListener('submit', function (event) {
    event.preventDefault();

    demanderConfirmation(
        'Confirmer l’archivage',
        'Voulez-vous vraiment archiver ce message ?',
        function () {
            envoyerActionArchivage(formulaireArchiver);
        }
    );
});

// Confirmer l'archivage depuis le tableau
document.querySelectorAll('.form-archiver-tableau').forEach(function (formulaire) {

    let confirmationAcceptee = false;

    formulaire.addEventListener('submit', function (event) {

        if (confirmationAcceptee) {
            confirmationAcceptee = false;
            return;
        }

        event.preventDefault();

        demanderConfirmation(
            'Confirmer l’archivage',
            'Voulez-vous vraiment archiver ce message ?',
            function () {

                confirmationAcceptee = true;

                const bouton = formulaire.querySelector('button[type="submit"]');

                formulaire.requestSubmit(bouton);
            }
        );

    });

});

// Confirmer la suppression depuis le tableau
document.querySelectorAll('.form-supprimer-tableau').forEach(function (formulaire) {

    let confirmationAcceptee = false;

    formulaire.addEventListener('submit', function (event) {

        if (confirmationAcceptee) {
            confirmationAcceptee = false;
            return;
        }

        event.preventDefault();

        demanderConfirmation(
            'Confirmer la suppression',
            'Attention ! Voulez-vous vraiment supprimer définitivement ce message ? Cette action est irréversible.',
            function () {

                confirmationAcceptee = true;

                const bouton = formulaire.querySelector('button[type="submit"]');

                formulaire.requestSubmit(bouton);
            }
        );

    });

});

// Confirmer la suppression définitive d'un message
let confirmationSuppressionAcceptee = false;

formulaireSupprimer.addEventListener('submit', function (event) {

    if (confirmationSuppressionAcceptee) {
        confirmationSuppressionAcceptee = false;
        return;
    }

    event.preventDefault();

    demanderConfirmation(
        'Confirmer la suppression',
        'Attention ! Voulez-vous vraiment supprimer définitivement ce message ? Cette action est irréversible.',
        function () {

            confirmationSuppressionAcceptee = true;

            formulaireSupprimer.requestSubmit(
                formulaireSupprimer.querySelector('button[type="submit"]')
            );

        }
    );

});


    // Préparer et ouvrir le détail du message
function ouvrirMessage(bouton) {

    document.getElementById('detail-nom').textContent =
        bouton.dataset.nom;

    document.getElementById('detail-email').textContent =
        bouton.dataset.email;

    document.getElementById('detail-telephone').textContent =
        bouton.dataset.telephone || 'Non renseigné';

    document.getElementById('detail-sujet').textContent =
        bouton.dataset.sujet;

    document.getElementById('detail-date').textContent =
        bouton.dataset.date;

    document.getElementById('detail-message').textContent =
        bouton.dataset.message;

    document.getElementById('traiter-message-id').value =
        bouton.dataset.id;

    document.getElementById('archiver-message-id').value =
        bouton.dataset.id;

    document.getElementById('attente-message-id').value =
        bouton.dataset.id;

    document.getElementById('supprimer-message-id').value =
        bouton.dataset.id;

    // Afficher les actions selon le statut
    actualiserActionsMessage(bouton.dataset.statut);

    // Ouvrir la fenêtre de consultation
    modal.style.display = 'flex';
}

    // Installer les événements sur tous les boutons Consulter
    boutonsConsulter.forEach(function (bouton) {

        bouton.addEventListener('click', function () {

            ouvrirMessage(bouton);

            // Nouveau message ou message en attente :
            // Marquer automatiquement un nouveau message comme lu sans recharger la page
if (bouton.dataset.statut === 'nouveau') {

    const formulaire = bouton.closest('form');
    const donnees = new FormData(formulaire);

    donnees.append('marquer_lu', '1');

    fetch(formulaire.action, {
        method: 'POST',
        body: donnees,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(async reponse => {
        const resultat = await reponse.json();

        if (!reponse.ok || !resultat.success) {
            throw new Error(resultat.message || 'Impossible de marquer le message comme lu.');
        }

        // Actualiser le statut dans le tableau
       bouton.dataset.statut = 'lu';
       actualiserActionsMessage('lu');

       const ligne = bouton.closest('tr');

       if (ligne) {
    ligne.dataset.statut = 'lu';

    // Actualiser les compteurs après la modification du statut
    actualiserCompteursMessages();

            const badge = ligne.querySelector('.statut-message');

            if (badge) {
                badge.textContent = 'Lu';
                badge.className = 'badge statut-message badge-info';
            }

            // Masquer le message dans l'onglet Nouveaux
            const filtreActif = document.querySelector('.message-tab.active')?.dataset.filter;

            if (filtreActif === 'nouveau') {
                ligne.style.display = 'none';
            }
        }
    })
    .catch(erreur => {
        alert(erreur.message);
    });
}

        });

    });

    // Rouvrir automatiquement le message après sa mise à jour.
    // Ce bloc est volontairement placé hors de boutonsConsulter.forEach().
    const urlParams = new URLSearchParams(window.location.search);
    const messageId = urlParams.get('ouvrir');

    if (messageId) {

        const boutonMessage = document.querySelector(
            '.btn-consulter[data-id="' + messageId + '"]'
        );

        if (boutonMessage) {
    ouvrirMessage(boutonMessage);
       }

        // Nettoyer l'URL sans recharger la page
        window.history.replaceState(
            {},
            document.title,
            'ges_messages.php'
        );
    }

    // Fermer le modal avec le bouton X
    boutonFermer.addEventListener('click', function () {
        modal.style.display = 'none';
    });

    // Fermer le modal en cliquant sur l'arrière-plan
    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });

});

</script>
</html>