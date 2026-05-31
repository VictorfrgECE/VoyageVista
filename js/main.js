/* ================================================================
   VoyageVista — JavaScript principal
   Fonctionnalités : menu mobile, validations, UI, budget calculator
================================================================ */

document.addEventListener('DOMContentLoaded', function () {

    // ----------------------------------------------------------------
    //  1. Menu hamburger mobile
    //     HTML attendu : <button id="navbarToggle"> + <div id="navbarMenuMobile">
    // ----------------------------------------------------------------
    const toggle = document.getElementById('navbarToggle');
    const menu   = document.getElementById('navbarMenuMobile');

    if (toggle && menu) {
        toggle.addEventListener('click', function () {
            const ouvert = menu.classList.toggle('ouvert');
            toggle.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
            toggle.classList.toggle('actif', ouvert);
        });

        // Fermer le menu si on clique ailleurs dans la page
        document.addEventListener('click', function (e) {
            if (!toggle.contains(e.target) && !menu.contains(e.target)) {
                menu.classList.remove('ouvert');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('actif');
            }
        });

        // Fermer aussi sur Échap
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                menu.classList.remove('ouvert');
                toggle.setAttribute('aria-expanded', 'false');
                toggle.classList.remove('actif');
                toggle.focus();
            }
        });
    }

    // Animation CSS des barres hamburger (injectée dynamiquement)
    var styleHamburger = document.createElement('style');
    styleHamburger.textContent = [
        '.navbar-toggle.actif span:nth-child(1){transform:translateY(7px) rotate(45deg);}',
        '.navbar-toggle.actif span:nth-child(2){opacity:0;transform:scaleX(0);}',
        '.navbar-toggle.actif span:nth-child(3){transform:translateY(-7px) rotate(-45deg);}'
    ].join('');
    document.head.appendChild(styleHamburger);

    // ----------------------------------------------------------------
    //  2. Tabs hero (Transports / Activités / Hébergements)
    //     HTML attendu : <button class="hero-tab" data-panel="ID_PANNEAU">
    // ----------------------------------------------------------------
    var tabs = document.querySelectorAll('.hero-tab');
    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            tabs.forEach(function (t) {
                t.classList.remove('actif');
                t.setAttribute('aria-selected', 'false');
            });
            tab.classList.add('actif');
            tab.setAttribute('aria-selected', 'true');

            // Afficher/masquer les panneaux associés
            var panelId = tab.getAttribute('data-panel');
            if (panelId) {
                document.querySelectorAll('.hero-panel').forEach(function (p) {
                    p.style.display = 'none';
                });
                var panel = document.getElementById(panelId);
                if (panel) { panel.style.display = ''; }
            }
        });
    });

    // ----------------------------------------------------------------
    //  3. Sélecteur voyageurs +/- (wireframe accueil)
    //     HTML attendu :
    //       <button class="voyageur-moins" data-cible="nb_adultes">-</button>
    //       <input type="number" id="nb_adultes" value="1" min="1">
    //       <button class="voyageur-plus"  data-cible="nb_adultes">+</button>
    // ----------------------------------------------------------------
    document.querySelectorAll('.voyageur-plus, .voyageur-moins').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var cibleId = btn.getAttribute('data-cible');
            var input   = document.getElementById(cibleId);
            if (!input) { return; }

            var val     = parseInt(input.value, 10) || 0;
            var min     = parseInt(input.min, 10);
            var max     = parseInt(input.max, 10);
            min = isNaN(min) ? 0 : min;

            if (btn.classList.contains('voyageur-plus')) {
                if (isNaN(max) || val < max) { input.value = val + 1; }
            } else {
                if (val > min) { input.value = val - 1; }
            }

            // Déclencher l'événement input pour les calculs en temps réel
            input.dispatchEvent(new Event('input'));
        });
    });

    // ----------------------------------------------------------------
    //  4. Validation formulaire d'inscription
    //     Vérifie que les 2 champs mot_de_passe correspondent
    // ----------------------------------------------------------------
    var formInscription = document.getElementById('form-inscription');
    if (formInscription) {
        var champMdp        = document.getElementById('mot_de_passe');
        var champConfirm    = document.getElementById('confirmer_mot_de_passe');
        var msgConfirm      = document.getElementById('msg-confirmer-mdp');

        // Vérification en temps réel pendant la frappe
        if (champConfirm) {
            champConfirm.addEventListener('input', function () {
                verifierMdp();
            });
        }
        if (champMdp) {
            champMdp.addEventListener('input', function () {
                if (champConfirm && champConfirm.value !== '') { verifierMdp(); }
            });
        }

        function verifierMdp() {
            if (!champMdp || !champConfirm) { return true; }
            var ok = champMdp.value === champConfirm.value;
            appliquerEtat(champConfirm, ok);
            if (msgConfirm) {
                msgConfirm.textContent = ok ? '' : 'Les mots de passe ne correspondent pas.';
                msgConfirm.style.color = '#D62828';
                msgConfirm.style.fontSize = '0.85em';
            }
            return ok;
        }

        // Validation force mot de passe (min 6 caractères)
        if (champMdp) {
            champMdp.addEventListener('blur', function () {
                var msgForce = document.getElementById('msg-force-mdp');
                if (champMdp.value.length > 0 && champMdp.value.length < 6) {
                    if (msgForce) {
                        msgForce.textContent = 'Le mot de passe doit contenir au moins 6 caractères.';
                        msgForce.style.color = '#D62828';
                        msgForce.style.fontSize = '0.85em';
                    }
                    appliquerEtat(champMdp, false);
                } else if (champMdp.value.length >= 6) {
                    if (msgForce) { msgForce.textContent = ''; }
                    appliquerEtat(champMdp, true);
                }
            });
        }

        // Soumission du formulaire
        formInscription.addEventListener('submit', function (e) {
            var ok = true;

            // Vérifier tous les champs requis
            formInscription.querySelectorAll('[required]').forEach(function (champ) {
                if (!validerChamp(champ)) { ok = false; }
            });

            // Vérifier la correspondance des mots de passe
            if (champMdp && champConfirm && !verifierMdp()) {
                ok = false;
            }

            if (!ok) { e.preventDefault(); }
        });
    }

    // ----------------------------------------------------------------
    //  5. Validation formulaire de connexion
    //     Empêche le submit si email ou mot de passe vides
    // ----------------------------------------------------------------
    var formConnexion = document.getElementById('form-connexion');
    if (formConnexion) {
        formConnexion.addEventListener('submit', function (e) {
            var ok = true;
            formConnexion.querySelectorAll('[required]').forEach(function (champ) {
                if (!validerChamp(champ)) { ok = false; }
            });

            // Valider le format email
            var emailChamp = formConnexion.querySelector('input[type="email"]');
            if (emailChamp && emailChamp.value) {
                var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!regex.test(emailChamp.value)) {
                    appliquerEtat(emailChamp, false);
                    ok = false;
                }
            }

            if (!ok) { e.preventDefault(); }
        });
    }

    // ----------------------------------------------------------------
    //  6. Validation en temps réel — tous les champs [required]
    // ----------------------------------------------------------------
    document.querySelectorAll('[required]').forEach(function (champ) {
        champ.addEventListener('blur', function () { validerChamp(champ); });
        champ.addEventListener('input', function () {
            if (champ.value.trim() !== '') {
                appliquerEtat(champ, true);
            }
        });
    });

    // Validation format email à la sortie du champ
    document.querySelectorAll('input[type="email"]').forEach(function (champ) {
        champ.addEventListener('blur', function () {
            if (champ.value) {
                var regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                appliquerEtat(champ, regex.test(champ.value));
            }
        });
    });

    // ----------------------------------------------------------------
    //  7. Auto-hide des alertes succès après 5 secondes
    //     Seules les .alerte-succes disparaissent automatiquement
    // ----------------------------------------------------------------
    document.querySelectorAll('.alerte-succes, .alerte.succes').forEach(function (alerte) {
        setTimeout(function () {
            alerte.style.transition = 'opacity 0.6s ease, transform 0.6s ease';
            alerte.style.opacity    = '0';
            alerte.style.transform  = 'translateY(-8px)';
            setTimeout(function () {
                alerte.style.display = 'none';
            }, 650);
        }, 5000);
    });

    // ----------------------------------------------------------------
    //  8. Confirmation avant suppression (data-confirm)
    //     HTML : <button data-confirm="Voulez-vous vraiment supprimer ?">
    //             ou <a href="..." data-confirm="...">
    // ----------------------------------------------------------------
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            var message = el.getAttribute('data-confirm') || 'Confirmer cette action ?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ----------------------------------------------------------------
    //  9. Anti double-envoi : bloquer via flag, PAS via disabled
    //     (disabled retire le name du POST — le bouton ne serait plus envoyé)
    // ----------------------------------------------------------------
    document.querySelectorAll('form:not([data-no-lock])').forEach(function (form) {
        var enCours = false;
        form.addEventListener('submit', function (e) {
            if (enCours) {
                e.preventDefault();
                return;
            }
            enCours = true;
            var btn = form.querySelector('[type="submit"]');
            if (btn) {
                btn.style.opacity = '0.65';
                btn.style.cursor  = 'not-allowed';
                btn.style.pointerEvents = 'none';
            }
            // Réactiver après 10s au cas où la page ne change pas
            setTimeout(function () {
                enCours = false;
                if (btn) {
                    btn.style.opacity = '';
                    btn.style.cursor  = '';
                    btn.style.pointerEvents = '';
                }
            }, 10000);
        });
    });

    // ----------------------------------------------------------------
    //  10. Calculateur budget étudiant (etudiant/budget.php)
    //      Champs : #transport, #logement, #activites, #nb_jours
    //      Sortie : #preview_total
    // ----------------------------------------------------------------
    var champTransport  = document.getElementById('transport');
    var champLogement   = document.getElementById('logement');
    var champActivites  = document.getElementById('activites');
    var champNbJours    = document.getElementById('nb_jours');
    var previewTotal    = document.getElementById('preview_total');

    if (previewTotal && (champTransport || champLogement || champActivites || champNbJours)) {

        function calculerBudget() {
            var transport  = parseFloat((champTransport  && champTransport.value)  || 0);
            var logement   = parseFloat((champLogement   && champLogement.value)   || 0);
            var activites  = parseFloat((champActivites  && champActivites.value)  || 0);
            var nbJours    = parseInt(  (champNbJours    && champNbJours.value)    || 1, 10);

            if (isNaN(transport)) { transport = 0; }
            if (isNaN(logement))  { logement  = 0; }
            if (isNaN(activites)) { activites = 0; }
            if (isNaN(nbJours) || nbJours < 1) { nbJours = 1; }

            // Logement et activités sont multipliés par le nombre de jours
            var total = transport + (logement * nbJours) + (activites * nbJours);

            previewTotal.textContent = total.toLocaleString('fr-FR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }) + ' €';

            // Feedback couleur : rouge si > 3000€, orange si > 1500€, vert sinon
            if (total > 3000) {
                previewTotal.style.color = '#D62828';
            } else if (total > 1500) {
                previewTotal.style.color = '#F4A261';
            } else {
                previewTotal.style.color = '#2D9E4E';
            }

            // Mise à jour du champ caché total_calcule si présent
            var champTotal = document.getElementById('total_calcule');
            if (champTotal) { champTotal.value = total.toFixed(2); }
        }

        [champTransport, champLogement, champActivites, champNbJours].forEach(function (c) {
            if (c) {
                c.addEventListener('input', calculerBudget);
                c.addEventListener('change', calculerBudget);
            }
        });

        // Calculer immédiatement au chargement si des valeurs sont pré-remplies
        calculerBudget();
    }

    // ----------------------------------------------------------------
    //  11. Étoiles de notation interactive (wireframe destinations)
    //      HTML : <div class="etoiles" data-note="3.5">
    //              génère automatiquement ★★★½☆ etc.
    // ----------------------------------------------------------------
    document.querySelectorAll('.etoiles[data-note]').forEach(function (conteneur) {
        var note = parseFloat(conteneur.getAttribute('data-note'));
        if (isNaN(note)) { return; }
        conteneur.innerHTML = '';
        conteneur.setAttribute('title', note.toFixed(1) + ' / 5');
        conteneur.setAttribute('aria-label', note.toFixed(1) + ' étoiles sur 5');

        for (var i = 1; i <= 5; i++) {
            var etoile = document.createElement('span');
            if (i <= Math.floor(note)) {
                etoile.textContent = '★';
                etoile.style.color = '#F4A261';
            } else if (i === Math.ceil(note) && note % 1 >= 0.5) {
                etoile.textContent = '½';
                etoile.style.color = '#F4A261';
            } else {
                etoile.textContent = '☆';
                etoile.style.color = '#ccc';
            }
            conteneur.appendChild(etoile);
        }
    });

    // ----------------------------------------------------------------
    //  12. Smooth scroll pour les ancres internes (#section)
    // ----------------------------------------------------------------
    document.querySelectorAll('a[href^="#"]').forEach(function (lien) {
        lien.addEventListener('click', function (e) {
            var id  = lien.getAttribute('href').slice(1);
            var cible = document.getElementById(id);
            if (cible) {
                e.preventDefault();
                cible.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    });

    // ----------------------------------------------------------------
    //  13. Affichage dynamique du nom du fichier dans les inputs file
    //      HTML : <input type="file" class="input-file" data-label="ID_DU_LABEL">
    // ----------------------------------------------------------------
    document.querySelectorAll('input[type="file"].input-file').forEach(function (input) {
        input.addEventListener('change', function () {
            var labelId = input.getAttribute('data-label');
            var label   = labelId ? document.getElementById(labelId) : null;
            if (label && input.files && input.files.length > 0) {
                label.textContent = input.files[0].name;
            }
        });
    });

    // ----------------------------------------------------------------
    //  Fonctions utilitaires internes
    // ----------------------------------------------------------------

    /**
     * Valide un champ (non vide). Retourne true si valide.
     */
    function validerChamp(champ) {
        if (!champ) { return false; }
        var vide = champ.value.trim() === '';
        appliquerEtat(champ, !vide);
        return !vide;
    }

    /**
     * Applique un état visuel (ok = vert, ko = rouge) à un champ.
     */
    function appliquerEtat(champ, ok) {
        champ.style.borderColor = ok ? '#2D9E4E' : '#D62828';
        champ.style.boxShadow   = ok
            ? '0 0 0 3px rgba(45,158,78,0.15)'
            : '0 0 0 3px rgba(214,40,40,0.15)';
        champ.setAttribute('aria-invalid', ok ? 'false' : 'true');
    }

});
