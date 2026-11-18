

<?php
/*require_once "auth.php"; */

session_start();

if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

$nom_admin = $_SESSION["admin_username"] ?? "Administrateur";

require_once "db.php";

$ip_visiteur = $_SERVER['REMOTE_ADDR'];

$sql = "INSERT INTO visites (page, ip_visiteur)
        VALUES (:page, :ip_visiteur)";

$stmt = $pdo->prepare($sql);

$stmt->execute([
    "page"        => "index.php",
    "ip_visiteur" => $ip_visiteur
]);

// Date d'aujourd'hui

$date_formatee = new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::LONG,
    IntlDateFormatter::NONE,
    'Africa/Porto-Novo',
    IntlDateFormatter::GREGORIAN,
    'd MMMM yyyy'
);

$date_du_jour = $date_formatee->format(new DateTime());

// Visiteurs enregistrés aujourd'hui

$sql_count = "SELECT COUNT(*)
              FROM visites
              WHERE DATE(date_visite) = CURDATE()";

$stmt_count = $pdo->query($sql_count);
$total_visites = $stmt_count->fetchColumn();

// Visites enregistrées ce mois

$sql_mois = "SELECT COUNT(*)
             FROM visites
             WHERE YEAR(date_visite) = YEAR(CURDATE())
               AND MONTH(date_visite) = MONTH(CURDATE())";

$stmt_mois = $pdo->query($sql_mois);
$total_visites_mois = $stmt_mois->fetchColumn();

// Nombre d'articles publiés

$sql_articles = "SELECT COUNT(*)
                 FROM articles
                 WHERE statut = 'publie'";

$stmt_articles = $pdo->query($sql_articles);
$total_articles_publies = $stmt_articles->fetchColumn();


// Statistiques des messages reçus

$sql_messages = "
    SELECT
        COUNT(*) AS total,
        SUM(statut = 'nouveau') AS nouveaux
    FROM messages
";

$stmt_messages = $pdo->query($sql_messages);
$stats_messages = $stmt_messages->fetch(PDO::FETCH_ASSOC);

$total_messages = (int) ($stats_messages['total'] ?? 0);

$total_messages_non_lus = (int) ($stats_messages['nouveaux'] ?? 0);
/*
$sql_count = "SELECT COUNT(*) FROM visites";

$stmt_count = $pdo->query($sql_count);

$total_visites = $stmt_count->fetchColumn();
*/

/* echo $total_visites; */

// 10 dernières visites

$sql_visites = "SELECT id, page, ip_visiteur, date_visite
                FROM visites
                ORDER BY id DESC
                LIMIT 10";

$stmt_visites = $pdo->query($sql_visites);

$visites = $stmt_visites->fetchAll(PDO::FETCH_ASSOC);



 // Les 5 dernières activités administratives

$sql_activites = "SELECT action, description, entite, entite_id, date_action
                  FROM activites_admin
                  ORDER BY date_action DESC
                  LIMIT 5";

$stmt_activites = $pdo->query($sql_activites);

$activites_recentes = $stmt_activites->fetchAll(PDO::FETCH_ASSOC);


// Statistiques du trafic sur les 7 derniers jours

$sql_graphique = "SELECT DATE(date_visite) AS jour, COUNT(*) AS total
                  FROM visites
                  WHERE date_visite >= CURDATE() - INTERVAL 6 DAY
                    AND date_visite < CURDATE() + INTERVAL 1 DAY
                  GROUP BY DATE(date_visite)
                  ORDER BY jour ASC";

$stmt_graphique = $pdo->query($sql_graphique);
$donnees_graphique = $stmt_graphique->fetchAll(PDO::FETCH_ASSOC);

// Préparer les données pour les 7 jours du graphique

$visites_par_jour = [];

foreach ($donnees_graphique as $ligne) {
    $visites_par_jour[$ligne['jour']] = (int) $ligne['total'];
}

$labels_graphique = [];
$valeurs_graphique = [];

for ($i = 6; $i >= 0; $i--) {
    $date = new DateTimeImmutable("-{$i} days");
    $jour = $date->format('Y-m-d');

    $labels_graphique[] = $date->format('d');
    $valeurs_graphique[] = $visites_par_jour[$jour] ?? 0;
}

