<?php

require_once __DIR__ . '/../admin/config.php';

$sent = false;
$error = '';

/*
|--------------------------------------------------------------------------
| Traitement du formulaire
|--------------------------------------------------------------------------
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Vérification du token CSRF
    if (
        !hash_equals(
            $_SESSION['csrf'] ?? '',
            $_POST['csrf'] ?? ''
        )
    ) {
        $error = 'Session expirée. Rechargez la page.';
    } else {

        // Enregistrement du message
        $s = store();

        $s->add(
            'messages',
            [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'company' => trim($_POST['company'] ?? ''),
                'subject' => trim($_POST['service'] ?? ''),
                'message' => trim($_POST['message'] ?? ''),
                'status' => 'new',
                'created_at' => date('Y-m-d H:i:s')
            ]
        );

        $sent = true;
    }
}

?>

<!doctype html>
<html lang="fr">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <title>
        Contact | AGZ Solutions
    </title>

    <link
        rel="stylesheet"
        href="assets/style.css"
    >

</head>

<body>


    <!-- =========================================================
         NAVIGATION
    ========================================================== -->
    <header class="navbar">

        <div class="container nav-inner">

            <a href="index.html">

                <img
                    class="logo"
                    src="assets/logo-agz-solutions.png"
                    alt="AGZ Solutions"
                >

            </a>


            <nav class="nav-links">

                <a href="index.html">
                    Accueil
                </a>

                <a href="services.html">
                    Services
                </a>

                <a href="a-propos.html">
                    À propos
                </a>

                <a href="blog.html">
                    Blog
                </a>

                <a
                    class="active"
                    href="contact.php"
                >
                    Contact
                </a>

            </nav>

        </div>

    </header>


    <!-- =========================================================
         HERO
    ========================================================== -->
    <section class="page-hero">

        <div class="container">

            <h1>
                Parlons de votre projet
            </h1>

            <p>
                Décrivez-nous votre besoin et nous vous répondrons rapidement.
            </p>

        </div>

    </section>


    <!-- =========================================================
         CONTACT
    ========================================================== -->
    <section class="section">

        <div class="container contact-grid">


            <!-- =====================================================
                 INFORMATIONS DE CONTACT
            ====================================================== -->
            <div>

                <h2>
                    AGZ Solutions
                </h2>

                <p>
                    Une question, un logiciel, une automatisation
                    ou un besoin informatique ?
                </p>


                <div class="contact-info">

                    <!-- Téléphone -->
                    <div class="contact-item">

                        <strong>
                            📞 Téléphone
                        </strong>

                        <br>

                        +229 01 97 12 49 05

                    </div>


                    <!-- E-mail -->
                    <div class="contact-item">

                        <strong>
                            ✉ E-mail
                        </strong>

                        <br>

                        agzsolutions.agz@gmail.com

                    </div>


                    <!-- Localisation -->
                    <div class="contact-item">

                        <strong>
                            📍 Localisation
                        </strong>

                        <br>

                        Cotonou, Bénin

                    </div>

                </div>

            </div>


            <!-- =====================================================
                 FORMULAIRE
            ====================================================== -->
            <form
                class="form"
                method="post"
            >

                <!-- Protection CSRF -->
                <input
                    type="hidden"
                    name="csrf"
                    value="<?= e(csrf_token()) ?>"
                >


                <!-- Message de succès -->
                <?php if ($sent): ?>

                    <div class="alert success">
                        Merci. Votre demande a bien été enregistrée.
                    </div>


                <!-- Message d'erreur -->
                <?php elseif ($error): ?>

                    <div class="alert error">
                        <?= e($error) ?>
                    </div>

                <?php endif; ?>


                <!-- Champs du formulaire -->
                <div class="form-grid">


                    <!-- Nom -->
                    <div>

                        <label>
                            Nom
                        </label>

                        <input
                            name="name"
                            required
                        >

                    </div>


                    <!-- E-mail -->
                    <div>

                        <label>
                            E-mail
                        </label>

                        <input
                            type="email"
                            name="email"
                            required
                        >

                    </div>


                    <!-- Entreprise -->
                    <div class="full">

                        <label>
                            Entreprise
                        </label>

                        <input
                            name="company"
                        >

                    </div>


                    <!-- Service -->
                    <div class="full">

                        <label>
                            Service
                        </label>

                        <select name="service">

                            <option>
                                Prestations informatiques
                            </option>

                            <option>
                                Conception &amp; développement
                            </option>

                            <option>
                                Logiciel de gestion
                            </option>

                            <option>
                                Automatisation des processus
                            </option>

                            <option>
                                Autre
                            </option>

                        </select>

                    </div>


                    <!-- Message -->
                    <div class="full">

                        <label>
                            Votre message
                        </label>

                        <textarea
                            name="message"
                            required
                        ></textarea>

                    </div>


                    <!-- Bouton -->
                    <div class="full">

                        <button
                            class="btn btn-primary"
                            type="submit"
                        >
                            Envoyer ma demande →
                        </button>

                    </div>

                </div>

            </form>

        </div>

    </section>


</body>

</html>