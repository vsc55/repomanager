<html>
<?php include('common-head.inc.php'); ?>

<?php
    // Import des variables et fonctions nécessaires, ne pas changer l'ordre des requires
    require 'vars/common.vars';
    require 'common-functions.php';
    require 'common.php';
    require 'vars/display.vars';
    if ($debugMode == "enabled") { echo "Mode debug activé : "; print_r($_POST); }

    // Comme la page contient un formulaire qui renvoie vers elle meme, on vérifie si des données ont été passées en POST (formulaire validé).
    // Si c'est le cas on récupère ces données et on les écrit dans le fichier de conf
    // Si ce n'est pas le cas c'est parce que la page a seulement été chargée et le formulaire n'a pas encore été validé. On n'écrit rien dans le fichier
    if(!empty($_POST['updateAuto'])) {
        $updateAuto = validateData($_POST['updateAuto']);
        exec("sed -i 's/^UPDATE_AUTO=.*/UPDATE_AUTO=\"${updateAuto}\"/g' $REPOMANAGER_CONF");
    }

    if(!empty($_POST['updateBackup'])) {
        $updateBackup = validateData($_POST['updateBackup']);
        exec("sed -i 's/^UPDATE_BACKUP=.*/UPDATE_BACKUP=\"${updateBackup}\"/g' $REPOMANAGER_CONF");
    }

    if(!empty($_POST['updateBackupDir'])) {
        $updateBackupDir = validateData($_POST['updateBackupDir']);
        exec("sed -i 's|^UPDATE_BACKUP_DIR=.*|UPDATE_BACKUP_DIR=\"${updateBackupDir}\"|g' $REPOMANAGER_CONF");
    }

    if(!empty($_POST['wwwUser'])) {
        $wwwUser = validateData($_POST['wwwUser']);
        exec("sed -i 's/^WWW_USER=.*/WWW_USER=\"${wwwUser}\"/g' $REPOMANAGER_CONF");
    }

    $oldWWWHostName = $WWW_HOSTNAME; // On conserve le hostname actuel car on va s'en servir pour le remplacer dans les fichiers de conf ci dessous
    if(!empty($_POST['wwwHostname']) AND ($oldWWWHostName !== $_POST['wwwHostname'])) {
        $newWWWHostname = validateData($_POST['wwwHostname']);
        exec("sed -i 's/^WWW_HOSTNAME=.*/WWW_HOSTNAME=\"${newWWWHostname}\"/g' $REPOMANAGER_CONF"); // on remplace dans le fichier de conf de repomanager

        // Puis on remplace dans tous les fichier de conf de repo
        if ($OS_FAMILY == "Redhat") {
            exec("find ${REPOS_CONF_FILES_DIR}/ -type f -name '*.repo' -print0 | xargs -0 sed -i 's/${oldWWWHostName}/${newWWWHostname}/g'");
        }
        if ($OS_FAMILY == "Debian") {
            exec("find ${REPOS_CONF_FILES_DIR}/ -type f -name '*.list' -print0 | xargs -0 sed -i 's/${oldWWWHostName}/${newWWWHostname}/g'");
        }
    }

    // adresse mail destinatrice des alertes
    if (!empty($_POST['emailDest'])) {
        $emailDest = validateData($_POST['emailDest']);
        exec("sed -i 's/^EMAIL_DEST=.*/EMAIL_DEST=\"${emailDest}\"/g' $REPOMANAGER_CONF");
    }

    // si on souhaite activer ou non la gestion des profils
    if (!empty($_POST['manageProfiles'])) {
        $manageProfiles = validateData($_POST['manageProfiles']);
        exec("sed -i 's/^MANAGE_PROFILES=.*/MANAGE_PROFILES=\"${manageProfiles}\"/g' $REPOMANAGER_CONF");
    }

    // modification du préfix des fichiers de conf repos
    $oldRepoFilesPrefix = $REPO_CONF_FILES_PREFIX; // On conserve le préfix actuel car on va s'en servir pour renommer les fichiers de conf ci dessous
    if(!empty($_POST['symlinksPrefix']) AND ($oldRepoFilesPrefix !== $_POST['symlinksPrefix'])) { // on ne traite que si on a renseigné un nouveau préfix
        $newRepoFilesPrefix = validateData($_POST['symlinksPrefix']);
        $confFiles = scandir($REPOS_CONF_FILES_DIR);
        foreach($confFiles as $confFile) {
            if (($confFile != "..") AND ($confFile != ".")) {
                // remplace les occurence de l'ancien préfix par le nouveau à l'intérieur du fichier
                exec("sed -i 's/${oldRepoFilesPrefix}/${newRepoFilesPrefix}/g' $confFile");

                // renomme le fichier en remplacant l'ancien prefix par le nouveau :
                $pattern = "/^${oldRepoFilesPrefix}/";
                $newConfFile = preg_replace($pattern, $newRepoFilesPrefix, $confFile);
                rename("${REPOS_CONF_FILES_DIR}/$confFile", "${REPOS_CONF_FILES_DIR}/${newConfFile}");
            }
        }
        // renomme les liens symboliques des profils :
        $profilesNames = scandir($PROFILS_MAIN_DIR);
        foreach($profilesNames as $profileName) {
            if (($profileName != "..") AND ($profileName != ".") AND ($profileName != "_configurations") AND ($profileName != "main")) {
                $profileName_dir = "$PROFILS_MAIN_DIR/$profileName";
                $repoConfFiles = scandir($profileName_dir);
                foreach($repoConfFiles as $symlink) { // Pour chaque répertoire de profil sur le serveur, on récupère les noms de fichier de conf (.repo ou .list selon l'OS)
                    if (($symlink != "..") AND ($symlink != ".") AND ($symlink != "config")) {
                        $pattern = "/^${oldRepoFilesPrefix}/";
                        $newSymlinkName = preg_replace($pattern, $newRepoFilesPrefix, $symlink);
                        exec("cd ${PROFILS_MAIN_DIR}/${profileName}/ && unlink ${symlink}"); // suppression du symlink
                        exec("cd ${PROFILS_MAIN_DIR}/${profileName}/ && ln -s ${REPOS_CONF_FILES_DIR}/${newSymlinkName}"); // création du nouveau avec le nouveau prefix
                    }
                }
            }
        }

        // enfin, remplace le préfix dans le fichier de conf repomanager.conf
        exec("sed -i 's/^REPO_CONF_FILES_PREFIX=.*/REPO_CONF_FILES_PREFIX=\"${newRepoFilesPrefix}\"/g' $REPOMANAGER_CONF");
    }   

    // Signer les paquets du repo GPG
    if (!empty($_POST['gpgSignPackages'])) {
        $gpgSignPackages = validateData($_POST['gpgSignPackages']);
        exec("sed -i 's/^GPG_SIGN_PACKAGES=.*/GPG_SIGN_PACKAGES=\"${gpgSignPackages}\"/g' $REPOMANAGER_CONF");
    }
    
    // Email lié à la clé GPG qui signe les paquets
    if (!empty($_POST['gpgKeyID'])) {
        $gpgKeyID = validateData($_POST['gpgKeyID']);
        exec("sed -i 's/^GPG_KEYID=.*/GPG_KEYID=\"${gpgKeyID}\"/g' $REPOMANAGER_CONF");
    }

    // Automatisation
    if(!empty($_POST['automatisationEnable'])) {
        $automatisationEnable = validateData($_POST['automatisationEnable']);
        exec("sed -i 's/^AUTOMATISATION_ENABLED=.*/AUTOMATISATION_ENABLED=\"${automatisationEnable}\"/g' $REPOMANAGER_CONF");
        
        // si on a activé l'automatisation mais que le fichier de planifications n'existe pas alors on le crée
        if(($automatisationEnable == "yes") AND (!file_exists($PLAN_CONF))) {
            exec("echo '[PLANIFICATIONS]' > $PLAN_CONF");
        }

        // si on a activé l'automatisation mais qu'il n'y a pas la tâche cron hebdomadaire, on la crée
        // on commence par vérifier si une tache cron est déjà présente ou non :
        $actualCrontab = shell_exec("crontab -l"); // on récupère le contenu actuel de la crontab de $WWW_USER

        // Il est possible qu'une tâche soit présente mais qu'elle soit commentée, dans ce cas on la supprime
        if (strpos($actualCrontab, "#") !== false) { // on check si on trouve un caractère '#' dans la crontab actuelle
            // on concatene le contenu actuel + suppression de la tâche commentée. On place le tout dans un fichier temporaire
            file_put_contents("/tmp/${WWW_USER}_crontab.tmp", $actualCrontab."0 0 * * * ${REPOMANAGER} --web --reminders".PHP_EOL);
            exec("sed -i '/#.*--web --reminders/d' /tmp/${WWW_USER}_crontab.tmp"); // suppression de la ligne commentée
            exec("crontab /tmp/${WWW_USER}_crontab.tmp"); // on importe le fichier dans la crontab de $WWW_USER
            unlink("/tmp/${WWW_USER}_crontab.tmp");
        }

        // si le contenu actuel ne contient pas de tâche cron de rappel, alors on la crée
        if (strpos($actualCrontab, "--web --reminders") === false) {
            // on concatene le contenu actuel + ajout de la nouvelle tâche. On place le tout dans un fichier temporaire
            file_put_contents("/tmp/${WWW_USER}_crontab.tmp", $actualCrontab."0 0 * * * ${REPOMANAGER} --web --reminders".PHP_EOL);
            exec("crontab /tmp/${WWW_USER}_crontab.tmp"); // on importe le fichier dans la crontab de $WWW_USER
            unlink("/tmp/${WWW_USER}_crontab.tmp");
        }
    }

    // Autoriser la mise à jour des repos par l'automatisation
    if(!empty($_POST['allowAutoUpdateRepos'])) {
        $allowAutoUpdateRepos = validateData($_POST['allowAutoUpdateRepos']);
        exec("sed -i 's/^ALLOW_AUTOUPDATE_REPOS=.*/ALLOW_AUTOUPDATE_REPOS=\"${allowAutoUpdateRepos}\"/g' $REPOMANAGER_CONF");
    }

    // Autoriser le changement d'environnement par l'automatisation
    if(!empty($_POST['allowAutoUpdateReposEnv'])) {
        $allowAutoUpdateReposEnv = validateData($_POST['allowAutoUpdateReposEnv']);
        exec("sed -i 's/^ALLOW_AUTOUPDATE_REPOS_ENV=.*/ALLOW_AUTOUPDATE_REPOS_ENV=\"${allowAutoUpdateReposEnv}\"/g' $REPOMANAGER_CONF");
    }

    // Autoriser la suppression des repos archivés par l'automatisation
    if(!empty($_POST['allowAutoDeleteArchivedRepos'])) {
        $allowAutoDeleteArchivedRepos = validateData($_POST['allowAutoDeleteArchivedRepos']);
        exec("sed -i 's/^ALLOW_AUTODELETE_ARCHIVED_REPOS=.*/ALLOW_AUTODELETE_ARCHIVED_REPOS=\"${allowAutoDeleteArchivedRepos}\"/g' $REPOMANAGER_CONF");
    }

    // Retention, nb de repos à conserver avant suppression par l'automatisation
    if(!empty($_POST['retention'])) {
        $retention = validateData($_POST['retention']);
        exec("sed -i 's/^RETENTION=.*/RETENTION=\"${retention}\"/g' $REPOMANAGER_CONF");
    }

