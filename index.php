<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Répertoire des Projets</title>
    <link href="style.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const allCheckbox = document.getElementById('all');
            const typeCheckboxes = document.querySelectorAll('input[type="checkbox"][name="fileType[]"]:not(#all)');

            // Gère le changement pour la case "Tous"
            allCheckbox.addEventListener('change', function() {
                const isChecked = this.checked;
                typeCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });
            });

            // Gère le changement pour les autres cases à cocher
            typeCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    // Vérifie si au moins une case autre que "Tous" est cochée
                    const isAnyChecked = Array.from(typeCheckboxes).some(c => c.checked);
                    if (isAnyChecked) {
                        allCheckbox.checked = false;
                    }
                });
            });
        });
    </script>

</head>
<body>
    <?php
        // Initialisation de $fileTypesRequested avec une valeur par défaut pour éviter l'erreur undefined.
        $fileTypesRequested = isset($_GET['fileType']) ? $_GET['fileType'] : ['all'];

        // Initialisation de $allSelected en fonction de $fileTypesRequested pour éviter l'erreur undefined.
        $allSelected = in_array('all', $fileTypesRequested);

        // Correction de la définition de $dir pour éviter les erreurs lors de l'utilisation de scandir().
        $baseDir = realpath(dirname(__FILE__));
        $currentDirRaw = isset($_GET['dir']) ? $_GET['dir'] : '';
        $currentDirSanitized = filter_var($currentDirRaw, FILTER_SANITIZE_STRING);
        $currentDirPath = $currentDirSanitized ? '/' . ltrim($currentDirSanitized, '/') : '';
        $dir = realpath($baseDir . $currentDirPath);

        if (!$dir || strpos($dir, $baseDir) !== 0) {
            die("Tentative d'accès non autorisée!");
        }

        $currentDirForLinks = $currentDirPath ? ltrim($currentDirPath, '/') : '';
        function formatSizeUnits($bytes) {
            if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
            elseif ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
            elseif ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
            elseif ($bytes > 1) return "$bytes bytes";
            elseif ($bytes == 1) return "1 byte";
            else return '0 bytes';
        }
        // Définition de la liste des projets
        $projects = array_diff(scandir($dir), ['..', '.']);

        $sortCriteria = isset($_GET['sort']) ? $_GET['sort'] : 'name';

        function sortProjects($a, $b) {
            global $sortCriteria, $dir;
        
            if ($sortCriteria == 'date') {
                // Compare la date de modification
                return filemtime($dir . '/' . $a) < filemtime($dir . '/' . $b) ? 1 : -1;
            } elseif ($sortCriteria == 'type') {
                // Compare l'extension de fichier
                return pathinfo($dir . '/' . $a, PATHINFO_EXTENSION) > pathinfo($dir . '/' . $b, PATHINFO_EXTENSION) ? 1 : -1;
            }
            // Tri par défaut par nom
            return strcasecmp($a, $b);
        }
        
        usort($projects, 'sortProjects');
        

    ?>

    <form method="GET" action="">
        <input type="hidden" name="dir" value="<?php echo htmlspecialchars($currentDirForLinks); ?>">
        <div>
            <label for="sort">Trier par :</label>
            <select name="sort" id="sort" onchange="this.form.submit()">
                <option value="name" <?php echo $sortCriteria == 'name' ? 'selected' : ''; ?>>Nom</option>
                <option value="type" <?php echo $sortCriteria == 'type' ? 'selected' : ''; ?>>Type</option>
                <option value="date" <?php echo $sortCriteria == 'date' ? 'selected' : ''; ?>>Date de modification</option>
            </select>
        </div>
        <div>
            <span>Sélectionnez le type de fichier :</span><br>
            <input type="checkbox" name="fileType[]" value="all" id="all" <?php if ($allSelected) echo 'checked'; ?>><label for="all">Tous</label><br>
            <?php
            $types = ['php', 'html', 'jpg', 'js', 'css', 'scss'];
            foreach ($types as $type) {
                $checked = in_array($type, $fileTypesRequested) ? 'checked' : '';
                echo "<input type=\"checkbox\" name=\"fileType[]\" value=\"$type\" id=\"$type\" $checked><label for=\"$type\">".strtoupper($type)."</label><br>";
            }
            ?>
        </div>
        <input type="submit" value="Filtrer">
    </form>

    <?php if ($currentDirPath !== ''): ?>
        <a href="?dir=<?= urlencode(dirname($currentDirForLinks)) ?>">Retour au dossier parent</a><br>
    <?php endif; ?>
  


    <div class="container">
        <h1>Projets Locaux</h1>
        <p>Chemin actuel: <?php echo htmlspecialchars('/' . $currentDirForLinks); ?></p>
        <div class="projects-list">
            <?php foreach ($projects as $project): ?>
                <?php
                    $isDir = is_dir($dir . '/' . $project);
                    $iconPath = 'images/' . ($isDir ? 'folder.jpg' : 'file.png');
                    $modificationTime = date("F d Y H:i:s", filemtime($dir . '/' . $project));
                    $extension = strtolower(pathinfo($project, PATHINFO_EXTENSION));
                    $fileSize = !$isDir ? formatSizeUnits(filesize($dir . '/' . $project)) : '';
                    $link = $isDir ? "?dir=" . urlencode($currentDirForLinks . '/' . $project) : $currentDirForLinks . '/' . $project;

                    if (!$isDir && in_array($extension, ['php', 'html', 'css', 'js', 'jpg', 'jpeg', 'png', 'gif'])) {
                        $iconPath = "images/$extension.png";
                    }

                    // Appliquez la logique de filtrage ici
                    if ($allSelected || in_array($extension, $fileTypesRequested) || $isDir) {
                ?>
                <div class="project-item">
                    <div class="project-icon"><img src="<?= $iconPath ?>" alt="Icon"></div>
                    <div class="project-name"><a href="<?= $link ?>"><?= $project ?></a></div>
                    <div class="project-size"><?= $fileSize ?></div>
                    <div class="project-modified"><?= $modificationTime ?></div>
                </div>
                <?php
                    } // Ferme le if de filtrage
                endforeach;
                ?>
        </div>
    </div>



    <div class="server-details">
        <h2>Informations Serveur</h2>
        <ul>
            <li><i class="fas fa-server"></i> <strong>Version PHP:</strong> <?php echo phpversion(); ?></li>
            <li><i class="fas fa-network-wired"></i> <strong>Adresse IP du Serveur:</strong> <?php echo $_SERVER['SERVER_ADDR']; ?></li>
            <li><i class="fas fa-clock"></i> <strong>Heure Actuelle du Serveur:</strong> <?php echo date('Y-m-d H:i:s'); ?></li>
            <li><i class="fas fa-memory"></i> <strong>Utilisation Mémoire Script:</strong> <?php echo round(memory_get_usage() / 1024 / 1024, 2) . ' MB'; ?></li>
            <li><i class="fas fa-laptop-code"></i> <strong>Software Serveur:</strong> <?php echo $_SERVER['SERVER_SOFTWARE']; ?></li>
            <li><i class="fas fa-microchip"></i> <strong>Système d'exploitation:</strong> <?php echo php_uname(); ?></li>
            <?php if (function_exists('sys_getloadavg')): ?>
                <?php $load = sys_getloadavg(); ?>
                <li><i class="fas fa-tachometer-alt"></i> <strong>Charge serveur (1 min):</strong> <?php echo $load[0]; ?></li>
            <?php endif; ?>
            <li><i class="fas fa-hdd"></i> <strong>Espace disque disponible:</strong> <?php echo formatSizeUnits(disk_free_space("/")); ?></li>
            <li><i class="fas fa-cogs"></i> <strong>Limite de mémoire PHP:</strong> <?php echo ini_get('memory_limit'); ?></li>
            <li><i class="fas fa-upload"></i> <strong>Limite de téléchargement de fichier:</strong> <?php echo ini_get('upload_max_filesize'); ?></li>
            <li><i class="fas fa-globe"></i> <strong>Version du serveur HTTP:</strong> <?php echo $_SERVER['SERVER_PROTOCOL']; ?></li>
            <li><i class="fas fa-user-shield"></i> <strong>Mode de sécurité:</strong> <?php echo ini_get('safe_mode') ? 'On' : 'Off'; ?></li>

        </ul>
    </div>


</body>
</html>
