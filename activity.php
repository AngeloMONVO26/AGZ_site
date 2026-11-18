
<?php

/**
 * Enregistre une activité effectuée par l'administrateur connecté.
 *
 * Prérequis :
 * - La session administrateur doit être démarrée.
 * - La connexion PDO doit être disponible dans $pdo.
 */
function enregistrerActivite(
    PDO $pdo,
    string $action,
    string $description,
    ?string $entite = null,
    ?int $entite_id = null
): bool {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    $admin_id = $_SESSION["admin_id"] ?? null;

    if (!$admin_id) {
        return false;
    }

    $sql = "INSERT INTO activites_admin
                (admin_id, action, description, entite, entite_id, date_action)
            VALUES
                (:admin_id, :action, :description, :entite, :entite_id, NOW())";

    $stmt = $pdo->prepare($sql);

    return $stmt->execute([
        "admin_id"   => $admin_id,
        "action"     => $action,
        "description" => $description,
        "entite"     => $entite,
        "entite_id"  => $entite_id
    ]);
}