// D'autres paramètres enregistrés dans display.vars
    if (!empty($_POST['debugMode'])) {
        $debugMode = validateData($_POST['debugMode']);
        exec("sed -i 's/^\$debugMode.*/\$debugMode = \"${debugMode}\";/g' ${WWW_DIR}/vars/display.vars");
    }


// Puis on récupère les infos du fichier de conf pour les afficher
    $PACKAGE_TYPE = exec("grep '^PACKAGE_TYPE=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    if ($PACKAGE_TYPE === "deb") {
        $OS_FAMILY = "Debian";
    }
    if ($PACKAGE_TYPE === "rpm") {
        $OS_FAMILY = "Redhat";
    }
    $EMAIL_DEST = exec("grep '^EMAIL_DEST=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $MANAGE_PROFILES = exec("grep '^MANAGE_PROFILES=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $REPO_CONF_FILES_PREFIX = exec("grep '^REPO_CONF_FILES_PREFIX=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    // Paramètres de maj
    $UPDATE_AUTO = exec("grep '^UPDATE_AUTO=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $UPDATE_BACKUP = exec("grep '^UPDATE_BACKUP=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $UPDATE_BACKUP_DIR = exec("grep '^UPDATE_BACKUP_DIR=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    // Paramètres WWW
    $WWW_USER = exec("grep '^WWW_USER=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    $WWW_HOSTNAME = exec("grep '^WWW_HOSTNAME=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    // Environnements
    $ENVIRONNEMENTS = exec("sed -n '/\[ENVIRONNEMENTS\]/,/\[/p' $REPOMANAGER_CONF | sed '1d;\$d' | sed '/^$/d'");
    // Paramètres automatisation    
    $AUTOMATISATION_ENABLED = exec("grep '^AUTOMATISATION_ENABLED=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    if ($AUTOMATISATION_ENABLED == "yes" ) {
        $ALLOW_AUTOUPDATE_REPOS = exec("grep '^ALLOW_AUTOUPDATE_REPOS=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
        $ALLOW_AUTOUPDATE_REPOS_ENV = exec("grep '^ALLOW_AUTOUPDATE_REPOS_ENV=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
        $ALLOW_AUTODELETE_ARCHIVED_REPOS = exec("grep '^ALLOW_AUTODELETE_ARCHIVED_REPOS=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
        $RETENTION = exec("grep '^RETENTION=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    }
    
    # D'autres paramètres spécifiques à rpm :
    if ($OS_FAMILY == "Redhat") {    $RELEASEVER = exec("grep '^RELEASEVER=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
                                $GPG_SIGN_PACKAGES = exec("grep '^GPG_SIGN_PACKAGES=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
                                $GPG_KEYID = exec("grep '^GPG_KEYID=' $REPOMANAGER_CONF | cut -d'=' -f2 | sed 's/\"//g'");
    }
?>

<body>
<?php include('common-header.inc.php'); ?>

<section class="mainSectionLeft">
    <section class="left">
        <form action="configuration.php" method="post">
        <table class="table-medium">
            <tbody>
            <tr>
                <td><h4>CONFIGURATION GÉNÉRALE</h4</td>
            </tr>
            <tr>
                <td>Type de paquets gérés</td>
                <td><input type="text" value=".<?php echo $PACKAGE_TYPE; ?>" readonly /></td>
            <?php 
            if ($OS_FAMILY == "Redhat") {
                echo "<tr>";
                echo "<td>Release version</td>";
                echo "<td><input type=\"text\" name=\"releasever\" autocomplete=\"off\" value=\"$RELEASEVER\"></td>";
                echo "</tr>";
                echo "<tr>";
                echo "<td>Signer les paquets avec GPG</td>";
                echo "<td>";
                if ( $GPG_SIGN_PACKAGES == "yes" ) {
                    echo "<input type=\"radio\" id=\"gpgSignPackages_yes\" name=\"gpgSignPackages\" value=\"yes\" checked=\"yes\" />";
                    echo "<label for=\"gpgSignPackages_yes\">Yes</label>";
                    echo "<input type=\"radio\" id=\"gpgSignPackages_no\" name=\"gpgSignPackages\" value=\"no\" />";
                    echo "<label for=\"gpgSignPackages_no\">No</label>";
                    echo "</td>";
                    echo "<tr>";
                    echo "<td>GPG Key ID (pour signature des paquets)</td>";
                    echo "<td><input type=\"text\" name=\"gpgKeyID\" autocomplete=\"off\" value=\"$GPG_KEYID\"></td>";
                    echo "</tr>"; 
                } else {
                    echo "<input type=\"radio\" id=\"gpgSignPackages_yes\" name=\"gpgSignPackages\" value=\"yes\"/>";
                    echo "<label for=\"gpgSignPackages_yes\">Yes</label>";
                    echo "<input type=\"radio\" id=\"gpgSignPackages_no\" name=\"gpgSignPackages\" value=\"no\" checked=\"yes\" />";
                    echo "<label for=\"gpgSignPackages_no\">No</label>";
                    echo "</td>";
                }        
            }?>
            <tr>
                <td>Mise à jour automatique</td>
                <td>
                    <input type="radio" id="updateAuto_radio_yes" name="updateAuto" value="yes" <?php if ($UPDATE_AUTO == "yes" ) { echo 'checked'; }?>>
                    <label for="updateAuto_radio_yes">Yes</label>
                    <input type="radio" id="updateAuto_radio_no" name="updateAuto" value="no" <?php if ($UPDATE_AUTO == "no" ) { echo 'checked'; }?>>
                    <label for="updateAuto_radio_no">No</label>
                </td>
            </tr>
            <tr>
                <td>Sauvegarde avant mise à jour</td>
                <td>
                    <input type="radio" id="updateBackup_radio_yes" name="updateBackup" value="yes" <?php if ($UPDATE_BACKUP == "yes" ) { echo 'checked'; }?>>
                    <label for="updateBackup_radio_yes">Yes</label>
                    <input type="radio" id="updateBackup_radio_no" name="updateBackup" value="no" <?php if ($UPDATE_BACKUP == "no" ) { echo 'checked'; }?>>
                    <label for="updateBackup_radio_no">No</label>
                </td>
            <?php if ($UPDATE_BACKUP == "yes" ) {
            echo "<tr>";
            echo "<td>Répertoire de sauvegarde</td>";
            echo "<td><input type=\"text\" name=\"updateBackupDir\" autocomplete=\"off\" value=\"${UPDATE_BACKUP_DIR}\"></td>";
            echo "</td>";
            echo "</tr>";
            } ?>
            </tr>
            <tr>
                <td>Destinataire (alertes mails)</td>
                <td><input type="text" name="emailDest" autocomplete="off" value="<?php echo $EMAIL_DEST; ?>"></td>
            </tr>
            <tr>
                <td><br><h4>CONFIGURATION WEB</h4></td>
            </tr>
            <tr>
                <td>Utilisateur web</td>
                <td><input type="text" name="wwwUser" autocomplete="off" value="<?php echo $WWW_USER; ?>"></td>
            </tr>
            <tr>
                <td>Hôte</td>
                <td><input type="text" name="wwwHostname" autocomplete="off" value="<?php echo $WWW_HOSTNAME; ?>"></td>
            </tr>
            <tr>
                <td>Activer la gestion des profils</td>
                <td>
                    <input type="radio" id="manageProfiles_radio_yes" name="manageProfiles" value="yes" <?php if ($MANAGE_PROFILES == "yes" ) { echo 'checked'; }?>>
                    <label for="manageProfiles_radio_yes">Yes</label> 
                    <input type="radio" id="manageProfiles_radio_no" name="manageProfiles" value="no" <?php if ($MANAGE_PROFILES == "no" ) { echo 'checked'; }?>>
                    <label for="manageProfiles_radio_no">No</label> 
                </td>
            </tr>
            <tr>
                <?php
                if ($OS_FAMILY == "Debian") {
                    echo '<td>Prefix des fichiers de repo \'.list\'</td>';
                }
                if ($OS_FAMILY == "Redhat") {
                    echo '<td>Prefix des fichiers de repo \'.repo\'</td>';
                }?>
                <td><input type="text" name="symlinksPrefix" autocomplete="off" value="<?php echo $REPO_CONF_FILES_PREFIX; ?>"></td>
            </tr>
            <tr>
                <td><br><h4>ENVIRONNEMENTS</h4></td>
            </tr>
                <?php
                foreach ($ENVS as $env) {
                    echo '<tr>';
                    echo "<td>${env}</td>";
                    echo '</tr>';
                }
                ?>
            <tr>
                <td><br><h4>PLANIFICATIONS</h4></td>
            </tr>
            <tr>
                <td>Activer les planifications</td>
                <td>
                    <input type="radio" id="automatisation_radio_yes" name="automatisationEnable" value="yes" <?php if ($AUTOMATISATION_ENABLED == "yes" ) { echo 'checked'; }?>>
                    <label for="automatisation_radio_yes">Yes</label> 
                    <input type="radio" id="automatisation_radio_no" name="automatisationEnable" value="no" <?php if ($AUTOMATISATION_ENABLED == "no" ) { echo 'checked'; }?>>
                    <label for="automatisation_radio_no">No</label> 
                </td>
            </tr>

        <?php if ($AUTOMATISATION_ENABLED == "yes" ) { 
        echo "<tr>";
        echo "<td>Autoriser la mise à jour automatique des repos</td>";
        echo "<td>";
        echo "<input type=\"radio\" id=\"allow_autoupdate_repos_radio_yes\" name=\"allowAutoUpdateRepos\" value=\"yes\""; if ($ALLOW_AUTOUPDATE_REPOS == "yes") { echo "checked >"; } else { echo " >"; }
        echo "<label for=\"allow_autoupdate_repos_radio_yes\">Yes</label>";
        echo "<input type=\"radio\" id=\"allow_autoupdate_repos_radio_no\" name=\"allowAutoUpdateRepos\" value=\"no\""; if ($ALLOW_AUTOUPDATE_REPOS == "no" ) { echo "checked >"; } else { echo " >"; }
        echo "<label for=\"allow_autoupdate_repos_radio_no\">No</label>";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Autoriser la mise à jour automatique de l'env des repos</td>";
        echo "<td>";
        echo "<input type=\"radio\" id=\"allow_autoupdate_repos_env_radio_yes\" name=\"allowAutoUpdateReposEnv\" value=\"yes\""; if ($ALLOW_AUTOUPDATE_REPOS_ENV == "yes") { echo "checked >"; } else { echo " >"; }
        echo "<label for=\"allow_autoupdate_repos_env_radio_yes\">Yes</label>";
        echo "<input type=\"radio\" id=\"allow_autoupdate_repos_env_radio_no\" name=\"allowAutoUpdateReposEnv\" value=\"no\""; if ($ALLOW_AUTOUPDATE_REPOS_ENV == "no" ) { echo "checked >"; } else { echo " >"; }
        echo "<label for=\"allow_autoupdate_repos_env_radio_no\">No</label>";
        echo "</td>";
        echo "</tr>";
        echo "<tr>";
        echo "<td>Autoriser la suppression automatique des anciens repos archivés</td>";
        echo "<td>";
        echo "<input type=\"radio\" id=\"allow_autodelete_old_repos_radio_yes\" name=\"allowAutoDeleteArchivedRepos\" value=\"yes\""; if ($ALLOW_AUTODELETE_ARCHIVED_REPOS == "yes") { echo "checked >"; } else { echo " >"; } 
        echo "<label for=\"allow_autodelete_old_repos_radio_yes\">Yes</label>";
        echo "<input type=\"radio\" id=\"allow_autodelete_old_repos_radio_no\" name=\"allowAutoDeleteArchivedRepos\" value=\"no\""; if ($ALLOW_AUTODELETE_ARCHIVED_REPOS == "no" ) { echo "checked >"; } else { echo " >"; }
        echo "<label for=\"allow_autodelete_old_repos_radio_no\">No</label>";
        echo "</td>";
        echo "</tr>"; 
        echo "<tr>";
        echo "<td>Retention (nombre de repos archivés à conserver)</td>";
        echo "<td><input type=\"number\" name=\"retention\" autocomplete=\"off\" value=\"${RETENTION}\"></td>";
        echo "</tr>";
        } ?>
            <tr>
                <td><button type="submit" class="button-submit-medium-green">Enregistrer</button></td>
            </tr>
            </tbody>
        </table>
        </form>
    </section>
</section>

<section class="mainSectionRight">
    <section class="right">
    <form action="configuration.php" method="post">
        <table class="table-medium">
            <tr>
                <td><h4>MODE DEBUG</h4></td>
            </tr>
            <tr>
                <td>
                    <select name="debugMode" class="select-small">
                        <option value="enabled" <?php if ($debugMode == "enabled") { echo "selected"; }?>>enabled</option>
                        <option value="disabled" <?php if ($debugMode == "disabled") { echo "selected"; }?>>disabled</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td><button type="submit" class="button-submit-small-green">Enregistrer</button></td>
            </tr>
        </table>
    </form>

    <table class="table-medium">
        <tr>
            <td><br><h4>ETAT DES CRON</h4></td>
        </tr>
        <tr>
            <td>Tâche cron journalière</td>
            <td>
            <?php
            // si un fichier de log existe, on récupère l'état
            if (file_exists("$CRON_LOG")) {
                $cronStatus = exec("grep 'Status=' $CRON_LOG | cut -d'=' -f2 | sed 's/\"//g'");
                if ($cronStatus === "OK") {
                    echo "Status : <span class=\"greentext\">${cronStatus}</span>";
                }
                if ($cronStatus === "KO") {
                    echo "Status : <span class=\"redtext\">${cronStatus}</span>";
                }
            }
            if (!file_exists("$CRON_LOG")) {
                echo "Status : inconnu";
            }
            ?>
            </td>
        </tr>
        <tr>
            <td>Rappels auto. des planifications à venir</td>
            <td>

            </td>
        </tr>
        <?php 
        /* Si un fichier de log cron existe c'est qu'il y a eu un problème lors de l'exécution de la tâche 
        On affiche donc une pastille rouge et le contenu du fichier de logs. 
        On affiche un bouton pour relancer la tâche manuellement 
        if (file_exists("$CRON_LOG")) {
            echo "<td>";
            echo "Etat des cron <img src=\"icons/red_circle.png\" class=\"cronStatus\">";
            echo "</td>";
            echo "<td>";
            echo "Relancer";
            echo "</td>";
            echo "</tr>";
            $content = file_get_contents("$CRON_LOG");
            echo "<td>";
            echo "<pre>";
            echo "$content";
            echo "</pre>";
            echo "</td>";
        } else {
            echo "<td>";
            echo "Etat des cron <img src=\"icons/green_circle.png\" class=\"cronStatus\">";
            echo "</td>";
        }*/
        ?>
    </table>
    </section>
</section>
<?php include('common-footer.inc.php'); ?>
</body>
</html>