$mois_graphique = (new IntlDateFormatter(
    'fr_FR',
    IntlDateFormatter::NONE,
    IntlDateFormatter::NONE,
    'Africa/Porto-Novo',
    IntlDateFormatter::GREGORIAN,
    'MMMM'
))->format(new DateTime());

// Calculer les coordonnées du graphique

$largeur_graphique = 560;
$hauteur_graphique = 160;
$decalage_haut = 20;

$maximum_visites = max(1, max($valeurs_graphique));
$points_graphique = [];

foreach ($valeurs_graphique as $index => $total) {
    $x = $index * ($largeur_graphique / 6);

    $y = $decalage_haut + $hauteur_graphique
       - ($total / $maximum_visites) * $hauteur_graphique;

    $points_graphique[] = round($x, 2) . ',' . round($y, 2);
}

$ligne_graphique = implode(' ', $points_graphique);

$premier_point = $points_graphique[0];
$dernier_point = $points_graphique[count($points_graphique) - 1];

$zone_graphique = $ligne_graphique . ' 560,200 0,200';

?>


<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Tableau de bord — AGZ Solutions</title>

    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <div class="app-shell">

        <!-- Barre latérale -->
        <aside class="sidebar" id="agz-sidebar"></aside>

        <div class="main-wrapper">

            <!-- Barre supérieure -->
            <header class="topbar" id="agz-topbar"></header>

            <main class="content">

                <!-- En-tête de la page -->
                <section class="page-head">
                    <div class="page-head-row">

                        <div>
                            <h1>Tableau de bord</h1>

                            <p> Bonjour, <?= htmlspecialchars($nom_admin,ENT_QUOTES,'UTF-8' ) ?> 👋 - voici un aperçu de l'activité de votre site.</p>
                        </div>

                        <!-- Date du jour -->
                        <div class="btn btn-outline">
                            <span>
                                Aujourd'hui - <?= date('j F Y') ?>
                            </span>
                        </div>

                    </div>
                </section>

                <!-- Statistiques principales -->
                <section
                    class="grid grid-4"
                    style="margin-bottom: 18px;"
                    aria-label="Statistiques du site"
                >

                    <!-- Visiteurs du jour -->
                    <div class="card stat-card">
                        <div class="label">
                            <span>Visiteurs aujourd'hui</span>
                        </div>

                        <div class="value">
                            <p><?= (int) $total_visites ?></p>
                        </div>
                    </div>

                    <!-- Visiteurs du mois -->
                    <div class="card stat-card">
                        <div class="label">
                            <span>Visiteurs ce mois</span>
                        </div>

                        <div class="value">
                            <p>
                                <?= number_format(
                                    (int) $total_visites_mois,
                                    0,
                                    ',',
                                    ' '
                                ) ?>
                            </p>
                        </div>

                        <div class="delta">
                            Visites enregistrées ce mois
                        </div>
                    </div>

                    <!-- Messages reçus -->
                    <div class="card stat-card">
                        <div class="label">
                            <span>Messages reçus</span>
                        </div>

                        <div class="value">
                            <p><?= (int) $total_messages ?></p>
                        </div>

                        <div class="delta">
                            <?= (int) $total_messages_non_lus ?>
                            nouveau(x) message(s)
                        </div>
                    </div>

                    <!-- Articles publiés -->
                    <div class="card stat-card">
                        <div class="label">
                            <span>Articles publiés</span>
                        </div>

                        <div class="value">
                            <p><?= (int) $total_articles_publies ?></p>
                        </div>

                        <div class="delta">
                            Articles actuellement publiés
                        </div>
                    </div>

                </section>

                <!-- Graphique et activité récente -->
                <section class="grid grid-2">

                    <!-- Trafic du site -->
                    <div class="card">

                        <div class="card-header">
                            <h2>Trafic du site</h2>
                            <span class="muted">7 derniers jours</span>
                        </div>

                        <div class="card-pad">

                            <svg
                                viewBox="0 0 560 200"
                                width="100%"
                                height="200"
                                preserveAspectRatio="none"
                                role="img"
                                aria-label="Évolution du trafic du site sur les sept derniers jours"
                            >
                                <defs>
                                    <linearGradient
                                        id="gradFill"
                                        x1="0"
                                        y1="0"
                                        x2="0"
                                        y2="1"
                                    >
                                        <stop
                                            offset="0%"
                                            stop-color="#2e6ff2"
                                            stop-opacity="0.22"
                                        />

                                        <stop
                                            offset="100%"
                                            stop-color="#2e6ff2"
                                            stop-opacity="0"
                                        />
                                    </linearGradient>
                                </defs>

                                <!-- Lignes horizontales -->
                                <g
                                    stroke="#eef1f6"
                                    stroke-width="1"
                                >
                                    <line x1="0" y1="10" x2="560" y2="10" />
                                    <line x1="0" y1="60" x2="560" y2="60" />
                                    <line x1="0" y1="110" x2="560" y2="110" />
                                    <line x1="0" y1="160" x2="560" y2="160" />
                                </g>

                                <!-- Zone sous la courbe -->
                                <polygon
                                    points="<?= htmlspecialchars(
                                        $zone_graphique,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    fill="url(#gradFill)"
                                />

                                <!-- Courbe du trafic -->
                                <polyline
                                    points="<?= htmlspecialchars(
                                        $ligne_graphique,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>"
                                    fill="none"
                                    stroke="#2e6ff2"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>

                            <!-- Dates des sept derniers jours -->
                            <div
                                class="chart-labels"
                                style="
                                    display: flex;
                                    justify-content: space-between;
                                    align-items: center;
                                    gap: 8px;
                                    color: var(--text-400);
                                    font-size: 12px;
                                    margin-top: 6px;
                                "
                            >
                                <?php foreach ($labels_graphique as $label): ?>
                                    <span style="flex: 1; text-align: center;">
                                        <?= htmlspecialchars(
                                            $label,
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>

                            <!-- Mois affiché -->
                            <div
                                style="
                                    text-align: right;
                                    color: var(--text-400);
                                    font-size: 12px;
                                    margin-top: 4px;
                                "
                            >
                                <?= htmlspecialchars(
                                    ucfirst($mois_graphique),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </div>

                        </div>
                    </div>

<!-- Activité récente -->
<div class="card">

    <div class="card-header">
        <h2>Activité récente</h2>
        <span class="muted">5 dernières actions</span>
    </div>

    <div class="card-pad" style="padding-top: 6px;">

    <?php if (!empty($activites_recentes)): ?>

        <?php foreach ($activites_recentes as $activite): ?>

            <?php
            $action = $activite['action'] ?? 'Activité';
            $description = $activite['description'] ?? '';
            $entite = $activite['entite'] ?? '';
            $date_action = $activite['date_action'] ?? '';

            $initiale = strtoupper(substr($action, 0, 1));

            $date_relative = '';

            if (!empty($date_action)) {
                $maintenant = new DateTimeImmutable();
                $date = new DateTimeImmutable($date_action);
                $difference = $maintenant->getTimestamp() - $date->getTimestamp();

                if ($difference < 60) {
                    $date_relative = "À l'instant";
                } elseif ($difference < 3600) {
                    $date_relative = "Il y a " . floor($difference / 60) . " min";
                } elseif ($difference < 86400) {
                    $date_relative = "Il y a " . floor($difference / 3600) . " h";
                } else {
                    $date_relative = $date->format('d/m/Y à H:i');
                }
            }
            ?>

            <div class="activity-item">
                <div class="activity-icon">
                    <?= htmlspecialchars($initiale, ENT_QUOTES, 'UTF-8') ?>
                </div>

                <div class="activity-body">
                    <p>
                        <?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>
                    </p>

                    <span>
                        <?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?>

                        <?php if (!empty($entite)): ?>
                            — <?= htmlspecialchars($entite, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>

                        <?php if (!empty($date_relative)): ?>
                            — <?= htmlspecialchars($date_relative, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </span>
                </div>
            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p class="text-muted">
            Aucune activité enregistrée pour le moment.
        </p>

    <?php endif; ?>

</div>

                    </div>

                </section>

            </main>
        </div>
    </div>

    <!-- Scripts de l'interface -->
    <script src="assets/js/app.js"></script>

    <script>
        AGZ.initLayout('dashboard', {
            searchPlaceholder: 'Rechercher…',
            adminUsername: <?= json_encode(
                $nom_admin,
                JSON_HEX_TAG
                | JSON_HEX_APOS
                | JSON_HEX_QUOT
                | JSON_HEX_AMP
            ) ?>
        });
    </script>

</body>
</html>
